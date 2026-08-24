<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * Registry degli agenti come identità di prima classe. Implementato dal modulo
 * padosoft/laravel-iam-agents; consumato dal grant Token Exchange e dal PDP delegato.
 * `null` per un agente ignoto ⇒ deny a valle (fail-closed).
 */
interface AgentRegistry
{
    public function find(SubjectRef $agent): ?AgentDescriptor;
}
