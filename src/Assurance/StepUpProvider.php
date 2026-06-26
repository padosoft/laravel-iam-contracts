<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Assurance;

use Padosoft\Iam\Contracts\Identity\SessionRef;
use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * Step-up dell'assurance su azione critica (doc 10 §4, doc 14): il PDP chiede `requires_step_up`,
 * lo StepUpProvider emette una challenge; alla verifica eleva l'AAL della sessione + step_up_at.
 */
interface StepUpProvider
{
    public function require(SubjectRef $subject, StepUpPurpose $purpose, SessionRef $session): StepUpChallenge;

    /**
     * Verifica la risposta alla challenge; se valida, eleva la sessione all'AAL richiesto.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verify(string $challengeId, array $payload): StepUpResult;
}
