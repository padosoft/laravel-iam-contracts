<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

/**
 * Il budget consentito su una delega: gli SCOPE limitano l'autorità (cosa l'agente
 * può fare), il BUDGET limita l'intensità (quanto può farlo). Ogni cap è opzionale
 * ma un budget senza alcun cap non è esprimibile: consenso vuoto = nessun vincolo
 * = nessun oggetto budget (null sulla grant).
 *
 * Fa parte del consenso: quando presente, entra nel binding dynamic-linking della
 * conferma step-up esattamente come agent/scopes/ttl/purpose — cambiare il budget
 * dopo la schermata invalida la conferma.
 */
final readonly class DelegationBudget
{
    public function __construct(
        public ?float $amount = null,      // spesa massima nella currency indicata
        public string $currency = 'EUR',
        public ?int $tokens = null,        // token LLM massimi
        public ?int $calls = null,         // chiamate/tool-call massime
    ) {
        if ($amount === null && $tokens === null && $calls === null) {
            throw new \InvalidArgumentException('Un DelegationBudget senza alcun cap non è esprimibile: usa null sulla grant.');
        }
        if (($amount !== null && $amount <= 0) || ($tokens !== null && $tokens <= 0) || ($calls !== null && $calls <= 0)) {
            throw new \InvalidArgumentException('I cap di un DelegationBudget devono essere positivi.');
        }
    }

    /** @return array<string, float|int|string> rappresentazione canonica (chiavi ordinate, solo cap presenti) */
    public function toArray(): array
    {
        $out = [];
        if ($this->amount !== null) {
            $out['amount'] = $this->amount;
            $out['currency'] = $this->currency;
        }
        if ($this->calls !== null) {
            $out['calls'] = $this->calls;
        }
        if ($this->tokens !== null) {
            $out['tokens'] = $this->tokens;
        }

        return $out;
    }

    /** @param  array<array-key, mixed>  $data */
    public static function fromArray(array $data): self
    {
        $currency = $data['currency'] ?? 'EUR';

        return new self(
            amount: is_numeric($data['amount'] ?? null) ? (float) $data['amount'] : null,
            currency: is_string($currency) && $currency !== '' ? $currency : 'EUR',
            tokens: is_numeric($data['tokens'] ?? null) ? (int) $data['tokens'] : null,
            calls: is_numeric($data['calls'] ?? null) ? (int) $data['calls'] : null,
        );
    }
}
