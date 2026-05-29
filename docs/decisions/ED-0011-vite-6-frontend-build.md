# ED-0011 — Frontend build pinned to Vite 6 (not 5, not latest)


- **Date:** 2026-05 · **Status:** Accepted
- **Context:** `vite@4` bundled `esbuild@0.18`, which carried a cluster of 5
  dev-server-only GHSA advisories parked in `.npm-audit-ignore`, and mismatched
  `laravel-vite-plugin@1.3.0`'s peer range (`^5||^6`), forcing
  `npm install --legacy-peer-deps`.
- **Decision:** Bump to **Vite 6** (`^6.0`), not 5 and not the newest (7/8).
  Vite 6 is the lowest major that pulls a *patched* `esbuild@^0.25` (vite 5 still
  ships `esbuild@^0.21`, leaving `GHSA-67mh-4wv8-2f99` unfixed); it satisfies the
  **existing** `laravel-vite-plugin@^1.0` peer range, so no plugin major bump is
  needed; and it keeps the broad node engine range (`^18||^20||>=22`) the CI
  node-20 jobs rely on (vite 7/8 require node ≥20.19 and `laravel-vite-plugin@2`+).
- **Consequences:** All 5 advisories cleared (`npm audit` → 0), `.npm-audit-ignore`
  emptied, `--legacy-peer-deps` no longer required (dropped from `bin/test.sh`).
  Revisit a 7/8 + plugin-major bump only if a future advisory needs it.
- **Source:** #34; `package.json`, `.npm-audit-ignore`, `bin/test.sh`.
