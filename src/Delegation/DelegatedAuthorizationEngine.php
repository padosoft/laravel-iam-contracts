<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

use Padosoft\Iam\Contracts\Authorization\AuthorizationEngine;
use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * PDP consapevole della delega: valuta l'INTERSEZIONE STRETTA — mai l'unione —
 * tra ciò che può fare l'utente e ciò che può fare l'agente (intersection rule).
 *
 * Interfaccia NUOVA che estende AuthorizationEngine ("add, don't mutate": mai
 * metodi aggiunti a interfacce esistenti). Implementata in -server come decorator
 * dell'engine nativo: due passi `check()` (utente, agente) + grant `pds_dgr`
 * ancora attiva. Deny-overrides composto: un deny esplicito su QUALUNQUE layer
 * nega; assenza di allow su un layer nega (fail-closed). Agente ignoto, pending,
 * sospeso o retired ⇒ deny con reason `agent_not_active`.
 *
 * La Decision cita ENTRAMBI i soggetti (decision id doppio livello): l'auditor
 * rigioca separatamente perché il lato utente e il lato agente hanno permesso.
 */
interface DelegatedAuthorizationEngine extends AuthorizationEngine
{
    /**
     * Decisione delegata sull'intersezione utente ∩ agente (∩ ogni hop della catena).
     *
     * @param  array<string, mixed>  $query  stessa shape di check() (TODO(M2): DecisionQuery)
     * @return array<string, mixed> shape di check() + `actors`, `sub_decisions` (TODO(M2): Decision)
     */
    public function checkDelegated(SubjectRef $subject, DelegationChain $chain, array $query): array;
}
