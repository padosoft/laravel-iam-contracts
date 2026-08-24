<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

/**
 * Lifecycle di un agente registrato. Solo `Active` può scambiare token e comparire
 * in una catena `act` valida (fail-closed). `Pending` è lo stato di atterraggio delle
 * registrazioni agentic (DCR gated / auth.md): diventa `Active` SOLO con approvazione
 * umana — mai auto-provisioning. `Retired` è terminale.
 */
enum AgentStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Retired = 'retired';

    public function allowsDelegation(): bool
    {
        return $this === self::Active;
    }
}
