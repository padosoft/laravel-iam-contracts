<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Identity;

/**
 * Riferimento a una sessione server-side (doc 10 §3). L'`id` è il `sid` che lega i token
 * alla sessione, così la revoca è possibile prima della scadenza del token.
 */
final readonly class SessionRef implements \Stringable
{
    public function __construct(public string $id) {}

    public function __toString(): string
    {
        return $this->id;
    }
}
