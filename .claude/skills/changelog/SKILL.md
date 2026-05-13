---
description: Update CHANGELOG.md Unreleased section from recent commits or manual entry
argument-hint: "[entry text]"
disable-model-invocation: true
allowed-tools: "Read, Edit, Bash(git *)"
---

# Update Changelog

## Context

!`git log $(git describe --tags --abbrev=0 2>/dev/null || echo HEAD~20)..HEAD --oneline`

## Instructions

1. Read `CHANGELOG.md`.

2. If `$ARGUMENTS` is provided, append it under the appropriate category in `## [Unreleased]`.

3. Else, analyze commits since the last tag and draft entries:
   - `### Added` — `feat:` commits.
   - `### Changed` — `ref:`, `perf:`, behavior changes.
   - `### Fixed` — `fix:` commits.
   - `### Removed` — removed features.

4. Entry format:
   - Imperative mood: "Add" not "Added".
   - Code in backticks: `` `(sql/format {:select [:id]})` ``.
   - Under 100 chars.
   - Skip non-user-facing commits (`chore:`, CI, internal refactor with no behavior change).
   - Prefix breaking changes with **BREAKING**.

5. Edit `CHANGELOG.md`. Present draft before writing when generating from commits.
