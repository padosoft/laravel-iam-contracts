<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * Contesto di delega cross-cutting per osservabilità e audit: chi (`sub`), per mezzo
 * di chi (`chain`), con quale grant (`grantId`), correlato come (`correlationId`).
 * Idratato una volta (middleware HTTP / job middleware) e propagato via Laravel
 * Context, così OGNI log e audit record di qualunque package risponde a
 * "chi ha fatto cosa, per conto di chi" senza conoscere la delega.
 *
 * Privacy: `sub` è un identificatore utente — nei log a lunga retention fuori da IAM
 * va pseudonimizzato (hash) dal chiamante; in chiaro solo nell'audit IAM
 * (crypto-shredding GDPR). La catena `act` (agenti) non è PII.
 */
final readonly class DelegationContext
{
    /**
     * @param  list<string>  $scopes
     */
    public function __construct(
        public SubjectRef $sub,
        public DelegationChain $chain,
        public ?string $grantId = null,
        public ?string $decisionId = null,
        public ?string $correlationId = null,
        public ?string $audience = null,
        public array $scopes = [],
    ) {}

    /**
     * Shape piatta per log/audit context. Chiavi stabili: fanno parte del contratto
     * di osservabilità (query pivot per actor/sub/grant nei pannelli admin).
     * NB: nessuna chiave contiene la substring `token` (regole di redaction).
     *
     * @return array{
     *     delegation_sub: string,
     *     delegation_actor: string,
     *     delegation_chain: list<string>,
     *     delegation_depth: int,
     *     delegation_grant_id: ?string,
     *     delegation_decision_id: ?string,
     *     correlation_id: ?string,
     * }
     */
    public function toLogContext(): array
    {
        return [
            'delegation_sub' => (string) $this->sub,
            'delegation_actor' => (string) $this->chain->current(),
            'delegation_chain' => array_map(strval(...), $this->chain->actors),
            'delegation_depth' => $this->chain->depth(),
            'delegation_grant_id' => $this->grantId,
            'delegation_decision_id' => $this->decisionId,
            'correlation_id' => $this->correlationId,
        ];
    }
}
