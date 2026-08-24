<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

/**
 * Stato di una DelegationGrant. Solo `Active` autorizza exchange e decisioni delegate;
 * ogni altro stato è un deny (fail-closed). `Revoked` è terminale.
 */
enum DelegationGrantStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Revoked = 'revoked';

    public function allowsDelegation(): bool
    {
        return $this === self::Active;
    }
}
