# GitHub Projects setup

We use **GitHub Projects** (built into GitHub, free) instead of Jira for tracking work.
No server to host, no license — and a single board can span multiple repos.

## Boards

| Board | Repos it covers | Purpose |
|-------|-----------------|---------|
| **Trading & Fund** (#1) | `dstrader`, `dstrader-aws`, `dstrader_python`, `DSTraderAnalysis`, `FamilyFund` | All trading / portfolio work in one place |
| **Remote Control** (#2) | `claude-remote-control` | The remote-control supervisor project |
| **Job Search** (#3) | `job-search` (local-only, no remote) | Job-search tooling, website specs, related notes |

The number in parentheses is the project number used by `gh project` commands.

> **Note:** the GitHub repo `dstrader-aws` (the AWS deployment repo) is cloned
> locally into the folder `~/dev/dstrader-docker` — folder name ≠ repo name.

## One-time setup

The GitHub CLI (`gh`) is already installed and logged in as `jdtogni78`.
To manage Projects from the CLI, the auth token needs the `project` scope:

```bash
gh auth refresh -s project --hostname github.com
# open https://github.com/login/device and enter the one-time code shown
```

Confirm it worked:

```bash
gh project list --owner jdtogni78
```

## Everyday commands

These are user-level (owner) Projects, so reference them by their **number** (shown in `gh project list`).

```bash
# List all your boards and their numbers
gh project list --owner jdtogni78

# See a board's items
gh project item-list <NUMBER> --owner jdtogni78

# Create an issue in a repo, then add it to a board
gh issue create --repo jdtogni78/dstrader --title "..." --body "..."
gh project item-add <NUMBER> --owner jdtogni78 --url <ISSUE_URL>

# Add a free-form note (draft item) to a board
gh project item-create <NUMBER> --owner jdtogni78 --title "Idea: ..." --body "..."

# Open a board in the browser
gh project view <NUMBER> --owner jdtogni78 --web
```

## Notes

- A repo can be linked to a board so its issues/PRs surface there; an issue can also live on
  multiple boards.
- Status (Todo / In Progress / Done) is the default workflow field — change it from the board UI
  or via `gh project item-edit`.
- Manage everything in the browser too: <https://github.com/users/jdtogni78/projects>

## Links

- Trading & Fund board (#1): <https://github.com/users/jdtogni78/projects/1>
- Remote Control board (#2): <https://github.com/users/jdtogni78/projects/2>
- Job Search board (#3): <https://github.com/users/jdtogni78/projects/3>
