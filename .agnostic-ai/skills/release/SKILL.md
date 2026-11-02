---
description: Use to bump the version, tag and publish a GitHub release with release.sh.
argument-hint: "[version or --dry-run]"
disable-model-invocation: true
---

# Release

::target claude
!`git branch --show-current`
!`git status --porcelain`
!`grep -E '^## \[' CHANGELOG.md | head -5`
::end

1. Abort unless on `main` with a clean tree.
2. Check `## [Unreleased]` has content (the script copies it into the GitHub release notes):
   `awk '/^## \[Unreleased\]/{flag=1;next}/^## \[/{flag=0}flag' CHANGELOG.md`
3. With a version argument, check it is `X.Y.Z`. Without one, `./release.sh` bumps the minor.
4. When in doubt, run `./release.sh --dry-run [version]` first, then `./release.sh [version]`. The script validates, moves `## [Unreleased]` into `## [X.Y.Z] - YYYY-MM-DD`, commits, tags `vX.Y.Z`, pushes branch and tag, and creates the release.
5. Verify with `gh release view v<version>` and report the URL.
