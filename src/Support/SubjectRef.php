<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Support;

/**
 * Riferimento a un soggetto del PDP: user | group | service_account | external_group | agent.
 * Vedi laravel-iam-docs/09-authorization-and-pdp.md §3.
 */
final readonly class SubjectRef implements \Stringable
{
    public function __construct(
        public string $type,
        public string $id,
    ) {}

    public function __toString(): string
    {
        return "{$this->type}:{$this->id}";
    }
}
