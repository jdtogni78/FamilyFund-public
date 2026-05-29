# ED-0010 — "Loan Share" is a label-only rename of credit_line


- **Date:** 2026-05 · **Status:** Accepted
- **Context:** The credit-line feature needed clearer user-facing wording.
- **Decision:** Rename to **"Loan Share" / "Loan Shares"** in the **UI labels
  only**. Classes, tables, and routes keep the `credit_line` name to avoid a
  churny rename.
- **Consequences:** Code/DB/route searches still use `credit_line`; only display
  strings say "Loan Share". Don't assume a label change implies a schema change.
- **Source:** #62 (`7290f706`); project memory.
