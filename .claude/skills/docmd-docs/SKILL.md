---
name: docmd-docs
description: Use when working in docs-site/ of this repo — building or serving the docmd documentation site, adding/editing pages under docs-site/docs/**, touching docmd.config.json navigation or plugins, wiring semantic search, or keeping the public docs in sync with the contracts. Trigger on any documentation-site change for laravel-iam-contracts.
---

# docmd docs-site — authoring skill (laravel-iam-contracts)

The public documentation site lives in **`docs-site/`** and is built with
[docmd](https://docs.docmd.io), a static-site generator from Markdown. It deploys to
`https://doc.laravel-iam-contracts.padosoft.com` via Cloudflare Pages (Git integration — the user wires
deploy, do NOT add deploy CI). This skill is the house guide for editing it.

## Layout

```
docs-site/
  docmd.config.json          # metadata, url, navigation (sole sidebar source), theme, plugins
  package.json               # scripts: dev / build / check
  .node-version              # "20"
  .gitignore                 # ignores _site/, node_modules/, search cache (keeps config.json)
  .docmd-search/config.json  # pinned embedding model (COMMITTED) — skips the interactive wizard
  assets/favicon.svg, assets/custom.css   # brand teal #0d9488
  scripts/check-no-raw-html.mjs           # CI guard
  docs/**                    # every page (route mirrors the tree); docs/index.md = "/"
  _site/                     # build output (git-ignored)
```

Route rule: `docs/reference/crypto.md` → `/reference/crypto`; `docs/index.md` → `/`.

## Commands

```bash
cd docs-site
npm ci            # install from the committed cross-platform lockfile (v3, Linux natives for CF)
npm run check     # guard: no raw HTML / MDX / ::: button
npm run build     # generates _site/ + semantic index + llms.txt + sitemap.xml
npm run dev       # local preview
```

Completion bar: `npm run check` **and** `npm run build` green, `_site/index.html` present, 0 visible
`:::`, mermaid + KaTeX rendered, `_site/.docmd-search/manifest.json` generated.

## navigation[] is the ONLY sidebar source

Pages do **not** auto-register. Every new page MUST be added to `navigation[]` in `docmd.config.json`
or it won't appear. Group icons are **Lucide** names in kebab-case (https://lucide.dev). The current
groups: Get Started, Concepts & Theory, Architecture, Guides, Reference, Links.

## Container syntax (pure Markdown — NEVER MDX/JSX)

| Need | Syntax |
|---|---|
| Callout | `::: callout info "Title" icon:lucide-name` … `:::` (info/tip/warning/danger/success) |
| Tabs | `::: tabs` then `== tab "Label"` blocks, close `:::` |
| Steps | `::: steps` then numbered list `1. **Title**`, body indented **3 spaces**, close `:::` |
| Collapsible | `::: collapsible "Title"` … `:::` (prefix `open` to expand by default) |
| Cards | `::: grids` › `::: grid` › `::: card "Title" icon:lucide-name` › body › `[Open →](/path)` › `:::` |
| Diagram | ` ```mermaid ` fence (flowchart, sequenceDiagram, …) |
| Math | KaTeX `$…$` inline, `$$…$$` block |

`::: button` is NOT a block — inside a card use a Markdown link `[Open →](/path)`. The guard rejects raw
HTML tags and `::: button`.

## Plugins (in docmd.config.json)

`search` (semantic), `git` (repo/editLink/lastUpdated), `seo`, `sitemap`, `mermaid`, `math`,
`llms` (fullContext), `analytics` (off). `sitemap`/`seo`/`llms` need the root `url`.

## Semantic search

`plugins.search.semantic: true` uses `docmd-search`: embeddings computed at **build time** with ONNX
Runtime; browser gets quantized Int8 vectors and does cosine match — 100% client-side. The model is
pinned in `.docmd-search/config.json` (`Xenova/all-MiniLM-L6-v2`) so the first build does NOT block on
the interactive model-picker wizard. Keep that file committed; the rest of `.docmd-search/` is ignored.

## Page standard (academic + junior-proof)

Deep pages follow: **Motivation → Theory (KaTeX where apt) → Design (a Mermaid) → Contract/data model →
ADR (`::: collapsible`, Problem→Decision→Consequences) → Worked example → Gotchas (`::: callout warning`)**.
This is a contracts package: the Reference must be **complete and exact** — copy signatures verbatim from
`src/` (namespace `Padosoft\Iam\Contracts\`) and state, per symbol, who **implements** (the server) and who
**consumes** (client/modules/SDKs) it.

## Brand & footer

Teal `#0d9488` in `assets/custom.css`. Footer credits Lorenzo Padovani / Padosoft, links GitHub +
Packagist, MIT.

## Gotchas

1. `docs/index.md` is mandatory (route `/`).
2. Steps body re-indented to **3 spaces** so nested fences/callouts stay in the item.
3. KaTeX only renders outside code blocks.
4. Use the committed lockfile (v3, cross-platform) — don't regenerate it on Windows; verify with `npm ci`.
5. Raw `<tag>` in prose fails `npm run check` — use containers, not HTML.
