<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

use Padosoft\Iam\Contracts\Support\SubjectRef;

/**
 * L'attore di una delega: l'agente che agisce per conto di un utente. Wrappa un
 * `SubjectRef` di tipo `agent` (il tipo previsto da SubjectRef ma finora mai attivato).
 * Fail-closed: un tipo diverso da `agent` è rifiutato — un attore non-agente non è
 * una delega, è un'altra cosa (impersonation, service account) e NON passa da qui.
 *
 * NB: da non confondere con `$actor` di Governance\FeatureScope, che indica il
 * chiamante corrente. Qui "actor" è nel senso OAuth del claim `act` (RFC 8693 §4.1).
 */
final readonly class ActorRef implements \Stringable
{
    public const string SUBJECT_TYPE = 'agent';

    public function __construct(
        public SubjectRef $subject,
    ) {
        if ($this->subject->type !== self::SUBJECT_TYPE) {
            throw new \InvalidArgumentException(
                "ActorRef richiede un SubjectRef di tipo 'agent', ricevuto '{$this->subject->type}'.",
            );
        }
    }

    public static function fromAgentId(string $agentId): self
    {
        return new self(new SubjectRef(self::SUBJECT_TYPE, $agentId));
    }

    /**
     * Costruisce l'attore dal livello corrente di un claim `act` (`{"sub":"agent:…"}`).
     * Ritorna null se il livello non ha un `sub` valido nel formato `agent:<id>`.
     *
     * @param  array<string, mixed>  $act
     */
    public static function fromActClaim(array $act): ?self
    {
        $sub = $act['sub'] ?? null;
        if (!is_string($sub) || $sub === '') {
            return null;
        }

        $prefix = self::SUBJECT_TYPE.':';
        if (!str_starts_with($sub, $prefix)) {
            return null;
        }

        $id = substr($sub, strlen($prefix));

        return $id === '' ? null : self::fromAgentId($id);
    }

    public function __toString(): string
    {
        return (string) $this->subject;
    }
}
