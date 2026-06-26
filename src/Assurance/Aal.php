<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Assurance;

/**
 * Authenticator Assurance Level (NIST 800-63B, doc 10 §4):
 *  - AAL1: fattore singolo (password)
 *  - AAL2: MFA / passkey (WebAuthn user-verifying)
 *  - AAL3: autenticatore hardware/cryptographic (FIDO2 resident key, PSD2)
 */
enum Aal: string
{
    case AAL1 = 'aal1';
    case AAL2 = 'aal2';
    case AAL3 = 'aal3';

    /** Ordine di forza per i confronti di step-up (aal2 soddisfa una richiesta aal1). */
    public function rank(): int
    {
        return match ($this) {
            self::AAL1 => 1,
            self::AAL2 => 2,
            self::AAL3 => 3,
        };
    }

    public function satisfies(self $required): bool
    {
        return $this->rank() >= $required->rank();
    }

    public static function fromString(?string $value): self
    {
        return self::tryFrom($value ?? '') ?? self::AAL1;
    }
}
