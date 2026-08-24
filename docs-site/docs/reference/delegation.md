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
) {}

public function isUsableAt(\DateTimeImmutable $now): bool;   // Active AND not expired
```

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
