---
title: Concepts
description: The mental model behind the contracts — PDP seam, subject references, crypto envelope, assurance levels, feature scopes.
---

# Concepts

## The problem

A control plane split across many packages has a coupling hazard: if the client imports a server class, or
the directory module imports a crypto implementation, the packages become a tangle. You cannot release them
independently, you cannot swap an engine, and a change in one ripples into all.

## The mental model

> **Depend on a contract, not on a class.**

`laravel-iam-contracts` is the single package every other one is allowed to depend on. It contains *only*
the seams — interfaces and immutable value objects — and **no behaviour to couple to**. Implementations live
elsewhere (`-server`, `-directory`, adapters) and are wired in at runtime via Laravel's container.

This is the classic *ports & adapters* shape: the contracts are the ports; the concrete engines, key
providers and verifiers are the adapters.

## Core entities

| Concept | Type | What it is |
| --- | --- | --- |
| **Subject** | `SubjectRef` (DTO) | A `type:id` reference (user, group, service account, external group, agent). |
| **Authorization** | `AuthorizationEngine` (interface) | The pluggable PDP: `check`, plus `listSubjects` / `listResources`. |
| **Crypto** | `KeyProvider`, `SecretCipher`, `TokenSigner` (interfaces) | Envelope encryption, secret cipher with crypto-shredding, JWT signing + JWKS. |
| **Assurance** | `Aal` (enum), `AssuranceProvider`, `StepUpProvider`, `FactorVerifier` | Authenticator assurance levels and step-up on critical actions. |
| **Governance** | `FeatureScope` (interface), `FeatureKey` / `ScopeLevel` (enums), `FeatureContext` (DTO) | Turning IGA features on/off, cascading layer → app → role → user. |
| **Identity** | `SessionRegistry` (interface), `SessionRef` / `SessionMeta` (DTOs) | Revocable server-side sessions bound to tokens via `sid`. |

## Example: swapping the authorization engine

The PDP is defined by one interface. Today the native engine resolves RBAC + ABAC + ReBAC over SQL:

```php
$engine = new NativeSqlEngine(/* ... */);          // lives in laravel-iam-server
```

Tomorrow, at Zanzibar scale, you bind a different adapter:

```php
$engine = new OpenFgaEngine($fgaClient);            // hypothetical v2 adapter
```

Both satisfy `AuthorizationEngine`. The PDP, the Admin API, the client middleware — none of them change,
because they were typed against the contract, never the class.

## Anti-patterns

- **Adding a runtime dependency here.** This package must require `php` only. Pulling in `illuminate/*` or
  any concrete library defeats its purpose and forces it on every consumer.
- **Putting behaviour in the contracts.** Beyond tiny pure helpers on enums (e.g. `Aal::satisfies()`), logic
  belongs in implementations, not here.
- **Silently changing an interface.** Adding or changing a method is a **breaking change** for every
  implementor across the ecosystem. Prefer a new interface; bump the major version when you must.
- **Stringly-typed subjects.** Passing `"user:42"` strings around instead of `SubjectRef` loses the type
  safety the value object exists to provide.

## Why it is shaped this way

- **Independent releases.** Each package versions on its own; they only have to agree on the contract version.
- **Pluggability.** Engines, key custodians and verifiers are swappable without touching consumers.
- **Fail-closed by construction.** The signatures bake in safe defaults — `SessionRegistry::active()` is
  fail-closed, `Aal::fromString(null)` returns the weakest level — so an implementor falling back to the
  documented default is automatically safe.
