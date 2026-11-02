---
description: Use to format, test and commit staged work with a conventional commit message.
argument-hint: "[optional commit message]"
disable-model-invocation: true
allowed-tools: "read, edit, shell(composer *), shell(./vendor/bin/phel *), shell(vendor/bin/phel *), shell(git *)"
---

# Commit

::target claude
!`git diff --stat`
!`git diff --cached --stat`
!`git status --short`
::end

1. `composer format`. Review and stage anything the formatter changed.
2. `composer test`. Fix failures and rerun before continuing.
3. Stage files by name, never `git add -A`.
4. Message: the argument if given, otherwise derived from the staged diff. Prefixes `feat:`, `fix:`, `ref:`, `chore:`, `docs:`, `test:`, `ci:`; add `(<scope>)` for one area (`select`, `where`, `dml`, `expr`). No AI mention.
5. For `feat:` and `fix:`, warn if `CHANGELOG.md` has no new entry under `## [Unreleased]`.
6. Commit, then report the hash, message and files.
