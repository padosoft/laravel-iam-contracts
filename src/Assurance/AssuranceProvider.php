<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Assurance;

use Padosoft\Iam\Contracts\Identity\SessionRef;
use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * Fornisce il livello di assurance corrente di una sessione (doc 10 §4). Native = AAL dalla
 * sessione; un adapter (Rebel) può calcolare trust scoring più ricco.
 */
interface AssuranceProvider
{
    /** AAL corrente del subject sulla sessione data (AAL1 se la sessione non è attiva). */
    public function currentAal(SubjectRef $subject, SessionRef $session): Aal;

    /** True se questo provider è in grado di portare il subject all'AAL richiesto. */
    public function supports(Aal $target): bool;
}
