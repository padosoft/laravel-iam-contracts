# LESSON.md — lezioni dell'ecosistema Laravel IAM

> Lezioni **generali** valide per ogni package, accumulate costruendo Laravel IAM v1.0 (16 milestone,
> TDD + loop advisory). Sotto, la sezione **specifica di questo package**. Aggiorna ad ogni scoperta.

## Generali — toolchain & PHPStan max

- **Test con PHP 8.5 (Herd)**: `~/.config/herd/bin/php85/php.exe`. Su Windows, PHPStan vuole
  `--memory-limit=1G` e, prima di Pest/testbench, `attrib -R` sulla dir
  `vendor/orchestra/testbench-core/laravel/bootstrap/cache` (bug `is_writable()`). `.gitattributes eol=lf`.
- **PHPStan crash transitorio** ("Result is incomplete because of severe errors"): ri-eseguire risolve.
- **Mai cast su `mixed`**: usare guardie `is_int`/`is_string`/`is_numeric`, non `(string)`/`(int)`.
- **`@property` sui Model invece di castare nel chiamante**: una colonna castata letta da un servizio
  esterno al model fa fallire PHPStan (`property.notFound` → `Cannot cast mixed`). Dichiarare
  `@property Carbon|null` sul model; poi un `?->` su valore ora non-null diventa `nullsafe.neverNull` → `->`.
- **Mai `*/` dentro un docblock**: `decided_*/granted_id` in `/** */` CHIUDE il commento → ParseError.
- **`@phpstan-impure`** per i metodi con side-effect osservabili (mutano una proprietà pubblica e vengono
  chiamati due volte): senza, PHPStan crede il secondo valore immutato (`booleanOr.leftAlwaysFalse`).
- **Config da `mixed` → `array<string,mixed>` provabile**: `is_array($x) ? $x : []` resta `array<mixed>`;
  ricostruire con un `foreach` che casta le chiavi a stringa per soddisfare la firma.
- **larastan + generics Eloquent + closure**: `Builder<User>` non è assegnabile a `Builder<Model>`
  (invariante) e `get()` perde `TModel`. Per un paginator generico: `@param Builder<covariant Model>` +
  `callable(Model): array` con narrowing `instanceof` al call-site.

## Generali — sicurezza & processo

- **Fail-closed sempre**: default-deny, deny-overrides; un errore (transport, PDP, parsing) → deny, mai un
  allow né un 500 opaco. Vale per PDP, client, directory, AI.
- **Il loop advisory trova bug reali ad ogni slice**: TOCTOU, fail-open, takeover, info-disclosure,
  escalation. `copilot -p` (advisory), **mai** `--autopilot --yolo`. Ogni fix → qui.
- **TOCTOU sulle transizioni di stato**: leggere-poi-scrivere uno stato senza `DB::transaction` +
  `lockForUpdate` + re-check sotto lock = last-write-wins (grant orfano, doppia approvazione).
- **Snapshot vs dato vivo**: la governance congela i segnali/policy al momento giusto; l'esito non deve
  dipendere da una modifica successiva (un ruolo tolto dal catalogo non deve creare grant permanenti).
- **Tenant isolation = 404, non 403**: il cross-tenant deve essere indistinguibile da "non esiste",
  altrimenti il 403 conferma l'esistenza dell'UUID (enumerazione).
- **Deps pesanti in `suggest`, non `require`**: `aws-sdk-php`, `ldaprecord` (ext-ldap), `laravel/ai`
  rallentano/ rompono install e CI. Il core resta usabile senza; l'adapter reale è opzionale e, se non
  installabile in dev, va isolato (sottospazio + `excludePaths` PHPStan).
- **Commit message via file** se l'here-string fallisce su Windows: scrivere su file e `git commit -F`.

## Specifiche di questo package (laravel-iam-contracts)

- **Una modifica a un contratto è una breaking change per l'intero ecosistema.** Aggiungere un metodo a
  `AuthorizationEngine`/`SessionRegistry`/… rompe ogni implementatore (NativeSqlEngine in `-server`, futuri
  OpenFGA/SpiceDB, gli adapter nei client). Versiona con semver, preferisci **nuove interfacce** a modifiche
  di quelle esistenti, e tratta anche le **shape `array{...}`** dei docblock come parte del contratto (sono
  i precursori dei value object DecisionQuery/Decision di M2).
- **`require` deve restare solo `php`.** Niente `illuminate/*`, niente runtime deps: questo è il livello su
  cui tutti dipendono, deve installarsi ovunque senza trascinare Laravel. Per i tipi temporali usa
  `\DateTimeImmutable` (vedi `StepUpChallenge`), non `Carbon`.
- **Le interfacce sono il "seam" per i motori pluggable.** `AuthorizationEngine` è il punto in cui il PDP
  nativo SQL (oggi) e un backend Zanzibar (OpenFGA/SpiceDB, v2) si scambiano senza toccare i consumer. Lo
  stesso vale per `KeyProvider` (Local → AWS KMS → Vault/HSM) e `FactorVerifier` (Fortify/passkeys → Rebel).
- **DTO `final readonly` + enum con comportamento minimo.** `Aal` porta `rank()`/`satisfies()`/`fromString()`:
  logica pura senza stato, comparabile e fail-safe (`fromString(null)` → `AAL1`, il più debole). È il pattern
  per ogni enum di assurance/scope.
- **Fail-closed anche nei contratti.** Le firme lo codificano: `SessionRegistry::active()` documenta
  "Fail-closed" (sessione sconosciuta/scaduta → `false`), `AssuranceProvider::currentAal()` ritorna `AAL1`
  se la sessione non è attiva. Mantieni questa semantica quando estendi le interfacce.
