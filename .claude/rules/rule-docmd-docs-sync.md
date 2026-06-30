---
name: rule-docmd-docs-sync
description: Binding rule — keep the docmd docs-site in sync with the contracts whenever a user-facing change or a substantial README update happens.
---

# RULE (binding): docmd docs must stay in sync with the contracts

This rule is **mandatory and blocking**. It applies to `C:\xampp\htdocs\laravel-iam-contracts`.

## The rule

**Every time** you add or change a user-facing element of this package — a contract interface, a value
object, an enum case, a method signature, a documented default/invariant, or you update the README in a
substantial way — you **MUST**, in the *same* piece of work, update the corresponding page under
`docs-site/docs/**` and (if the page is new) register it in `navigation[]` in `docs-site/docmd.config.json`.
Follow the [`docmd-docs`](../skills/docmd-docs/SKILL.md) skill.

Because this is a **contracts-only** package, the bar is exactness:

- A new/changed symbol in `src/` (namespace `Padosoft\Iam\Contracts\`) requires the matching Reference page
  (`docs-site/docs/reference/*`) to be updated with the **verbatim** signature, and the *who implements /
  who consumes* lines kept correct.
- A new contract that changes the ecosystem story requires updating the dependency diagram in
  `docs-site/docs/architecture/overview.md`.
- A breaking change to a published interface requires a note in
  `docs-site/docs/architecture/versioning.md` (and a major bump — see that page).

## Definition of done (before you close the work)

```bash
cd docs-site
npm run check && npm run build   # both MUST be green
```

`_site/index.html` present, 0 visible `:::`, mermaid + KaTeX rendered, semantic index generated.

## When the rule does NOT apply

Declare it explicitly (in the PR/commit body) when a change is **not** user-facing and so needs no doc
update:

- internal refactor with identical public surface;
- tooling/CI/lint config that doesn't change a contract;
- pure cosmetics (formatting, typo in a comment).

In those cases write one line: *"No docs-site change: <reason>."*

## Anti-patterns (all forbidden)

- A new/changed contract, enum case or signature shipped **without** the Reference page updated.
- A new page that is **not** in `navigation[]` (it won't render in the sidebar).
- Any **MDX/JSX or raw HTML** in a page (fails `npm run check`).
- A README feature/claim that the docs-site contradicts or omits.
- Committing with a **red** `npm run build`.
