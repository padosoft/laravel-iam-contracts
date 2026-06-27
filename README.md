<p align="center">
  <img src="art/banner.png" alt="Laravel IAM" width="100%">
</p>

<h1 align="center">Laravel IAM — Contracts</h1>

<p align="center">
  <strong>The shared contract layer of the Laravel IAM ecosystem.</strong><br>
  Interfaces and immutable value objects every <code>padosoft/laravel-iam-*</code> package implements or consumes.
</p>

<p align="center">
  <a href="https://packagist.org/packages/padosoft/laravel-iam-contracts"><img src="https://img.shields.io/packagist/v/padosoft/laravel-iam-contracts.svg?style=flat-square" alt="Latest Version on Packagist"></a>
  <a href="https://packagist.org/packages/padosoft/laravel-iam-contracts"><img src="https://img.shields.io/packagist/dt/padosoft/laravel-iam-contracts.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/padosoft/laravel-iam-contracts"><img src="https://img.shields.io/packagist/php-v/padosoft/laravel-iam-contracts.svg?style=flat-square" alt="PHP Version"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square" alt="License"></a>
</p>

---

## Why this package

Laravel IAM is an **Identity & Authorization Control Plane** split across several packages — a server, a
client, governance/AI modules, a directory connector, migration bridges. They all need to speak the same
language: *what is a subject? how does the PDP decide allow/deny? how is a secret encrypted? what is an
assurance level?*

`laravel-iam-contracts` is that language. It ships **only interfaces and `final readonly` value objects** —
no implementations, **no Laravel dependency, no runtime dependencies at all** (just PHP). It is the
**dependency root** of the ecosystem: everything depends on it, it depends on nothing.

That gives you the property that makes the whole platform pluggable: **you depend on abstractions, not
implementations.** Swap the native SQL authorization engine for an OpenFGA/SpiceDB (Zanzibar) backend, swap
the local key provider for AWS KMS or an HSM, swap the passkey verifier for an external SCA provider — and
**none of the consuming code changes**, because it was typed against these contracts.

## Features

- **`AuthorizationEngine`** — the pluggable PDP contract: `check()` for deterministic allow/deny decisions
  (with explain), plus `listSubjects()` / `listResources()` reverse-index queries (Zanzibar-style).
- **`SubjectRef`** — the `type:id` value object (`final readonly implements Stringable`) used across the
  whole ecosystem to reference users, groups, service accounts, external groups and agents.
- **Crypto seam** — `KeyProvider` (envelope encryption: wrap/unwrap/generate DEKs), `SecretCipher`
  (encrypt/decrypt/`shred` with per-tenant `scope` → GDPR crypto-shredding), `TokenSigner` (ES256 JWT +
  JWKS + key rotation).
- **Assurance (NIST 800-63B)** — the `Aal` enum with `rank()`/`satisfies()`, plus `AssuranceProvider`,
  `StepUpProvider` and `FactorVerifier` for step-up authentication on critical actions.
- **Governance / IGA** — `FeatureScope`: a single primitive to turn every governance feature (Access
  Review/Request, PIM, SoD, anomaly detection, least-privilege) on/off, cascading across four levels
  (layer → app → role → user).
- **Identity** — `SessionRegistry` for revocable, server-side sessions (idle + absolute timeout,
  fail-closed) bound to tokens via a `sid`.
- **Zero runtime dependencies** — `require` is `php` only. Installs anywhere, drags nothing in.

## Use cases

- **Write an alternative authorization engine.** Implement `AuthorizationEngine` against OpenFGA, SpiceDB,
  or your own store, register it behind the PDP, and the server keeps working unchanged.
- **Type your domain against a stable subject reference.** Accept `SubjectRef` in your services and audit
  records instead of stringly-typed `"user:42"` — one value object, `Stringable`, used everywhere.
- **Plug a different key custodian.** Implement `KeyProvider` for AWS KMS / Vault / an HSM without touching
  the code that calls `SecretCipher`. The envelope-encryption contract stays the same.
- **Gate a governance feature.** Use `FeatureScope::isEnabled()` / `isPermitted()` to roll out PIM or SoD
  per organization, per role, or per user, with a safe default.

## Installation

```bash
composer require padosoft/laravel-iam-contracts
```

**Requirements:** PHP **8.3+**. No Laravel required — this package is framework-agnostic and dependency-free.

## Quick start

### 1. Reference a subject

```php
use Padosoft\Iam\Contracts\Support\SubjectRef;

$subject = new SubjectRef(type: 'user', id: '42');

(string) $subject;   // "user:42"  — Stringable, safe to log, store, and key on
```

### 2. Implement the authorization engine

The PDP is pluggable: provide an `AuthorizationEngine` and the platform routes decisions through it.

```php
use Padosoft\Iam\Contracts\Authorization\AuthorizationEngine;
use Padosoft\Iam\Contracts\Support\SubjectRef;

final class MyEngine implements AuthorizationEngine
{
    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function check(array $query): array
    {
        // deterministic, deny-overrides; never fail-open
        return ['decision' => 'deny', 'reason' => 'no_matching_grant'];
    }

    /** @return iterable<SubjectRef> */
    public function listSubjects(string $relation, string $objectType, string $objectId): iterable
    {
        return [];
    }

    /** @return iterable<array{type: string, id: string}> */
    public function listResources(SubjectRef $subject, string $relation): iterable
    {
        return [];
    }
}
```

### 3. Compare assurance levels

```php
use Padosoft\Iam\Contracts\Assurance\Aal;

Aal::AAL2->satisfies(Aal::AAL1);   // true  — MFA satisfies a single-factor requirement
Aal::AAL1->satisfies(Aal::AAL2);   // false — step-up required
Aal::fromString(null);             // Aal::AAL1 — fail-safe: unknown ⇒ weakest level
```

### 4. Gate a governance feature

```php
use Padosoft\Iam\Contracts\Governance\FeatureScope;
use Padosoft\Iam\Contracts\Governance\FeatureContext;
use Padosoft\Iam\Contracts\Governance\FeatureKey;
use Padosoft\Iam\Contracts\Support\SubjectRef;

function maybeReview(FeatureScope $scope): void
{
    $ctx = new FeatureContext(
        feature: FeatureKey::AccessReview,
        organizationId: 'org_123',
    );

    if ($scope->isEnabled($ctx) && $scope->isPermitted($ctx, new SubjectRef('user', '42'))) {
        // run the access review
    }
}
```

## Ecosystem

| Package | Role |
| --- | --- |
| **laravel-iam-contracts** *(this repo)* | Shared interfaces & DTOs — the dependency root |
| [laravel-iam-server](https://github.com/padosoft/laravel-iam-server) | The IAM server: identity, PDP (RBAC+ABAC+ReBAC), OAuth/OIDC, audit, governance, Admin API & panel |
| [laravel-iam-client](https://github.com/padosoft/laravel-iam-client) | Client for apps consuming Laravel IAM: OIDC login, JWT/JWKS, middleware, Gate adapter |
| [laravel-iam-ai](https://github.com/padosoft/laravel-iam-ai) | Optional AI module: advisory-only governance (redaction + hallucination guard + audit) |
| [laravel-iam-directory](https://github.com/padosoft/laravel-iam-directory) | Optional directory module: LDAP / Active Directory (LdapRecord); SCIM in v2 |
| [laravel-iam-bridge-spatie-permission](https://github.com/padosoft/laravel-iam-bridge-spatie-permission) | Migration bridge from spatie/laravel-permission: scan, shadow mode, decision diffing, cutover |

## Documentation

A docmd doc-site lives in [`docs/`](docs/): start at [`docs/index.md`](docs/index.md), then
[Getting started](docs/getting-started.md), [Concepts](docs/concepts.md) and the full
[Reference](docs/reference.md) of every interface and DTO.

## Security

Laravel IAM is **fail-closed by design**: default-deny, deny-overrides, and any error (transport, PDP,
parsing) resolves to *deny* — never an allow, never an opaque 500. These contracts encode that ethos in
their signatures (e.g. `SessionRegistry::active()` is fail-closed; `Aal::fromString(null)` returns the
weakest level). If you discover a security issue, please email **security@padosoft.com** rather than opening
a public issue.

## License

MIT © [Padosoft](https://www.padosoft.com). See [LICENSE](LICENSE).
