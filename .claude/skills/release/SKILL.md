---
description: Run release.sh to bump version, tag, and publish a GitHub release
argument-hint: "[version or --dry-run]"
disable-model-invocation: true
---

# Release

## Context

!`git branch --show-current`
!`git status --porcelain`
!`grep -E '^## \[' CHANGELOG.md | head -5`

## Instructions

### Phase 1: Pre-flight

1. Abort if not on `main` or if there are uncommitted changes (see context).

2. **Check `## [Unreleased]` has content**:
   ```bash
   awk '/^## \[Unreleased\]/{flag=1;next}/^## \[/{flag=0}flag' CHANGELOG.md
   ```
   Warn if empty — the release script bakes this section into the GitHub release notes.

3. **Determine version**:
   - If `$ARGUMENTS` provides a version, validate format `X.Y.Z`.
   - Otherwise `./release.sh` auto-bumps the minor.

### Phase 2: Release

4. **Dry run first** when in doubt:
   ```bash
   ./release.sh --dry-run [version]
   ```

5. **Run the release**:
   ```bash
   ./release.sh [version]
   ```
   The script: validates semver + preflight, moves `## [Unreleased]` into `## [X.Y.Z] - YYYY-MM-DD`, commits, tags `vX.Y.Z`, pushes branch + tag, creates the GitHub release with the extracted notes.

### Phase 3: Verify

6. **Confirm release**:
   ```bash
   gh release view v<version>
   ```

7. **Report the release URL.**

## Reference

- Release script: `release.sh` (repo root)
- Changelog format: Keep a Changelog 1.1.0
