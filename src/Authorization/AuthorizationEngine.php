<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Authorization;

use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * Motore di autorizzazione pluggable dietro il PDP. Il motore nativo (NativeSqlEngine)
 * copre RBAC+ABAC+ReBAC su SQL; per scala Zanzibar si affianca OpenFGA/SpiceDB senza
 * cambiare il PDP.
 *
 * Vedi laravel-iam-docs/09-authorization-and-pdp.md §4 (combining), §9 (schema), §10 (engine).
 *
 * NOTE(M2): `array` per query/decision diventano value object (DecisionQuery, Decision)
 * con l'algoritmo deny-overrides e il formato explain di §4/§8.
 */
interface AuthorizationEngine
{
    /**
     * Decisione deterministica allow/deny (+ explain, step-up, policy_version).
     *
     * @param  array<string, mixed>  $query  TODO(M2): DecisionQuery
     * @return array<string, mixed>          TODO(M2): Decision
     */
    public function check(array $query): array;

    /**
     * Chi ha `relation` su `object` (reverse-index) → list-subjects.
     *
     * @return iterable<SubjectRef>
     */
    public function listSubjects(string $relation, string $objectType, string $objectId): iterable;

    /**
     * Su cosa `subject` ha `relation` → list-resources.
     *
     * @return iterable<array{type: string, id: string}>
     */
    public function listResources(SubjectRef $subject, string $relation): iterable;
}
