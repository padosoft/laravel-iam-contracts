<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * Descrizione di un agente registrato nel registry. L'identità agentica ha TRE
 * soggetti, non due: `operator` (il provider che gestisce l'agente: "openai",
 * "anthropic", interno…), l'istanza agente (`subject`, tipo `agent`) e l'utente
 * delegante (nella grant). `maxScopes` è il tetto derivato dal manifest
 * dell'applicazione (`applicationId`): riducibile dall'admin, mai superabile.
 * `owner` è l'àncora di accountability: il retire dell'owner sospende l'agente.
 */
final readonly class AgentDescriptor
{
    /**
     * @param  list<string>  $maxScopes
     */
    public function __construct(
        public SubjectRef $subject,
        public AgentStatus $status,
        public array $maxScopes,
        public ?string $operator = null,
        public ?SubjectRef $owner = null,
        public ?string $applicationId = null,
    ) {}
}
