#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
set -euo pipefail

if [[ $# -gt 1 ]]; then
  echo "Usage: $0 [VERSION] (for example: 1.2.2 or 1.2.2-rc1)" >&2
  exit 2
fi

repo_root=$(git -C "$(dirname "$0")" rev-parse --show-toplevel)
cd "$repo_root"

if [[ $(git branch --show-current) != master ]]; then
  echo "Release must be run from the master branch." >&2
  exit 1
fi
if [[ -n $(git status --porcelain) ]]; then
  echo "Working tree must be clean before releasing." >&2
  exit 1
fi

arguments=(--version-only)
if [[ $# -eq 1 ]]; then
  arguments+=(--tag "v$1")
fi
version=$(python3 .github/scripts/validate-release.py "${arguments[@]}")
tag="v$version"

if git show-ref --verify --quiet "refs/tags/$tag"; then
  echo "Tag $tag already exists locally; the version is already tagged." >&2
  exit 1
fi
remote_tag=$(git ls-remote --tags origin "refs/tags/$tag")
if [[ -n $remote_tag ]]; then
  echo "Tag $tag already exists on origin; the version is already published." >&2
  exit 1
fi
remote_master=$(git ls-remote --heads origin refs/heads/master)
if [[ -z $remote_master || ${remote_master%%$'\t'*} != "$(git rev-parse HEAD)" ]]; then
  echo "HEAD must match origin/master. Synchronize and push reviewed commits before releasing." >&2
  exit 1
fi

git tag -a "$tag" -m "Release $tag"
if ! git push origin "refs/tags/$tag:refs/tags/$tag"; then
  echo "Push failed. Local tag $tag has been retained; inspect origin before retrying." >&2
  exit 1
fi
echo "Pushed $tag. Approve the release environment deployments in GitHub Actions."
