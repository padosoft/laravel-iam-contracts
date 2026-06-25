<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Governance;

/**
 * Livelli a cascata su cui una feature di governance è attivabile/scopabile.
 * Risoluzione: user > role > app > layer (il più specifico esplicito vince).
 * Vedi laravel-iam-docs/14-governance-and-iga.md §1.
 */
enum ScopeLevel: string
{
    case Layer = 'layer';
    case App = 'app';
    case Role = 'role';
    case User = 'user';
}
