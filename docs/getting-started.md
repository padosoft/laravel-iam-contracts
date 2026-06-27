---
title: Getting started
description: Install laravel-iam-contracts and implement your first contract (engine, subject, assurance, feature scope).
---

# Getting started

## Requirements

- PHP **8.3+**
- A Composer project (no Laravel required — this package is framework-agnostic)

## Install

```bash
composer require padosoft/laravel-iam-contracts
```

This pulls in **nothing else**: the only requirement is `php: ^8.3`. Autoloading is PSR-4 under
`Padosoft\Iam\Contracts\`.

## When do you depend on this directly?

- You are **building an implementation** — an authorization engine, a key provider, a session registry —
  that the Laravel IAM server (or your own app) will plug in.
- You are **typing your own domain** against ecosystem value objects like `SubjectRef`, instead of passing
  raw strings around.

If you are simply *using* the IAM server or client, you get these contracts transitively — you rarely need
to require this package by hand.

## Your first implementation

Every contract is a small, single-purpose interface. Here is a minimal — deliberately fail-closed —
authorization engine:

```php
use Padosoft\Iam\Contracts\Authorization\AuthorizationEngine;
use Padosoft\Iam\Contracts\Support\SubjectRef;

final class DenyAllEngine implements AuthorizationEngine
{
    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function check(array $query): array
    {
        // Default-deny: the safest possible engine.
        return ['decision' => 'deny', 'reason' => 'deny_all_engine'];
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

A real engine (the native SQL one lives in `laravel-iam-server`) would resolve grants with a
**deny-overrides** algorithm and return an *explain* trace — but the contract it satisfies is exactly this.

## Working with value objects

Value objects are `final readonly` — construct them, read them, stringify them, never mutate them:

```php
use Padosoft\Iam\Contracts\Support\SubjectRef;
use Padosoft\Iam\Contracts\Assurance\Aal;

$ref = new SubjectRef('service_account', 'billing-cron');
(string) $ref;                     // "service_account:billing-cron"

Aal::AAL2->satisfies(Aal::AAL1);   // true
```

## Next

Read [Concepts](concepts.md) to understand *why* the contracts are shaped this way, then the full
[Reference](reference.md).
