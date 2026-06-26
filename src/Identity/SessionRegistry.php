<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Identity;

use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * Registry server-side delle sessioni (doc 10 §3/§4). Ogni sessione è revocabile e legata ai
 * token via `sid`. Idle timeout + absolute timeout; l'absolute timeout non è mai esteso.
 */
interface SessionRegistry
{
    /** Apre una sessione e ritorna il riferimento (sid). */
    public function start(SubjectRef $subject, SessionMeta $meta): SessionRef;

    /** Aggiorna last_activity_at (idle timeout). No-op se la sessione non è più attiva. */
    public function touch(SessionRef $session): void;

    /** True se la sessione esiste, non è revocata e non è scaduta (idle/absolute). Fail-closed. */
    public function active(string $sessionId): bool;

    public function revokeSession(string $sessionId, string $reason): void;

    public function revokeAllForSubject(SubjectRef $subject, string $reason): void;

    /**
     * Sessioni attive del soggetto (device management).
     *
     * @return iterable<int, SessionRef>
     */
    public function listForSubject(SubjectRef $subject): iterable;
}
