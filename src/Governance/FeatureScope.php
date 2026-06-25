<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Governance;

use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * Primitiva trasversale che rende ogni feature di governance (Access Review/Request,
 * PIM, SoD, anomaly/least-privilege) accendibile/spegnibile e granulare su 4 livelli
 * a cascata (layer→app→role→user), con default sicuro e gate via permesso.
 *
 * Vedi laravel-iam-docs/14-governance-and-iga.md §1 (ADR-FS-001).
 */
interface FeatureScope
{
    /** La feature è attiva per questo contesto? (cascata layer→app→role→user) */
    public function isEnabled(FeatureContext $ctx): bool;

    /** Il soggetto ha il permesso che fa da gate d'uso della feature? */
    public function isPermitted(FeatureContext $ctx, SubjectRef $actor): bool;

    /** Modalità della feature dove applicabile (es. SoD: 'off'|'detect'|'enforce'). */
    public function mode(FeatureContext $ctx): string;
}
