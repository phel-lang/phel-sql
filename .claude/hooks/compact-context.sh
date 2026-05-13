#!/bin/bash
# SessionStart hook: re-inject key context after compaction
cat <<'EOF'
## Context Reminder (post-compaction)

**phel-sql** is a HoneySQL-style SQL DSL written in pure Phel. Map in, `[sql params]` out. No I/O.

- Conventional commits (`feat:`, `fix:`, `ref:`, `chore:`, `docs:`, `test:`, `ci:`). NEVER mention AI tooling.
- Test: `composer test` (runs `vendor/bin/phel test`).
- Format: `composer format`. `.phel` edits auto-format via PostToolUse hook.
- Architecture: expressions (`ident+`, `operand`, `emit-tagged`) and clauses (dispatch maps). Statement kind auto-detected by `statement-type`.
- Sub-files in `src/sql/` start with `(in-ns phel.sql)`; cross-file fn refs resolve at call time.
- Protected files: `release.sh`, `.github/*`, `composer.lock`.
- `feat:`/`fix:` commits must update `## [Unreleased]` in `CHANGELOG.md`.
- PR template at `.github/PULL_REQUEST_TEMPLATE.md` (📚 Description / 🔖 Changes). Assign `@me`. Labels: `bug`, `enhancement`, `refactoring`, `documentation`, `pure testing`, `dependencies`.
EOF
