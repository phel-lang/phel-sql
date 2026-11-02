---
description: Use to take a phel-sql GitHub issue through branch, TDD, PR, CI and merge.
argument-hint: "[issue-number]"
disable-model-invocation: true
---

# GitHub issue workflow

Read the issue body and every comment as requirements. When a later comment conflicts with the body, the comment wins.

::target claude
!`gh issue view ${ARGUMENTS#\#} --json number,url,title,body,labels,assignees,state,comments 2>/dev/null || echo "Provide an issue number"`
::end

## Setup

1. Parse the issue number from the argument (strip `#`).
2. If unassigned: `gh issue edit <number> --add-assignee @me`.
3. Branch from fresh `main`: `git checkout main && git pull && git checkout -b <prefix><issue-number>-<slug>`. Prefix from labels: `bug` is `fix/`, `enhancement` or none is `feat/`, `documentation` is `docs/`.

## Plan

4. Plan Mode: say whether it touches a clause, operator, tagged form, error or docs; list the files (`src/phel/sql/<file>.phel`, `src/phel/sql.phel` for a new cross-file declaration, `tests/sql/<topic>-test.phel`, docs); list the tests (one per shape, one per error); give the order.

## Implement (TDD)

5. After approval: failing `deftest`s first, then the minimal emitter or branch, then refactor while green.
6. `composer test`.

## Ship

7. Update `CHANGELOG.md` under `## [Unreleased]`.
8. Commit specific files: `<type>(<scope>): <description>` with the body `Related to #<issue-number>`.
9. Final refactor pass, as the last commit before the PR. Re-review every touched file for duplication, dead branches, unused locals, naming drift against the surrounding sub-file, violations of `.agnostic-ai/rules/phel.md` and `sql-dsl.md`, and speculative abstractions. Apply, rerun `composer test`, commit as `ref(<scope>): polish <area> after #<issue-number>`. If nothing turns up, say so in the PR body.
10. Open the PR with `/pr #<issue-number>`.

## Verify and merge

11. `gh pr checks <pr-number> --watch`. Fix red checks on the branch.
12. `gh pr merge <pr-number> --squash --admin --delete-branch`. If `--admin` is rejected, use `--auto --squash --delete-branch` and say the PR awaits approval.
13. `git checkout main && git fetch origin main && git reset --hard origin/main`.
