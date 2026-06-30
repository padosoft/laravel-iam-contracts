---
title: "Consuming contracts"
description: "The consumer's guide: type your domain against SubjectRef, Aal and FeatureScope instead of raw strings, depend on interfaces via the container, and keep your code swap-agnostic. For app, module and SDK authors."
---

# Consuming contracts

This guide is for the **caller** side of the seam: you are writing application code, a module, or an SDK
that *uses* Laravel IAM. You rarely implement these interfaces — you depend on them so your code stays typed,
safe and swap-agnostic.

## When you consume rather than implement

- You are building an **app** behind `laravel-iam-client` and want to type incoming claims and decisions.
- You are writing a **module** (`-ai`, `-directory`, a bridge) that references subjects and gates features.
- You are typing your **own domain** against ecosystem value objects instead of passing strings around.

::: callout info "You usually get the contracts transitively" icon:info
Requiring `laravel-iam-server` or `laravel-iam-client` brings these contracts in for you. Require
`padosoft/laravel-iam-contracts` directly only when you want to reference the symbols in your own typed
signatures.
:::

## Rule 1 — type against the value object, not the string

`SubjectRef` exists so you stop passing `"user:42"` strings around. Accept the object; stringify only at the
very edge (a log line, a cache key).

```php
use Padosoft\Iam\Contracts\Support\SubjectRef;

// Good: typed, can't be mistyped, self-documenting.
function recordAccess(SubjectRef $actor, string $resource): void
{
    Log::info('access', ['actor' => (string) $actor, 'resource' => $resource]);
}

recordAccess(new SubjectRef('user', '42'), 'doc:1');
```

```php
// Avoid: stringly-typed — loses every guarantee the value object gives you.
function recordAccess(string $actor, string $resource): void { /* ... */ }
```

## Rule 2 — depend on the interface, resolve from the container

Ask for the **port**; the server (or your binding) supplies the adapter. Your code never names a concrete
class, so swapping the engine/key-provider/registry never touches you.

```php
use Padosoft\Iam\Contracts\Authorization\AuthorizationEngine;

final class ReportPolicy
{
    public function __construct(private AuthorizationEngine $engine) {}

    public function canExport(SubjectRef $actor, string $reportId): bool
    {
        $decision = $this->engine->check([
            'subject'  => (string) $actor,
            'action'   => 'export',
            'resource' => "report:{$reportId}",
        ]);

        // Fail-closed read: treat anything that isn't an explicit allow as deny.
        return ($decision['decision'] ?? 'deny') === 'allow';
    }
}
```

::: callout tip "Read decisions fail-closed too" icon:shield-check
Default the decision to `deny` when the key is missing: `($decision['decision'] ?? 'deny')`. A malformed or
partial response then denies instead of throwing or accidentally allowing. This mirrors the
[fail-closed contract](/concepts/fail-closed) on the producer side.
:::

## Rule 3 — use the enum helpers for assurance

Don't compare AAL strings by hand — the enum already encodes the ordering and the fail-safe default.

```php
use Padosoft\Iam\Contracts\Assurance\Aal;

$current = Aal::fromString($claims['aal'] ?? null);   // unknown ⇒ AAL1

if (! $current->satisfies(Aal::AAL2)) {
    // trigger step-up — MFA required for this action
}
```

## Rule 4 — gate features through `FeatureScope`

Let the cascade resolver decide; you just ask the two questions.

```php
use Padosoft\Iam\Contracts\Governance\FeatureScope;
use Padosoft\Iam\Contracts\Governance\FeatureContext;
use Padosoft\Iam\Contracts\Governance\FeatureKey;

function maybePim(FeatureScope $scope, SubjectRef $actor, string $org): void
{
    $ctx = new FeatureContext(feature: FeatureKey::Pim, organizationId: $org);

    if ($scope->isEnabled($ctx) && $scope->isPermitted($ctx, $actor)) {
        // PIM is on for this org AND the actor may use it
    }
}
```

## For SDK authors (Node / React Native / Rust)

You can't depend on a PHP package — so you mirror the **wire contract** instead of these types:

```text
POST {base}/api/iam/v1/decisions/check         → { "data": { "decision": "...", ... } }
POST {base}/api/iam/v1/decisions/list-resources
```

Mirror the `data` envelope shape, keep your client **thin and fail-closed** (any transport/parse error ⇒
deny), and track the `v1` path as your stability boundary. See
[Ecosystem & dependencies](/architecture/overview#the-wire-contract-for-the-sdks).

## Gotchas

::: callout warning "Consumer pitfalls" icon:alert-triangle
- **Re-stringifying subjects.** Once you take `(string) $ref`, you've lost the type. Pass the `SubjectRef`
  through and stringify only at the boundary.
- **Naming a concrete class.** Type-hinting `NativeSqlEngine` instead of `AuthorizationEngine` re-couples
  you to one adapter and breaks swap-ability.
- **Truthy decision reads.** `if ($decision['decision'])` treats `"deny"` as truthy. Compare to `'allow'`
  explicitly and default missing keys to deny.
:::

## Related

- [Implementing a contract](/guides/implementing-a-contract) — the producer side.
- [Contract reference](/reference/overview) — every symbol you can type against.
- [Fail-closed by design](/concepts/fail-closed) — why you default to deny on read.
