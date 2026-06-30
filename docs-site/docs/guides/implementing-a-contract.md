---
title: "Implementing a contract"
description: "The implementer's guide: pick the interface, honour its invariants and fail-closed defaults, return the documented shapes, and register the implementation in Laravel's container. Worked example: a SQL-ish AuthorizationEngine."
---

# Implementing a contract

This is the guide for the person on the **adapter** side of the hexagon: you are writing a class that
satisfies one of the interfaces in this package so the platform can plug it in. It covers the invariants you
must honour, the fail-closed defaults you inherit, and how to register the result.

## Before you start

::: callout info "Which interface do you need?" icon:help-circle
- A new **PDP backend** (OpenFGA, SpiceDB, your own store) → [`AuthorizationEngine`](/reference/authorization)
- A different **key custodian** (KMS, Vault, HSM) → [`KeyProvider`](/reference/crypto)
- A secret store with **crypto-shredding** → [`SecretCipher`](/reference/crypto)
- A **token signer** (JWT + JWKS) → [`TokenSigner`](/reference/crypto)
- An **assurance / step-up** stack → [`AssuranceProvider`](/reference/assurance), [`StepUpProvider`](/reference/assurance), [`FactorVerifier`](/reference/assurance)
- A **governance feature gate** → [`FeatureScope`](/reference/governance)
- A **session store** → [`SessionRegistry`](/reference/identity)
:::

## The four rules every implementation follows

::: steps
1. **Satisfy the whole interface, exactly.**
   Implement every method with the documented signature. Don't widen returns or narrow parameters — that is
   a contract break (see [Versioning](/architecture/versioning)).

2. **Honour the fail-closed defaults.**
   Where the contract documents a safe default — `SessionRegistry::active()` returns `false` on
   doubt, `AssuranceProvider::currentAal()` returns `AAL1` for an inactive session, `check()` never
   fail-opens — your implementation must return that safe value, never the permissive one. See
   [Fail-closed by design](/concepts/fail-closed).

3. **Return the documented shapes.**
   Array-typed contracts (`check()`, the crypto envelopes) have a documented key set. Produce exactly those
   keys so the rest of the platform — and the wire contract — stays isomorphic.

4. **Keep your dependencies on your side.**
   The contract depends on nothing; your *adapter* may depend on whatever it likes (a DB, an SDK, an HTTP
   client). Never push those dependencies back into code typed against the contract.
:::

## Worked example — an `AuthorizationEngine`

A realistic skeleton: deterministic, deny-overrides, fail-closed, returning the documented decision shape.

```php
use Padosoft\Iam\Contracts\Authorization\AuthorizationEngine;
use Padosoft\Iam\Contracts\Support\SubjectRef;

final class SqlishEngine implements AuthorizationEngine
{
    public function __construct(private MyGrantStore $store) {}

    /**
     * @param  array<string, mixed>  $query   keys: subject, action, resource, context
     * @return array<string, mixed>           keys: decision, reason, (explain, policy_version, requires_step_up)
     */
    public function check(array $query): array
    {
        $grants = $this->store->matching($query);

        // deny-overrides: any explicit deny wins, regardless of allows
        foreach ($grants as $g) {
            if ($g->effect === 'deny') {
                return ['decision' => 'deny', 'reason' => 'explicit_deny', 'policy_version' => $g->version];
            }
        }

        foreach ($grants as $g) {
            if ($g->effect === 'allow') {
                return ['decision' => 'allow', 'reason' => 'grant', 'policy_version' => $g->version];
            }
        }

        // default-deny: no matching grant ⇒ deny (never fail-open)
        return ['decision' => 'deny', 'reason' => 'no_matching_grant'];
    }

    /** @return iterable<SubjectRef> */
    public function listSubjects(string $relation, string $objectType, string $objectId): iterable
    {
        foreach ($this->store->subjectsWith($relation, $objectType, $objectId) as $row) {
            yield new SubjectRef($row->type, $row->id);
        }
    }

    /** @return iterable<array{type: string, id: string}> */
    public function listResources(SubjectRef $subject, string $relation): iterable
    {
        foreach ($this->store->resourcesFor((string) $subject, $relation) as $row) {
            yield ['type' => $row->type, 'id' => $row->id];
        }
    }
}
```

Note the three fail-closed properties: deny-overrides is checked **before** allow; the no-grant branch
returns **deny**; nothing throws an allow.

## Register it in the container

Bind your adapter to the **port** so the core resolves it transparently:

```php
use Padosoft\Iam\Contracts\Authorization\AuthorizationEngine;

$this->app->singleton(AuthorizationEngine::class, fn ($app) =>
    new SqlishEngine($app->make(MyGrantStore::class))
);
```

Anything typed against `AuthorizationEngine` now receives your implementation — no other code changes. (See
[Ports & adapters](/concepts/ports-and-adapters) for the full wiring model.)

## Test against the contract, not the implementation

Write tests that assert the **contract's** guarantees — they then protect any implementation:

```php
it('never fails open on an empty store', function () {
    $engine = new SqlishEngine(new EmptyGrantStore());

    expect($engine->check(['subject' => 'user:42', 'action' => 'read', 'resource' => 'doc:1']))
        ->toMatchArray(['decision' => 'deny']);
});
```

## Gotchas

::: callout warning "Common implementation mistakes" icon:alert-triangle
- **Fail-open on error.** Catching a store exception and returning `allow` defeats the model. On error,
  deny.
- **Returning undocumented keys only.** Consumers read `decision` / `reason`; omit them and you break the
  contract even if your class "works".
- **Leaking your adapter's types.** Returning a `MyGrant` object instead of the documented array re-couples
  the core to your implementation.
- **Mutating a `SubjectRef`.** It is `final readonly` — construct a new one instead.
:::

## Related

- [Consuming contracts](/guides/consuming-contracts) — the other side of the seam.
- [Contract reference](/reference/overview) — exact signatures for every interface.
- [Fail-closed by design](/concepts/fail-closed) — the defaults you must honour.
