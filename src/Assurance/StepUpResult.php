<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Assurance;

/**
 * Esito di una verifica di step-up: se riuscita, l'AAL a cui è stata elevata la sessione.
 */
final readonly class StepUpResult
{
    public function __construct(
        public bool $success,
        public Aal $aal,
    ) {}
}
