<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Governance;

use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * Contesto di valutazione di una FeatureScope (cascata layer→app→role→user).
 * Vedi laravel-iam-docs/14-governance-and-iga.md §1.
 */
final readonly class FeatureContext
{
    public function __construct(
        public FeatureKey $feature,
        public ?string $organizationId = null,
        public ?string $applicationKey = null,
        public ?string $roleKey = null,
        public ?SubjectRef $subject = null,
    ) {}
}
