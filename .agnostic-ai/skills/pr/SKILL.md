---
description: Use to push the branch and open a PR with the repo template and a label.
argument-hint: "[issue-number]"
disable-model-invocation: true
allowed-tools: "read, edit, shell(git *), shell(gh *)"
---

# Create PR

::target claude
!`git branch --show-current`
!`git log main..HEAD --oneline`
!`git diff main..HEAD --stat`
::end

1. If user-facing changes are missing from `## [Unreleased]` in `CHANGELOG.md`, add them and commit `chore: update changelog`.
2. `git push -u origin HEAD`.
3. Title: `<type>(<scope>): <short description>`, under 70 characters. Type comes from the branch prefix (`feat/`, `fix/`, `docs/`, `ref/`). With an issue number, take the description from `gh issue view <number> --json title -q '.title'`.
4. Read `.github/PULL_REQUEST_TEMPLATE.md` and reuse its exact section headers.
5. `gh pr create --title "<title>" --assignee @me --label "<label>" --body "<filled template>"`, ending the body with `Closes #<issue-number>`. Keep the body under 15 lines: what and why, not implementation.
6. Label (one): `bug` (`fix/`), `enhancement` (`feat/`), `documentation` (`docs/`), `refactoring` (restructuring without behavior change), `pure testing` (tests only), `dependencies`.
7. Report the PR URL.
