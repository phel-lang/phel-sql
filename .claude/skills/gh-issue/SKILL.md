---
description: Fetch a GitHub issue, branch, implement with TDD, open a PR
argument-hint: "[issue-number]"
disable-model-invocation: true
---

# GitHub Issue Workflow

## Context

Read both the issue body **and every comment** as requirements input. Maintainer follow-ups frequently add scope or override the original description; when a later comment conflicts with the body, prefer the comment.

!`gh issue view ${ARGUMENTS#\#} --json number,url,title,body,labels,assignees,state,comments 2>/dev/null || echo "Provide an issue number"`

## Instructions

### Phase 1: Setup

1. **Parse the issue number** from `$ARGUMENTS` (strip `#` if present).

2. **Assign yourself if unassigned**:
   ```bash
   gh issue edit <number> --add-assignee @me
   ```

3. **Branch from fresh `main`**:

   Prefix from labels:
   - `bug` → `fix/`
   - `enhancement` → `feat/`
   - `documentation` → `docs/`
   - No label → `feat/`

   ```bash
   git checkout main && git pull
   git checkout -b <prefix><issue-number>-<slug>
   ```

### Phase 2: Plan

4. **Enter Plan Mode**:
   - Identify whether this touches a clause, operator, tagged form, error, or docs.
   - List files to touch (`src/sql/<file>.phel`, `src/sql.phel` if a new cross-file decl is needed, `tests/sql/<topic>-test.phel`, docs).
   - Plan tests first.

5. **Create plan** with: summary, files to touch, test list (one per shape + one per error), implementation order.

### Phase 3: Implement (TDD)

6. After plan approval:
   - Write failing `deftest`s first.
   - Implement the minimum emitter / branch to pass.
   - Refactor while green.

7. **Run full suite**:
   ```bash
   composer test
   ```

### Phase 4: Ship

8. **Update `CHANGELOG.md`** under `## [Unreleased]`.

9. **Commit**:
   ```bash
   git add <specific-files>
   git commit -m "<type>(<scope>): <description>

   Related to #<issue-number>"
   ```

10. **Final refactor pass (mandatory, last commit before PR)**:
    Re-review every touched file. Look for:
    - duplication (extract or reuse);
    - dead branches, unused locals;
    - naming drift vs. surrounding sub-file;
    - violations of `.claude/rules/phel.md`, `sql-dsl.md`;
    - speculative abstractions.

    Apply, re-run `composer test`, commit as a separate `ref(<scope>):` — must be the final commit on the branch:
    ```bash
    git commit -m "ref(<scope>): polish <area> after #<issue-number>

    Related to #<issue-number>"
    ```
    If the review surfaces nothing, record that in the PR body instead of skipping silently.

11. **Open PR** via `/pr #<issue-number>`.

### Phase 5: Verify & Merge

12. **Wait for CI**:
    ```bash
    gh pr checks <pr-number> --watch
    ```
    Fix red checks on the branch.

13. **Merge** (admin bypass when allowed):
    ```bash
    gh pr merge <pr-number> --squash --admin --delete-branch
    ```
    If `--admin` is rejected, fall back to `--auto --squash --delete-branch` and surface that the PR awaits approval.

14. **Sync local main**:
    ```bash
    git checkout main && git fetch origin main && git reset --hard origin/main
    ```

## Checklist

- [ ] Issue fetched (body + comments)
- [ ] Self-assigned
- [ ] Branch from fresh `origin/main`
- [ ] Plan approved
- [ ] Tests written first (per shape + error)
- [ ] Implementation complete
- [ ] `composer test` green
- [ ] CHANGELOG updated
- [ ] Feature commit with issue reference
- [ ] Final `ref(...)` polish commit
- [ ] PR via `/pr`
- [ ] CI green
- [ ] Merged (admin or auto)
- [ ] Local `main` synced
