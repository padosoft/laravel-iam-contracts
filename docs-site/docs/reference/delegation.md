---
title: "Delegation"
description: "The delegated-access contracts for AI agents: ActorRef, DelegationChain, DelegationGrant, the RFC 8693 token-exchange DTOs, DelegationContext for observability, and the DelegatedAuthorizationEngine intersection PDP."
---

# Delegation

The `Delegation\` namespace is the contract layer for **delegated access for AI agents**: an agent never
holds the user's token — it exchanges it (OAuth 2.0 Token Exchange, **RFC 8693**) for a short-lived,
down-scoped token carrying **both identities** (`sub` = user, `act` = agent), and every authorization
decision is the **strict intersection** of what the user may do and what the agent may do. Never the union.

These contracts are implemented by [`laravel-iam-agents`](https://github.com/padosoft/laravel-iam-agents)
(agent registry, delegation grants, consent, the token-exchange grant — full docs at
[doc.laravel-iam-agents.padosoft.com](https://doc.laravel-iam-agents.padosoft.com)) and by `laravel-iam-server`
(the `DelegatedAuthorizationEngine` decorator); they are consumed by the PEP SDKs and by agent runtimes
such as `laravel-flow-ai`.

::: callout warning "Fail-closed, everywhere" icon:alert-triangle
A token **with** an `act` claim must never be evaluated as a plain user token. A malformed `act` claim
throws — it never degrades to full-user authority. An unknown, pending, suspended or retired agent is a
deny. This namespace encodes the confused-deputy defence; do not soften it.
:::

## `ActorRef`

`final readonly class ActorRef implements \Stringable`

The acting party of a delegation: an agent. Wraps a `SubjectRef` and **enforces** `type === 'agent'`
(constructor throws otherwise). Not to be confused with the `$actor` parameter of
`Governance\FeatureScope`, which means "the current caller" — here *actor* is the OAuth `act` sense
(RFC 8693 §4.1).

```php
public const string SUBJECT_TYPE = 'agent';

public function __construct(public SubjectRef $subject) {}    // throws if type !== 'agent'
public static function fromAgentId(string $agentId): self;
public static function fromActClaim(array $act): ?self;       // {"sub":"agent:…"} → ActorRef|null
public function __toString(): string;                          // "agent:{id}"
```

## `DelegationChain`

`final readonly class DelegationChain`

The ordered actor chain — current actor first, then previous hops (RFC 8693 §4.1 nested `act`).
Depth is 1 in the MVP; the VO already models N hops for multi-hop delegation.

```php
public function __construct(ActorRef ...$actors);              // throws on empty
public function current(): ActorRef;                           // the outermost actor
public function depth(): int;
public function toActClaim(): array;                           // nested {"sub":…,"act":{…}}
public static function fromTokenClaims(array $claims): ?self;  // null = no act claim; malformed = throws
```

## `DelegationGrant` / `DelegationGrantStatus`

`final readonly class DelegationGrant` · `enum DelegationGrantStatus: string`

The user's consented delegation: *agent X may act for me in scopes S, for purpose P, until T*. Consent is
a step-up confirmation (AAL2, dynamic-linking over the parameters) cited by `consentConfirmationId` /
`consentAal`. The grant id travels inside issued tokens as the private claim `pds_dgr` so revocation can
be enforced before natural expiry.

```php
public function __construct(
    public string $id,
    public SubjectRef $user,
    public SubjectRef $agent,
    public array $scopes,                    // list<string>
    public string $purpose,
    public DelegationGrantStatus $status,    // Active|Suspended|Expired|Revoked
    public \DateTimeImmutable $expiresAt,
    public \DateTimeImmutable $createdAt,
    public ?string $consentConfirmationId = null,
    public ?Aal $consentAal = null,
    public ?\DateTimeImmutable $revokedAt = null,
    public ?SubjectRef $revokedBy = null,
    public ?DelegationBudget $budget = null,     // v1.4: scopes bound authority, budgets bound intensity
) {}

public function isUsableAt(\DateTimeImmutable $now): bool;   // Active AND not expired
```

New constructor parameters are always appended **after** the existing ones with a default — positional
consumers keep working across minor versions.

## `AgentDescriptor` / `AgentStatus` / `AgentRegistry`

`final readonly class AgentDescriptor` · `enum AgentStatus: string` · `interface AgentRegistry`

Agents are first-class identities with a **triple-identity model**: the `operator` (the provider running
the agent — "openai", "anthropic", in-house), the agent instance (`subject`, type `agent`) and the
delegating user (in the grant). `maxScopes` is the ceiling derived from the application manifest —
admin-reducible, never exceedable. `AgentStatus::Pending` is where agentic registrations (gated DCR /
auth.md) land: they become `Active` **only through human approval**.

```php
enum AgentStatus: string { case Pending; case Active; case Suspended; case Retired; }

final readonly class AgentDescriptor
{
    public function __construct(
        public SubjectRef $subject,
        public AgentStatus $status,
        public array $maxScopes,             // list<string>
        public ?string $operator = null,
        public ?SubjectRef $owner = null,
        public ?string $applicationId = null,
    ) {}
}

interface AgentRegistry
{
    public function find(SubjectRef $agent): ?AgentDescriptor;   // null ⇒ deny downstream
}
```

## `DelegationGrantStore`

`interface DelegationGrantStore`

```php
public function findActive(SubjectRef $user, SubjectRef $agent): ?DelegationGrant;
public function find(string $grantId): ?DelegationGrant;      // pds_dgr lookup, used per-decision
public function listFor(SubjectRef $user): iterable;          // self-service "my delegations"
public function revoke(string $grantId, SubjectRef $revokedBy): void;   // idempotent
```

## Token exchange — `TokenExchanger`, `TokenExchangeRequest`, `TokenExchangeResult`

The client side of RFC 8693. The agent authenticates with its **own** credentials (`private_key_jwt`,
RFC 7523) and presents the user's token as `subject_token`. The issued token is short-lived, carries
`sub` + `act` + `pds_dgr`, and is **not refreshable by design** — re-exchanging *is* the revocation
freshness check.

```php
final readonly class TokenExchangeRequest
{
    public function __construct(
        public string $subjectToken,
        public string $subjectTokenType = ActClaim::TOKEN_TYPE_ACCESS,
        public array $scopes = [],                    // [] = all granted scopes
        public ?string $audience = null,
        public ?string $resource = null,
        public string $requestedTokenType = ActClaim::TOKEN_TYPE_ACCESS,
        public ?string $actorToken = null,            // multi-hop only (v2)
        public ?string $actorTokenType = null,
    ) {}
}

final readonly class TokenExchangeResult
{
    public function __construct(
        public string $accessToken,
        public string $issuedTokenType,
        public int $expiresIn,
        public array $scopes,                         // always explicit — may differ from requested
        public string $tokenType = 'Bearer',
    ) {}
}

interface TokenExchanger
{
    public function exchange(TokenExchangeRequest $request): TokenExchangeResult;
}
```

## Budget & intensity — `DelegationBudget`, `BudgetVerdict`, `DelegationBudgetGuard`

*(v1.4)* Scopes bound **authority** (what the agent may do); a budget bounds **intensity** (how much
it may do it). `DelegationBudget` is part of the consent — when present it enters the dynamic-linking
binding exactly like agent/scopes/ttl/purpose:

```php
final readonly class DelegationBudget {
    public function __construct(
        public ?float $amount = null,   // max spend in `currency`
        public string $currency = 'EUR',
        public ?int $tokens = null,     // max LLM tokens
        public ?int $calls = null,      // max calls / tool invocations
    );                                   // at least one cap required; caps must be positive
    public function toArray(): array;    // canonical: sorted keys, only present caps
    public static function fromArray(array $data): self;
}

interface DelegationBudgetGuard {        // reference implementation: laravel-ai-finops
    public function verdict(DelegationGrant $grant): BudgetVerdict;
}

final readonly class BudgetVerdict {
    public static function allow(array $remaining = []): self;
    public static function deny(string $reason, array $remaining = []): self;  // reason mandatory
}
```

**Fail-closed contract for callers** (the token-exchange grant honours this): a grant **with** a
budget in a deployment **without** a bound guard is unenforceable ⇒ the exchange is refused. Grants
without a budget never consult the guard.

## JIT elevation — `ElevationRequest`, `ElevationNotifier`

*(v1.4)* When an agent hits an action outside its grant, the runtime can ask the delegating user to
extend the consent instead of dead-ending on a deny:

```php
final readonly class ElevationRequest {
    public function __construct(
        public string $id, public string $grantId,
        public SubjectRef $user, public SubjectRef $agent, public string $agentName,
        public array $requestedScopes,           // non-empty list<string>
        public string $reason,                    // mandatory: the user must understand the ask
        public \DateTimeImmutable $expiresAt,    // pending window — expires fail-closed
    );
}

interface ElevationNotifier {                     // reference implementation: laravel-rebel-channels
    /** @throws \Throwable when delivery fails on every configured channel */
    public function notify(ElevationRequest $request): void;
}
```

The notifier only **informs** — approval always goes through the step-up consent flow (dynamic
linking over the extra scopes), never through an in-channel "approve".

## `AgentLifecycle`

*(v1.4)* Lifecycle transitions invocable by security components (reference consumer:
`laravel-rebel-ai-guard` auto-suspend on delegation-stream anomalies):

```php
interface AgentLifecycle {
    public function suspend(SubjectRef $agent, string $reason, string $actor): void;  // idempotent
}
```

Deliberately **suspend-only** (re-activation is a human decision) and **separate from
`AgentRegistry`** (add, don't mutate): read-only consumers never receive the capability to suspend.

## `ActClaim`

`final class ActClaim` — the protocol constants: the `act` claim name, the RFC 8693 grant-type and
token-type URNs (including `id-jag` for auth.md interop), the private claim `pds_dgr`, and the dedicated
`typ` header `delegated+jwt` (spec hygiene — **not** the only defence: delegated tokens are
introspection-mandatory).

## `DelegationContext`

`final readonly class DelegationContext`

The cross-cutting observability VO: *who* (`sub`), *through whom* (`chain`), *under which grant*
(`grantId`), *correlated how* (`correlationId`). Hydrated once (HTTP/job middleware) and propagated via
Laravel Context so **every** log and audit record in any package answers "who did what, on behalf of
whom". `toLogContext()` returns stable, redaction-safe keys (no key contains the substring `token`).

Since `laravel/ai` **0.11** it also carries *which piece of work*: `invocationId` names the AI run the
record belongs to, and `parentInvocationId` names the run that delegated to it when an agent executed
as another agent's tool — the same parent→child shape as a nested `act` claim, observed from the
runtime instead of from the token. Both are optional trailing parameters, and `withInvocation()`
returns a copy carrying them, so a context built before this existed is unchanged and an application
with no SDK never gains empty keys.

Correlating by timestamp was the alternative, and it fails exactly when it matters: two agent runs
overlapping is precisely the situation you are investigating.

```php
$context = $context->withInvocation('inv_01K2...', parentInvocationId: 'inv_01K1...');

$context->toLogContext();
// [..., 'invocation_id' => 'inv_01K2...', 'parent_invocation_id' => 'inv_01K1...']
```

Stamping is `laravel-iam-agents`' job (it listens to the SDK's step events); this package only
defines the shape, and takes **no** dependency on `laravel/ai`.

## `DelegatedAuthorizationEngine`

`interface DelegatedAuthorizationEngine extends AuthorizationEngine`

The intersection PDP — a **new** interface (the ecosystem rule is "add, don't mutate": never add methods
to existing interfaces). Implemented in `-server` as a decorator over the native engine: two `check()`
passes (user, agent) plus the `pds_dgr` grant still being active. Compound deny-overrides: an explicit
deny on either layer denies; missing allow on either layer denies. The decision cites **both** subjects
so an auditor can replay each layer independently.

```php
public function checkDelegated(SubjectRef $subject, DelegationChain $chain, array $query): array;
```
