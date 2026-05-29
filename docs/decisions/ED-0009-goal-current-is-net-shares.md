# ED-0009 — Goal "Current" is net shares


- **Date:** 2026-05 · **Status:** Accepted
- **Context:** Borrowed shares aren't actually held by the account.
- **Decision:** A goal's "Current" figure uses **net** shares (OWN − BOR), not
  gross — net is the only truthful "what you hold" number.
- **Consequences:** Goal progress reflects net position; borrowed shares don't
  inflate it.
- **Source:** Goal/AccountGoal logic; project memory.
