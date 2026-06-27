# CLAUDE.md — laravel-iam-contracts

Guida per agenti AI che lavorano in questo repo (package dell'ecosistema **Laravel IAM**). Prima di
qualsiasi lavoro leggi `LESSON.md`, `RULES.md` e questa pagina. Skill: `laravel-iam-package-workflow`.

## Cos'è questo package

Contratti (interfacce + DTO) condivisi dell'ecosistema Laravel IAM: la **radice delle dipendenze**.

- **Composer:** `padosoft/laravel-iam-contracts`
- **Namespace:** `Padosoft\Iam\Contracts\`
- **Ruolo nell'ecosistema:** è il **dependency root** — zero dipendenze interne (solo PHP). Definisce le
  interfacce stabili e i value object che ogni altro package `padosoft/laravel-iam-*` implementa o consuma.
  Dipendi dalle astrazioni qui, non dalle implementazioni: si può sostituire il motore PDP
  (NativeSqlEngine → OpenFGA/SpiceDB) senza toccare i consumer.
- **Dipende da:** niente di interno. Solo `php: ^8.3`.

## Architettura del package

Solo interfacce ed enum/DTO `final readonly` — **nessuna implementazione, nessuna dipendenza Laravel**.
Sottocartelle di `src/` (namespace `Padosoft\Iam\Contracts\…`):

- **`Authorization/`** — `AuthorizationEngine`: il contratto del PDP pluggable. `check()` (decisione
  deterministica allow/deny + explain), `listSubjects()` (reverse-index: chi ha `relation` su un oggetto),
  `listResources()` (su cosa un subject ha `relation`). Il motore nativo SQL sta in `-server`; per scala
  Zanzibar si affianca OpenFGA/SpiceDB **senza cambiare il PDP**.
- **`Crypto/`** — `KeyProvider` (envelope encryption: wrap/unwrap/generate DEK), `SecretCipher`
  (encrypt/decrypt/`shred` segreti con `scope` per crypto-shredding GDPR), `TokenSigner` (JWT ES256 +
  JWKS + `rotate`).
- **`Assurance/`** — `AssuranceProvider`, `StepUpProvider`, `FactorVerifier` (interfacce) + `Aal` (enum
  NIST 800-63B con `rank()`/`satisfies()`), `StepUpPurpose`/`StepUpChallenge`/`StepUpResult`/`StepUpPurpose`
  (DTO). Step-up dell'assurance su azione critica.
- **`Governance/`** — `FeatureScope` (primitiva on/off + cascata a 4 livelli per le feature IGA) + `FeatureKey`
  / `ScopeLevel` (enum) + `FeatureContext` (DTO).
- **`Identity/`** — `SessionRegistry` (sessioni server-side revocabili, idle+absolute timeout, fail-closed) +
  `SessionRef`/`SessionMeta` (DTO).
- **`Support/`** — `SubjectRef`: il value object `type:id` (`final readonly implements Stringable`) usato in
  tutto l'ecosistema per riferirsi a user/group/service_account/external_group/agent.

I docblock citano i doc di design `laravel-iam-docs/` (09 PDP, 10 identity/session, 11 crypto, 14 governance).

## Invarianti (NON violare)
1. **Mai bypassare il PDP.** L'AI propone draft/spiegazioni; il PDP deterministico decide allow/deny.
2. **Fail-closed** sull'autorizzazione; mai fail-open su operazioni critiche.
3. **Niente segreti/OTP/PII nei log.** Segreti cifrati via envelope encryption.
4. **Audit per ogni mutazione** (hash-chain).
5. **Slug permessi/ruoli immutabili** (`app_key:permission`).
6. **Scope/condition dichiarati dalle app** nel manifest, mai hardcoded nel core.
7. **Nessuna UI legge il DB**: solo Admin API.
8. **OIDC layer**: base MIT (steverhoades). **Vietato** codice AGPL (limosa-io). OAuth = league/oauth2-server.

### Specifiche di questo package
- **Una modifica a un contratto è una breaking change** per tutto l'ecosistema. Versiona con cura
  (semver): aggiungere un metodo a un'interfaccia rompe ogni implementatore. Preferisci nuove interfacce
  a modifiche di quelle esistenti.
- **Dependency-light per principio**: `require` deve restare `php` soltanto. Niente `illuminate/*`,
  niente runtime deps. Se serve un tipo Laravel (es. `Carbon`), usa `\DateTimeImmutable` della PHP stdlib.
- **DTO `final readonly`, interfacce piccole e coese**. Gli `array<string,mixed>` con shape annotata
  (`@return array{...}`) sono i precursori dei value object di M2+ (DecisionQuery/Decision): la shape è
  parte del contratto.

## Convenzioni codice
- `declare(strict_types=1)`, classi `final` di default.
- Namespace radice **`Padosoft\Iam\`** (PSR-4).
- **PHPStan max**, **Pest**, **Pint**. Test negativi obbligatori (denial, tenant isolation, fail-closed)
  dove applicabili (qui per lo più contract tests / shape tests).

## Gate (in locale, con PHP 8.5 Herd)
```bash
# in un progetto root con questo package installato via path/VCS + le sue dev-deps
php vendor/bin/pint
php vendor/bin/phpstan analyse --memory-limit=1G
php vendor/bin/pest
```
> Nota: i test e il tooling QA sono stati sviluppati nel monorepo originale; vedi `LESSON.md` per il
> setup standalone. La suite di test completa di questo package è in fase di migrazione per-repo.

## Loop di lavoro
Branch per task → gate locale (test + advisory `copilot -p`, **mai `--yolo`**) → PR → CI + Copilot review
→ merge → tag. Aggiorna `LESSON.md` ad ogni fix. Dettaglio: la skill `laravel-iam-package-workflow`.
