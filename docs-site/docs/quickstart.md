---
title: "Quickstart — implement your first contract"
description: "Install laravel-iam-contracts and implement a fail-closed AuthorizationEngine, reference a subject with SubjectRef, and compare assurance levels with Aal — in five minutes."
---

# Quickstart

This page takes you from `composer require` to a working, **fail-closed** implementation of the most
important contract — the authorization engine — plus the value objects you will use everywhere.

## 1. Install

```bash
composer require padosoft/laravel-iam-contracts
```

It pulls in **nothing else**: the only requirement is `php: ^8.3`. Everything autoloads under
`Padosoft\Iam\Contracts\`.

::: callout info "Do I even need to require this directly?" icon:help-circle
If you are simply *using* the IAM server or client, you get these contracts **transitively** and rarely
require the package by hand. You require it directly when you are **building an implementation** (an
engine, a key provider, a session registry) or **typing your own domain** against ecosystem value
objects like `SubjectRef`.
:::

## 2. Reference a subject

`SubjectRef` is the `type:id` value object used across the whole ecosystem to reference users, groups,
service accounts, external groups and agents. It is `final readonly` and `Stringable`.

```php
use Padosoft\Iam\Contracts\Support\SubjectRef;

$subject = new SubjectRef(type: 'user', id: '42');

(string) $subject;   // "user:42" — safe to log, store, and key on
```

## 3. Implement the authorization engine

The PDP is pluggable: provide an `AuthorizationEngine` and the platform routes every decision through it.
Here is the safest possible engine — it denies everything — which also shows the exact contract you must
satisfy.

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
        // Default-deny, deny-overrides, never fail-open.
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

A real engine (the native SQL one lives in `laravel-iam-server`) resolves RBAC + ABAC + ReBAC grants with
a **deny-overrides** algorithm and returns an *explain* trace — but the contract it satisfies is exactly
the three methods above. See [Implementing a contract](/guides/implementing-a-contract) for the full
walkthrough.

## 4. Compare assurance levels

`Aal` is the NIST 800-63B Authenticator Assurance Level enum. Its helpers make step-up decisions a
one-liner — and they are **fail-safe** by construction.

```php
use Padosoft\Iam\Contracts\Assurance\Aal;

Aal::AAL2->satisfies(Aal::AAL1);   // true  — MFA satisfies a single-factor requirement
Aal::AAL1->satisfies(Aal::AAL2);   // false — step-up required
Aal::fromString(null);             // Aal::AAL1 — unknown ⇒ weakest level (fail-safe)
```

## 5. Gate a governance feature

`FeatureScope` turns every governance feature on/off, cascading across four levels (layer → app → role →
user), with a permission gate.

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

## Where to next

::: grids
  ::: grid
    ::: card "Implementing a contract" icon:wrench
    The full implementer's guide: invariants, fail-closed defaults, registration. **[Open →](/guides/implementing-a-contract)**
    :::
  :::
  ::: grid
    ::: card "Why contracts?" icon:lightbulb
    The design argument behind a contracts-only package. **[Read →](/concepts/why-contracts)**
    :::
  :::
  ::: grid
    ::: card "Contract reference" icon:book-marked
    Every interface and DTO with exact signatures. **[Browse →](/reference/overview)**
    :::
  :::
:::
