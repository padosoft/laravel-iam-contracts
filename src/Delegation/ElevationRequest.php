<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * Una richiesta di JIT scope elevation: l'agente ha incontrato un'azione FUORI
 * dalla grant e — invece del flat deny — chiede al delegante di estendere il
 * consenso agli scope indicati. La richiesta è pending fino a `expiresAt` (poi
 * scade da sola, fail-closed); l'approvazione è un NUOVO consenso step-up con
 * dynamic linking sugli scope aggiuntivi — mai un'auto-estensione.
 *
 * Questo VO è ciò che viaggia verso l'ElevationNotifier (il canale out-of-band):
 * contiene solo ciò che serve a informare il delegante, nessun token, nessun
 * segreto.
 */
final readonly class ElevationRequest
{
    /** @param  list<string>  $requestedScopes */
    public function __construct(
        public string $id,
        public string $grantId,
        public SubjectRef $user,
        public SubjectRef $agent,
        public string $agentName,
        public array $requestedScopes,
        public string $reason,
        public \DateTimeImmutable $expiresAt,
    ) {
        if ($requestedScopes === []) {
            throw new \InvalidArgumentException('Una elevation senza scope richiesti non è esprimibile.');
        }
        if ($reason === '') {
            throw new \InvalidArgumentException('Una elevation richiede sempre la reason (il delegante deve capire cosa sta approvando).');
        }
    }
}
