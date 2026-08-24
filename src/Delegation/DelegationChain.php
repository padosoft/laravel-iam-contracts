<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

/**
 * Catena ordinata di attori di una delega: l'attore corrente per primo, poi gli hop
 * precedenti verso l'originale (RFC 8693 §4.1: `act` annidato). Depth 1 in MVP
 * (`max_delegation_depth`), ma il VO rappresenta N hop per il multi-hop di v2.
 *
 * Fail-closed a carico del consumer: un token CON claim `act` non deve MAI essere
 * valutato come single-subject — la catena va passata al DelegatedAuthorizationEngine.
 */
final readonly class DelegationChain
{
    /** @var non-empty-list<ActorRef> */
    public array $actors;

    public function __construct(ActorRef ...$actors)
    {
        if ($actors === []) {
            throw new \InvalidArgumentException('DelegationChain richiede almeno un attore.');
        }

        $this->actors = array_values($actors);
    }

    /** L'attore corrente (il piu' esterno, chi sta eseguendo la chiamata). */
    public function current(): ActorRef
    {
        return $this->actors[0];
    }

    public function depth(): int
    {
        return count($this->actors);
    }

    /**
     * Serializza la catena nel claim `act` annidato (RFC 8693 §4.1):
     * `{"sub":"agent:B","act":{"sub":"agent:A"}}` — attore corrente all'esterno.
     *
     * @return array<string, mixed>
     */
    public function toActClaim(): array
    {
        $claim = null;
        foreach (array_reverse($this->actors) as $actor) {
            $level = ['sub' => (string) $actor];
            if ($claim !== null) {
                $level[ActClaim::ACT] = $claim;
            }
            $claim = $level;
        }

        return $claim;
    }

    /**
     * Estrae la catena dai claims di un token. Ritorna null se il claim `act` è assente.
     * Un claim `act` presente ma malformato (livello senza `sub agent:…` valido) lancia:
     * fail-closed — un token delegato illeggibile non degrada a token utente pieno.
     *
     * @param  array<string, mixed>  $claims
     */
    public static function fromTokenClaims(array $claims): ?self
    {
        $act = $claims[ActClaim::ACT] ?? null;
        if ($act === null) {
            return null;
        }

        if (!is_array($act)) {
            throw new \InvalidArgumentException('Claim `act` malformato: atteso oggetto.');
        }

        $actors = [];
        $level = $act;
        while (true) {
            /** @var array<string, mixed> $level */
            $actor = ActorRef::fromActClaim($level);
            if ($actor === null) {
                throw new \InvalidArgumentException('Claim `act` malformato: livello senza `sub` agent valido.');
            }
            $actors[] = $actor;

            $next = $level[ActClaim::ACT] ?? null;
            if ($next === null) {
                break;
            }
            if (!is_array($next)) {
                throw new \InvalidArgumentException('Claim `act` malformato: livello annidato non oggetto.');
            }
            $level = $next;
        }

        return new self(...$actors);
    }
}
