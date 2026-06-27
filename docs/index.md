---
title: Home
---

# Laravel IAM — Contracts

`padosoft/laravel-iam-contracts` is the **shared contract layer** of the
[Laravel IAM](https://github.com/padosoft) ecosystem — an Identity & Authorization Control Plane for
Laravel.

It ships **only interfaces and `final readonly` value objects**: no implementations, no Laravel dependency,
**no runtime dependencies at all** (just PHP 8.3+). It is the **dependency root** — every other
`padosoft/laravel-iam-*` package depends on it, and it depends on nothing.

## Why it exists

The ecosystem is split into many packages (server, client, AI, directory, migration bridges). They must
agree on the same vocabulary:

- *What is a subject?* → `SubjectRef`
- *How does the PDP decide allow/deny?* → `AuthorizationEngine`
- *How is a secret encrypted, a token signed?* → `KeyProvider`, `SecretCipher`, `TokenSigner`
- *What is an assurance level, a step-up?* → `Aal`, `StepUpProvider`
- *How is a session tracked and revoked?* → `SessionRegistry`
- *How is a governance feature gated?* → `FeatureScope`

By depending on these abstractions instead of concrete classes, you can swap implementations — a different
PDP engine, a different key custodian, a different passkey verifier — **without changing any consuming
code**.

## Install

```bash
composer require padosoft/laravel-iam-contracts
```

## Next

- [Getting started](getting-started.md) — install and implement your first contract.
- [Concepts](concepts.md) — the mental model behind the contracts.
- [Reference](reference.md) — every interface and DTO, with signatures.
