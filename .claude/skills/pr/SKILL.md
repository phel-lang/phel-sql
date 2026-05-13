---
description: Push branch and create a PR with concise description and labels
argument-hint: "[issue-number]"
disable-model-invocation: true
allowed-tools: "Read, Edit, Bash(git *), Bash(gh *)"
---

# Create Pull Request

## Context

!`git branch --show-current`
!`git log main..HEAD --oneline`
!`git diff main..HEAD --stat`

## Instructions

1. **Check `CHANGELOG.md`** — if user-facing changes weren't recorded under `## [Unreleased]`, add them now and commit:
   ```bash
   git add CHANGELOG.md && git commit -m "chore: update changelog"
   ```

2. **Push branch**:
   ```bash
   git push -u origin HEAD
   ```

3. **Generate PR title**:
   - If `$ARGUMENTS` contains an issue number, fetch its title:
     ```bash
     gh issue view <number> --json title -q '.title'
     ```
   - Format: `<type>(<scope>): <short description>` (under 70 chars).
   - Derive `<type>` from branch prefix (`feat/` → feat, `fix/` → fix, `docs/` → docs, `ref/` → ref).

4. **Read `.github/PULL_REQUEST_TEMPLATE.md`** and reuse its exact section headers (📚 Description / 🔖 Changes). Do not hardcode — always read the template first.

5. **Create PR**:
   ```bash
   gh pr create --title "<title>" --assignee @me --label "<label>" --body "$(cat <<'EOF'
   <headers from .github/PULL_REQUEST_TEMPLATE.md filled in>

   Closes #<issue-number>
   EOF
   )"
   ```

   **Labels** — pick the single most relevant:
   - `bug` — branch starts with `fix/`
   - `enhancement` — branch starts with `feat/`
   - `documentation` — branch starts with `docs/`
   - `refactoring` — code restructuring with no behavior change
   - `pure testing` — only test changes
   - `dependencies` — dependency updates

   **Body guidelines:**
   - Focus on *what* and *why*, not implementation details.
   - Use `Closes #<number>` to auto-close on merge.
   - Keep the body under 15 lines.

6. **Report the PR URL.**
