<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * Persistenza delle deleghe consentite. Implementato dal modulo
 * padosoft/laravel-iam-agents. Ogni mutazione è auditata (hash-chain,
 * stream=delegation); la revoca è soft-delete con evidenza (chi, quando).
 */
interface DelegationGrantStore
{
    /** La grant attiva user→agent, se esiste ed è usabile ORA. Null ⇒ exchange negato. */
    public function findActive(SubjectRef $user, SubjectRef $agent): ?DelegationGrant;

    /** Lookup puntuale per id (claim `pds_dgr`): usato dal PDP delegato a ogni decisione. */
    public function find(string $grantId): ?DelegationGrant;

    /**
     * Le deleghe dell'utente (self-service "le mie deleghe" e admin).
     *
     * @return iterable<DelegationGrant>
     */
    public function listFor(SubjectRef $user): iterable;

    /** Revoca una grant. Idempotente su grant già revocata. */
    public function revoke(string $grantId, SubjectRef $revokedBy): void;
}
