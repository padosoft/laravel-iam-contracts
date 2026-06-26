<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Assurance;

/**
 * Challenge di step-up emessa al subject (doc 10 §4): l'id da rispondere, il metodo richiesto
 * (totp|passkey) e la scadenza.
 */
final readonly class StepUpChallenge
{
    public function __construct(
        public string $id,
        public string $method,
        public \DateTimeImmutable $expiresAt,
    ) {}
}
