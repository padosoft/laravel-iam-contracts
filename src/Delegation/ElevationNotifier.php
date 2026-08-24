<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

/**
 * Il canale out-of-band che porta al DELEGANTE una richiesta di JIT elevation
 * (implementazione di riferimento: laravel-rebel-channels — Telegram/WhatsApp/
 * SMS/voice). Il notifier INFORMA soltanto: l'approvazione passa SEMPRE dal
 * flusso di consenso step-up self-service — mai un "approve" dentro il canale
 * senza verifica del fattore.
 *
 * Deve lanciare su fallimento di consegna: il chiamante decide se la richiesta
 * resta comunque visibile in self-service (sì, by design) e audita l'esito.
 */
interface ElevationNotifier
{
    /** @throws \Throwable quando la consegna fallisce su tutti i canali configurati */
    public function notify(ElevationRequest $request): void;
}
