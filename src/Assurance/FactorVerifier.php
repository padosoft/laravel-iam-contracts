<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Assurance;

use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * Verifica un fattore di autenticazione (TOTP, passkey/WebAuthn) per il subject. È il punto
 * di innesto verso Fortify/laravel-passkeys (cablato in M5.4) o un adapter (Rebel/SCA).
 * In M5.2 lo StepUpProvider lo usa come dipendenza pluggable.
 */
interface FactorVerifier
{
    /** @param  array<string, mixed>  $payload */
    public function verify(SubjectRef $subject, array $payload): bool;
}
