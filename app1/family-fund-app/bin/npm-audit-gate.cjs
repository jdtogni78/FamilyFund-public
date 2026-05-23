#!/usr/bin/env node
// npm audit gate that honors a documented ignore list (.npm-audit-ignore at the
// repo root). Runs `npm audit --json`, then fails only on advisories at or above
// NPM_AUDIT_LEVEL (default "moderate") whose GHSA id is NOT in the ignore list.
// Shared by bin/security-scan.sh and the CI security-scan workflow so the local
// gate and CI agree exactly.
const fs = require("fs");
const path = require("path");
const { execSync } = require("child_process");

const order = { info: 0, low: 1, moderate: 2, high: 3, critical: 4 };
const level = process.env.NPM_AUDIT_LEVEL || "moderate";
const min = order[level] ?? 2;

const ignorePath =
  process.env.NPM_AUDIT_IGNORE ||
  path.resolve(__dirname, "../../../.npm-audit-ignore");

const allow = new Set();
if (fs.existsSync(ignorePath)) {
  for (let line of fs.readFileSync(ignorePath, "utf8").split("\n")) {
    line = line.replace(/#.*/, "").trim();
    if (line) allow.add(line);
  }
}

// npm audit exits non-zero whenever advisories exist; capture stdout regardless.
let raw = "";
try {
  raw = execSync(`npm audit --json --audit-level=${level}`, {
    encoding: "utf8",
    stdio: ["ignore", "pipe", "ignore"],
  });
} catch (e) {
  raw = e.stdout || "";
}

let report;
try {
  report = JSON.parse(raw);
} catch {
  console.error("[npm-audit-gate] could not parse `npm audit --json` output");
  process.exit(2);
}

const blocking = new Set();
for (const [name, info] of Object.entries(report.vulnerabilities || {})) {
  for (const via of info.via || []) {
    if (typeof via !== "object") continue;
    if ((order[via.severity] ?? 0) < min) continue;
    const id = (via.url || "").split("/").pop() || String(via.source || "");
    if (allow.has(id)) continue;
    blocking.add(`${name}: ${via.severity} ${id} ${via.title || ""}`.trim());
  }
}

if (blocking.size) {
  console.error(`[npm-audit-gate] advisories >= ${level} not in ignore list:`);
  for (const b of blocking) console.error("  - " + b);
  process.exit(1);
}
console.error(`[npm-audit-gate] clean (>= ${level}, after ignore list).`);
