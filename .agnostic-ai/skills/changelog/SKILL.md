---
description: Use to add entries to the Unreleased section of CHANGELOG.md from recent commits or given text.
argument-hint: "[entry text]"
disable-model-invocation: true
allowed-tools: "read, edit, shell(git *)"
---

# Update changelog

::target claude
!`git log $(git describe --tags --abbrev=0 2>/dev/null || echo HEAD~20)..HEAD --oneline`
::end

1. Read `CHANGELOG.md`.
2. With an argument, append it under the right category in `## [Unreleased]`.
3. Without one, draft entries from the commits since the last tag and show the draft before writing:
   - `### Added`: `feat:` commits.
   - `### Changed`: `ref:`, `perf:`, behavior changes.
   - `### Fixed`: `fix:` commits.
   - `### Removed`: removed features.
4. Entry format: imperative ("Add", not "Added"); code in backticks; under 100 characters; prefix breaking changes with **BREAKING**; skip non-user-facing commits (`chore:`, CI, refactors without behavior change).
