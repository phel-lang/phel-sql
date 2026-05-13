---
description: Format, test, and commit with a conventional commit message
argument-hint: "[optional commit message]"
disable-model-invocation: true
allowed-tools: "Read, Edit, Bash(composer *), Bash(./vendor/bin/phel *), Bash(vendor/bin/phel *), Bash(git *)"
---

# Commit

## Context

!`git diff --stat`
!`git diff --cached --stat`
!`git status --short`

## Instructions

### Phase 1: Format

1. Format staged `.phel` files:
   ```bash
   composer format
   ```
2. If the formatter changed files, review and stage them.

### Phase 2: Test

3. Run the full suite (it's fast — pure Phel, no static analysis pipeline):
   ```bash
   composer test
   ```
   If any test fails, fix and re-run before continuing.

### Phase 3: Commit

4. **Stage files** — add by name, never `git add -A`.

5. **Draft commit message** using conventional commit format:
   - If `$ARGUMENTS` is provided, use it.
   - Otherwise, analyze the staged diff and generate one.
   - Prefixes: `feat:`, `fix:`, `ref:`, `chore:`, `docs:`, `test:`, `ci:`.
   - Add `(<scope>)` when scoped to one area (`select`, `where`, `dml`, `expr`, etc.).
   - **NEVER mention AI tooling.**

6. **Commit**:
   ```bash
   git commit -m "<message>"
   ```

7. **CHANGELOG check** — if the prefix is `feat:` or `fix:`, verify `CHANGELOG.md` has been updated under `## [Unreleased]`. If not, warn before committing.

8. Report: commit hash, message, and files included.
