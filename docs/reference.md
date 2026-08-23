---
title: Reference
description: Every interface and value object shipped by laravel-iam-contracts, grouped by namespace, with signatures.
---

# Reference

Every interface and value object shipped by `padosoft/laravel-iam-contracts`, grouped by namespace. All
classes live under `Padosoft\Iam\Contracts\`.

---

## Support

### `SubjectRef`

`final readonly class SubjectRef implements \Stringable`

A reference to a PDP subject: `user | group | service_account | external_group | agent`.

```php
public function __construct(public string $type, public string $id) {}
public function __toString(): string;   // "{type}:{id}"
```

---

## Authorization

### `AuthorizationEngine`

`interface` — the pluggable engine behind the PDP. The native engine covers RBAC + ABAC + ReBAC over SQL;
at Zanzibar scale an OpenFGA/SpiceDB adapter slots in without changing the PDP.

```php
/**
 * Deterministic allow/deny decision (+ explain, step-up, policy_version).
 * @param array<string,mixed> $query   // becomes DecisionQuery in M2
 * @return array<string,mixed>          // becomes Decision in M2
 */
public function check(array $query): array;

/** Who has `relation` on `object` (reverse index) → list-subjects.
 *  @return iterable<SubjectRef> */
public function listSubjects(string $relation, string $objectType, string $objectId): iterable;

/** What `subject` has `relation` on → list-resources.
 *  @return iterable<array{type: string, id: string}> */
public function listResources(SubjectRef $subject, string $relation): iterable;
```

---

## Crypto

### `KeyProvider`

`interface` — DEK management for envelope encryption (wrap/unwrap). Drivers: `LocalKeyProvider` (v1),
`AwsKmsKeyProvider` (M3.x), Vault/Azure/GCP/HSM (v2).

```php
/** @return array{ciphertext: string, key_id: string, key_version: int} */
public function wrapDataKey(string $plaintextDek): array;

/** @param array{ciphertext: string, key_id: string, key_version: int} $wrapped */
public function unwrapDataKey(array $wrapped): string;

/** @return array{plaintext: string, wrapped: array{ciphertext: string, key_id: string, key_version: int}} */
public function generateDataKey(): array;
```

### `SecretCipher`

`interface` — encrypt/decrypt application secrets via envelope encryption. `scope` enables per-tenant /
per-subject DEKs and therefore **crypto-shredding** (GDPR right-to-erasure).

```php
/** @return array{ciphertext: string, wrapped_dek: string|null, key_id: string, key_version: int, scope: string|null} */
public function encrypt(string $plaintext, ?string $scope = null): array;

/** @param array{ciphertext: string, wrapped_dek: string|null, key_id: string, key_version: int, scope: string|null} $value */
public function decrypt(array $value): string;

/** Destroys the DEK(s) of a scope → irreversible crypto-shredding. */
public function shred(string $scope): void;
```

### `TokenSigner`

`interface` — issue/verify signed JWTs (asymmetric, ES256) + JWKS and key rotation. Private keys are stored
encrypted (via `KeyProvider`); the public key is exposed in the JWKS.

```php
/** @param array<string,mixed> $claims  (adds iat/exp/jti and kid header) */
public function issue(array $claims, int $ttlSeconds): string;

/** Verify signature + expiry, return claims; throws on invalid token.
 *  @return array<string,mixed> */
public function parse(string $jwt): array;

/** Active + overlapping public keys (for rotation).
 *  @return list<array<string,mixed>> */
public function jwks(): array;

/** Rotate the signing key (new active; previous kept in overlap). Returns the new kid. */
public function rotate(): string;

/** Public PEM of the active key (external verification / engine placeholders).
 *  @return non-empty-string */
public function verificationPem(): string;
```

---

## Assurance

### `Aal` (enum: `aal1` | `aal2` | `aal3`)

Authenticator Assurance Level (NIST 800-63B). `AAL1` = single factor; `AAL2` = MFA / passkey; `AAL3` =
hardware/cryptographic authenticator.

```php
public function rank(): int;                  // 1 | 2 | 3
public function satisfies(self $required): bool;
public static function fromString(?string $value): self;   // unknown ⇒ AAL1 (fail-safe)
```

### `AssuranceProvider`

`interface` — current assurance level of a session.

```php
/** Current AAL of the subject on the session (AAL1 if the session is not active). */
public function currentAal(SubjectRef $subject, SessionRef $session): Aal;

/** True if this provider can raise the subject to the requested AAL. */
public function supports(Aal $target): bool;
```

### `StepUpProvider`

`interface` — step-up assurance on a critical action. The PDP requests `requires_step_up`; the provider
issues a challenge and, on verify, raises the session AAL.

```php
public function require(SubjectRef $subject, StepUpPurpose $purpose, SessionRef $session): StepUpChallenge;

/** @param array<string,mixed> $payload */
public function verify(string $challengeId, array $payload): StepUpResult;
```

### `FactorVerifier`

`interface` — verify a single authentication factor (TOTP, passkey/WebAuthn). The plug point toward
Fortify / laravel-passkeys or an external SCA adapter.

```php
/** @param array<string,mixed> $payload */
public function verify(SubjectRef $subject, array $payload): bool;
```

### Assurance DTOs

```php
final readonly class StepUpPurpose {
    public function __construct(public string $action, public Aal $requiredAal = Aal::AAL2) {}
}

final readonly class StepUpChallenge {
    public function __construct(public string $id, public string $method, public \DateTimeImmutable $expiresAt) {}
}

final readonly class StepUpResult {
    public function __construct(public bool $success, public Aal $aal) {}
}
```

---

## Governance

### `FeatureScope`

`interface` — the cross-cutting primitive that makes every governance feature toggleable and scopable across
four cascading levels (layer → app → role → user), with a safe default and a permission gate.

```php
/** Is the feature enabled for this context? (cascade layer→app→role→user) */
public function isEnabled(FeatureContext $ctx): bool;

/** Does the subject hold the permission gating the feature? */
public function isPermitted(FeatureContext $ctx, SubjectRef $actor): bool;

/** Feature mode where applicable (e.g. SoD: 'off'|'detect'|'enforce'). */
public function mode(FeatureContext $ctx): string;
```

### `FeatureKey` (enum)

`access_review` · `access_request` · `pim` · `sod` · `least_privilege` · `anomaly_detection`

### `ScopeLevel` (enum)

`layer` · `app` · `role` · `user` — resolution: most specific explicit wins (user > role > app > layer).

### `FeatureContext` (DTO)

```php
final readonly class FeatureContext {
    public function __construct(
        public FeatureKey $feature,
        public ?string $organizationId = null,
        public ?string $applicationKey = null,
        public ?string $roleKey = null,
        public ?SubjectRef $subject = null,
    ) {}
}
```

---

## Identity

### `SessionRegistry`

`interface` — server-side session registry. Every session is revocable and bound to tokens via `sid`. Idle
timeout + absolute timeout; the absolute timeout is never extended.

```php
public function start(SubjectRef $subject, SessionMeta $meta): SessionRef;
public function touch(SessionRef $session): void;            // updates last_activity; no-op if inactive
public function active(string $sessionId): bool;             // exists & not revoked & not expired — fail-closed
public function revokeSession(string $sessionId, string $reason): void;
public function revokeAllForSubject(SubjectRef $subject, string $reason): void;

/** @return iterable<int, SessionRef> */
public function listForSubject(SubjectRef $subject): iterable;
```

### `SessionRef`

`final readonly class SessionRef implements \Stringable` — wraps the `sid` that binds tokens to a session
(so revocation is possible before token expiry).

```php
public function __construct(public string $id) {}
public function __toString(): string;   // the sid
```

### `SessionMeta` (DTO)

Metadata a session opens with. Device/IP/UA identifiers are already hashed by the caller (privacy by design).
Timeouts are in seconds; the absolute timeout is the non-extendable ceiling.

```php
final readonly class SessionMeta {
    public function __construct(
        public Aal $aal = Aal::AAL1,
        public ?string $organizationId = null,
        public ?string $deviceFingerprintHash = null,
        public ?string $ipHash = null,
        public ?string $userAgentHash = null,
        public int $idleTimeout = 1800,
        public int $absoluteTimeout = 43200,
    ) {}
}
```

---

## Delegation

Delegated access for AI agents (RFC 8693 Token Exchange + `act` claim). An agent never holds the user's
token; the effective authority of a delegated call is the **strict intersection** of the user's and the
agent's permissions — never the union, fail-closed. Implemented by `laravel-iam-agents` (registry, grants,
consent, exchange grant) and `laravel-iam-server` (intersection engine decorator).

### `ActorRef`

`final readonly class ActorRef implements \Stringable` — the acting agent. Wraps a `SubjectRef` and
**enforces** `type === 'agent'` (throws otherwise). `fromActClaim(array $act): ?self` parses one `act`
level (`{"sub":"agent:…"}`).

### `DelegationChain`

`final readonly class DelegationChain` — ordered actor chain, current actor first (RFC 8693 §4.1 nested
`act`). `toActClaim()` serializes the nested claim; `fromTokenClaims()` returns `null` when no `act` claim
is present and **throws** on a malformed one (a delegated token never degrades to full-user authority).

### `DelegationGrant` / `DelegationGrantStatus`

The user's consented delegation: agent, scopes, human-readable `purpose`, expiry, status
(`Active|Suspended|Expired|Revoked`), plus the step-up consent evidence (`consentConfirmationId`,
`consentAal`). `isUsableAt(now)` = Active and not expired. The grant id travels in tokens as the private
claim `pds_dgr` (targeted revocation).

### `AgentDescriptor` / `AgentStatus` / `AgentRegistry`

Agents are first-class identities with a triple-identity model: `operator` (provider), agent instance
(`subject`, type `agent`), delegating user (in the grant). `maxScopes` is the manifest-derived ceiling.
`AgentStatus::Pending` is where agentic registrations (gated DCR / auth.md) land — `Active` only through
human approval. `AgentRegistry::find()` returns `null` for unknown agents ⇒ deny downstream.

### `DelegationGrantStore`

`findActive(user, agent)`, `find(grantId)` (per-decision `pds_dgr` lookup), `listFor(user)`
(self-service), `revoke(grantId, revokedBy)` (idempotent, audited).

### `TokenExchanger` / `TokenExchangeRequest` / `TokenExchangeResult`

Client side of RFC 8693: the agent authenticates with its own `private_key_jwt` and presents the user's
token as `subject_token`. The issued token is short-lived, carries `sub`+`act`+`pds_dgr`, and is **not
refreshable by design** — re-exchange is the revocation freshness check. `scopes` in the result is always
explicit.

### `ActClaim`

Protocol constants: `act`, the RFC 8693 grant-type/token-type URNs (incl. `id-jag` for auth.md interop),
the private claim `pds_dgr`, the dedicated `typ` header `delegated+jwt`.

### `DelegationContext`

Cross-cutting observability VO (`sub`, chain, grant id, decision id, correlation id) hydrated once and
propagated via Laravel Context so every log/audit record answers "who did what, on behalf of whom".
`toLogContext()` uses stable, redaction-safe keys (no `token` substring).

### `DelegatedAuthorizationEngine`

`interface DelegatedAuthorizationEngine extends AuthorizationEngine` — the intersection PDP:
`checkDelegated(SubjectRef $subject, DelegationChain $chain, array $query): array`. Two `check()` passes
(user, agent) plus the grant being active; compound deny-overrides; the decision cites both subjects.
New interface by design ("add, don't mutate").
