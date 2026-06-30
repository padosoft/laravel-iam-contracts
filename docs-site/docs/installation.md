---
title: "Installation"
description: "Install padosoft/laravel-iam-contracts with Composer. PHP 8.3+, zero runtime dependencies, PSR-4 autoloading under Padosoft\\Iam\\Contracts. No Laravel required."
---

# Installation

## Requirements

| Requirement | Value |
| --- | --- |
| PHP | **8.3+** (uses enums, `readonly` classes, `final readonly`) |
| Composer | any recent version |
| Laravel | **not required** — the package is framework-agnostic |
| Runtime dependencies | **none** — `require` is `php` only |

## Install

```bash
composer require padosoft/laravel-iam-contracts
```

That is the whole installation. There is **no service provider to register, no config to publish, no
migrations** — this package ships only interfaces and value objects. It deliberately drags nothing into
your dependency tree.

## Autoloading

Classes are autoloaded **PSR-4** under the `Padosoft\Iam\Contracts\` namespace, mapped to `src/`:

| Namespace | Contents |
| --- | --- |
| `Padosoft\Iam\Contracts\Support` | `SubjectRef` |
| `Padosoft\Iam\Contracts\Authorization` | `AuthorizationEngine` |
| `Padosoft\Iam\Contracts\Crypto` | `KeyProvider`, `SecretCipher`, `TokenSigner` |
| `Padosoft\Iam\Contracts\Assurance` | `Aal`, `AssuranceProvider`, `StepUpProvider`, `FactorVerifier`, `StepUpPurpose`, `StepUpChallenge`, `StepUpResult` |
| `Padosoft\Iam\Contracts\Governance` | `FeatureScope`, `FeatureKey`, `ScopeLevel`, `FeatureContext` |
| `Padosoft\Iam\Contracts\Identity` | `SessionRegistry`, `SessionRef`, `SessionMeta` |

## Verify it resolved

```bash
composer show padosoft/laravel-iam-contracts
```

```php
use Padosoft\Iam\Contracts\Support\SubjectRef;

echo (string) new SubjectRef('user', '1');   // "user:1"
```

## Where this package fits

You usually do **not** require this package directly in an application — you get it transitively through
[`laravel-iam-server`](https://doc.laravel-iam-server.padosoft.com) or
[`laravel-iam-client`](https://doc.laravel-iam-client.padosoft.com). You require it explicitly when you
**author an implementation** of one of its interfaces, or when you want to **type your own domain** against
its value objects.

::: callout tip "Stability" icon:shield-check
Because every other ecosystem package depends on these symbols, they are versioned conservatively. See
[Versioning & ABI stability](/architecture/versioning) for what counts as a breaking change and how to
pin the package safely.
:::

## Next

- [Quickstart](/quickstart) — implement your first contract.
- [Contract reference](/reference/overview) — every interface and DTO.
