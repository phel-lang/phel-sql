# Claude Code project config

Repo-maintenance config for phel-sql. Inspired by `.claude/` in [phel-lang/phel-lang](https://github.com/phel-lang/phel-lang/tree/main/.claude), trimmed for a pure-Phel library.

| Path | Purpose |
|------|---------|
| `CLAUDE.md` | Project entrypoint loaded into context. |
| `settings.json` | Permissions, hooks, status line. |
| `settings.local.json` | Optional local allowances, gitignored. |
| `statusline.sh` | Status-line renderer. |
| `hooks/` | `format-phel.sh` (PostToolUse), `protect-files.sh` (PreToolUse), `compact-context.sh` (SessionStart). |
| `agents/` | `clean-code-reviewer`, `debugger`, `tdd-coach`. |
| `rules/` | `phel.md` (source + tests), `sql-dsl.md` (clause / dispatch invariants). |
| `skills/` | `commit`, `pr`, `gh-issue`, `release`, `changelog`, `new-clause`. |

No PHP source, no PHPUnit, no static analysis pipeline. Single test command (`composer test`), single formatter (`composer format`).
