<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

use Padosoft\Iam\Contracts\Assurance\Aal;
use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * La delega consentita dall'utente: l'agente `agent` può agire per conto di `user`
 * negli `scopes` indicati, per lo scopo dichiarato `purpose`, fino a `expiresAt`.
 * Il consenso è una conferma step-up (AAL2, dynamic-linking sui parametri) citata
 * da `consentConfirmationId`/`consentAal` — evidenza, non decorazione.
 *
 * All'exchange gli scope emessi sono `requested ∩ grant.scopes ∩ agent.maxScopes`;
 * il layer utente resta al PDP per-request (intersection rule). L'id della grant
 * viaggia nel token come claim `pds_dgr` per la revoca mirata.
 */
final readonly class DelegationGrant
{
    /**
     * @param  list<string>  $scopes
     */
    public function __construct(
        public string $id,
        public SubjectRef $user,
        public SubjectRef $agent,
        public array $scopes,
        public string $purpose,
        public DelegationGrantStatus $status,
        public \DateTimeImmutable $expiresAt,
        public \DateTimeImmutable $createdAt,
        public ?string $consentConfirmationId = null,
        public ?Aal $consentAal = null,
        public ?\DateTimeImmutable $revokedAt = null,
        public ?SubjectRef $revokedBy = null,
    ) {}

    /** La grant autorizza la delega ADESSO (stato attivo e non scaduta). Fail-closed. */
    public function isUsableAt(\DateTimeImmutable $now): bool
    {
        return $this->status->allowsDelegation() && $now < $this->expiresAt;
    }
}
