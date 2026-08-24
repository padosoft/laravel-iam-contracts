<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * Transizioni di lifecycle di un agente invocabili da componenti di sicurezza
 * (implementazione: il registry di laravel-iam-agents; consumer di riferimento:
 * laravel-rebel-ai-guard, che sospende un agente su anomalia dello stream
 * delegation quando l'auto-suspend è abilitato).
 *
 * SOLO suspend: la ri-attivazione è una decisione umana (console/Admin API),
 * mai di un detector. Idempotente: sospendere un agente già sospeso non è un
 * errore. `actor` identifica CHI sospende (es. "rebel-ai-guard") e finisce
 * nell'audit; `reason` è il perché, human-readable.
 *
 * Interfaccia NUOVA e separata da AgentRegistry (add, don't mutate): i PEP che
 * devono solo LEGGERE gli agenti non ricevono mai la capability di sospenderli.
 */
interface AgentLifecycle
{
    public function suspend(SubjectRef $agent, string $reason, string $actor): void;
}
