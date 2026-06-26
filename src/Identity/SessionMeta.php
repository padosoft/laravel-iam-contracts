<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Identity;

use Padosoft\Iam\Contracts\Assurance\Aal;

/**
 * Metadati con cui si apre una sessione (doc 10 §3). Gli identificatori di device/IP/UA sono
 * già hashati dal chiamante (privacy by design, doc 12). I timeout sono in secondi; l'absolute
 * timeout è il tetto massimo NON estendibile.
 */
final readonly class SessionMeta
{
    public function __construct(
        public Aal $aal = Aal::AAL1,
        public ?string $organizationId = null,
        public ?string $deviceFingerprintHash = null,
        public ?string $ipHash = null,
        public ?string $userAgentHash = null,
        public int $idleTimeout = 1800,
        public int $absoluteTimeout = 43200,
    ) {}
}
