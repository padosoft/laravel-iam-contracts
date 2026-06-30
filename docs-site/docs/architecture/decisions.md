---
title: "Architecture decisions (ADR)"
description: "The load-bearing decisions behind laravel-iam-contracts: standalone dependency-free package, array placeholders for DecisionQuery/Decision, final readonly value objects, Stringable subject refs, pre-hashed session metadata, and FeatureScope as a single cross-cutting primitive."
---

# Architecture decisions (ADR)

Every choice that shapes this package is recorded here as *Problem → Decision → Consequences*. These ADRs
explain not just *what* the contracts look like but *why* — so a future change is made with the original
trade-off in view.

## ADR-001 — Standalone, dependency-free contracts package

::: collapsible open "Problem → Decision → Consequences"
**Problem.** The platform is many packages that must interoperate, release independently, and allow
implementations to be swapped. Concrete cross-package dependencies would create cycles and lockstep
releases.

**Decision.** Ship all shared interfaces and value objects in one package that requires **`php` only**,
contains **no implementations**, and is the **sink** of the dependency graph.

**Consequences.** No cycles; independent releases; swappable adapters; tiny reviewable surface. The cost is
that any change to a published interface is breaking — so the package evolves by **adding** interfaces, not
mutating them. (Full treatment in [Why a contracts-only package](/concepts/why-contracts).)
:::

## ADR-002 — `array<string, mixed>` placeholders for the decision contract

::: collapsible "Problem → Decision → Consequences"
**Problem.** `AuthorizationEngine::check()` needs a rich query (subject, action, resource, context) and a
rich result (decision, reason, explain trace, step-up flag, `policy_version`). Designing the final
`DecisionQuery` / `Decision` value objects up front would block shipping the engine and risk locking in a
shape before the PDP's `combine`/`explain` algorithm settled.

**Decision.** Ship `check()` with `array<string, mixed>` for both input and output **now**, documented with
a `TODO(M2)` to harden into `DecisionQuery` / `Decision` value objects in a later major. The array shape
deliberately mirrors the **HTTP wire contract** (`POST /api/iam/v1/decisions/check`, `{ "data": … }`
envelope) so the in-process and over-the-wire forms stay isomorphic.

**Consequences.**
- *Positive:* the engine ships and the SDKs align to one shape; the in-process array is trivially
  serialisable to the wire JSON.
- *Negative:* weaker static typing today — implementors rely on documented keys rather than a typed object.
- *Migration:* introducing `DecisionQuery` / `Decision` is a **major** bump; it will be additive where
  possible (a new typed overload) to ease the transition. See [Versioning](/architecture/versioning).
:::

## ADR-003 — Value objects are `final readonly`

::: collapsible "Problem → Decision → Consequences"
**Problem.** DTOs that travel across package boundaries (`SubjectRef`, `SessionMeta`, `FeatureContext`,
the step-up DTOs) must be safe to pass, log, store and key on without a consumer mutating shared state.

**Decision.** Every value object is declared `final readonly` with public promoted constructor properties.
No setters, no subclassing.

**Consequences.** Immutability by construction — a `SubjectRef` you hand out cannot be changed under you.
`final` keeps the type a stable contract (no surprising subclass overriding `__toString()`). The minor cost
is that "changing" a value means constructing a new one, which is the intended semantics.
:::

## ADR-004 — `SubjectRef` and `SessionRef` are `Stringable`

::: collapsible "Problem → Decision → Consequences"
**Problem.** Subjects and sessions are referenced constantly — in logs, audit records, cache keys, policy
tuples. A bare `"user:42"` string is easy to mistype and impossible to type-check; a heavyweight object is
awkward where a string is expected.

**Decision.** Make `SubjectRef` render as `"{type}:{id}"` and `SessionRef` render as its `sid`, both via
`implements \Stringable`. You get a typed object that drops into any string context.

**Consequences.** One canonical, type-checked reference that is still `(string)`-castable for logs and
keys. Consumers should accept `SubjectRef`, not `string`, to keep the type safety — passing the stringified
form around again loses it.
:::

## ADR-005 — Session metadata is pre-hashed by the caller

::: collapsible "Problem → Decision → Consequences"
**Problem.** A session needs device/IP/user-agent context for security decisions and device management, but
storing raw IPs and fingerprints is a privacy liability (GDPR data minimisation).

**Decision.** `SessionMeta` carries `deviceFingerprintHash`, `ipHash`, `userAgentHash` — **already hashed by
the caller** (privacy by design). The registry never sees raw identifiers. Timeouts are explicit seconds,
and the `absoluteTimeout` is documented as a **non-extendable** ceiling.

**Consequences.** The contract makes the privacy-preserving path the default one. The caller owns the
hashing policy (salt, algorithm), keeping that concern out of the contract. The absolute-timeout ceiling is
encoded as a documented invariant the registry must honour.
:::

## ADR-006 — `FeatureScope` as a single cross-cutting governance primitive

::: collapsible "Problem → Decision → Consequences"
**Problem.** Governance/IGA has many features (Access Review, Access Request, PIM, SoD, least-privilege,
anomaly detection). Each needs to be toggled and scoped per organization, app, role or user. Modelling each
feature's gate separately would duplicate the same cascade logic six times.

**Decision.** Express *all* of them through one primitive: `FeatureScope` with `isEnabled()` /
`isPermitted()` / `mode()`, a `FeatureKey` enum to name the feature, a `ScopeLevel` enum for the four
cascade levels (layer → app → role → user, most-specific-explicit wins), and a `FeatureContext` DTO to
carry the evaluation context.

**Consequences.** One tested cascade resolver gates every governance feature; turning PIM or SoD on for one
org, role or user is uniform. New features are a new `FeatureKey` case, not a new interface. `mode()`
returns a `string` (e.g. SoD `'off'|'detect'|'enforce'`) to stay open to feature-specific modes without
widening the interface.
:::

## ADR-007 — `FactorVerifier` is a separate, pluggable seam

::: collapsible "Problem → Decision → Consequences"
**Problem.** Step-up authentication must verify a factor (TOTP, passkey/WebAuthn). Binding the step-up flow
directly to Fortify or laravel-passkeys would hard-wire the platform to one authenticator stack.

**Decision.** Split verification into its own one-method interface, `FactorVerifier::verify()`, that the
`StepUpProvider` depends on. The concrete verifier (Fortify/laravel-passkeys, or an external SCA/Rebel
adapter) is injected.

**Consequences.** The step-up lifecycle (`require` → challenge → `verify` → raise AAL) is independent of
*how* a factor is checked. Swapping the authenticator stack is a binding change, and the step-up provider
stays untouched.
:::

## Related

- [Why a contracts-only package](/concepts/why-contracts) — the umbrella decision.
- [Versioning & ABI stability](/architecture/versioning) — how these decisions evolve safely.
- [Contract reference](/reference/overview) — the symbols these ADRs shape.
