<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

/**
 * Verdetto del meter sui budget di delega. `remaining` è informativo (per UI/log,
 * es. {"amount": 12.5, "tokens": 40000, "calls": 12}); l'unica cosa che autorizza
 * è `allowed`. Un verdetto negato porta SEMPRE la reason (auditata, mai mostrata
 * grezza all'agente).
 */
final readonly class BudgetVerdict
{
    /** @param  array<string, float|int>  $remaining */
    private function __construct(
        public bool $allowed,
        public string $reason,
        public array $remaining = [],
    ) {}

    /** @param  array<string, float|int>  $remaining */
    public static function allow(array $remaining = []): self
    {
        return new self(true, '', $remaining);
    }

    /** @param  array<string, float|int>  $remaining */
    public static function deny(string $reason, array $remaining = []): self
    {
        if ($reason === '') {
            throw new \InvalidArgumentException('Un deny di budget richiede sempre la reason.');
        }

        return new self(false, $reason, $remaining);
    }
}
