<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Assurance;

/**
 * Motivo di uno step-up (doc 10 §4): l'azione critica da autorizzare e l'AAL richiesto dal PDP.
 */
final readonly class StepUpPurpose
{
    public function __construct(
        public string $action,
        public Aal $requiredAal = Aal::AAL2,
    ) {}
}
