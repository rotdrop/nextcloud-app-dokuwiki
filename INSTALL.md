# Installation Instructions

<!-- markdown-toc start - Don't edit this section. Run M-x markdown-toc-refresh-toc -->
**Table of Contents**

- ["Official" Releases](#official-releases)
- [Release-Branches](#release-branches)
- [Building from Source](#building-from-source)
- [Automated Releases](#automated-releases)

<!-- markdown-toc end -->


## "Official" Releases

This app is available throught the
[Nextcloud app-store](https://apps.nextcloud.com/apps/dokuwiki)
and show up automatically in the app-installation option of you
Nextcloud instance if you are logged in as administrator. The goal is
to have working versions for the most recent three (3) releases of
nextcloud available through the Nextcloud app-store.

This is the preferred installation method for all "consumer"-users
which have no or little experience in software development.

## Release-Branches

> Installing from GitHub needs more-or-less advanced programming skill
> or at least the will to learn new things

On GitHub there are release branches for stable releases of
Nextcloud. These include all needed assets to run the app and probably
a lot of unneeded data. Also, updating those release branches is as of
now not automated, so they may lack behind. They may even lack behind
the releases available through the app-store. But perhaps they are
more up to date. Or not. Or they may be. Or not.

Anyhow. Example: release branch for Nextcloud vXY:

- get yourself terminal access to your Nextcloud instance, probably by ssh
  - don't know what this means? Then this section is not for you
- [`git clone https://github.com/rotdrop/nextcloud-app-dokuwiki.git`](https://github.com/rotdrop/nextcloud-app-dokuwiki.git) into your apps directory
- `git checkout stableXY`
- fix permissions, maybe something like

  `chmod -R www-data:www-data YOUR_INSTALLATION_DIRECTORY_OF_CHOICE`

  might be needed. This depends on your installation.
- afterwards the app should show app in the admin's app-management
  page and can be enabled there.

## Building from Source

> This assumes that you really have firm programming knowledge, best
> with a deep background in web-applications, you know what `npm`,
> `composer`, `webpack`, `vue` means and you can help yourself: if
> something does not work then your primary reaction is to use one of
> the various available search engines, read the docs, Read Those Fine
> Docs [RTFM], and finally, if really nothing helps after trying hard
> for 24 hours, you may then contact the author of this package and
> suggest improvements, submit pull-request and so forth.

As of the time of this writing building the app from source requires
that it is installed into a running Nextcloud instance. This is
unfortunate, please submit a pull request if you do not like the
current state.

So:

- get yourself terminal access to your Nextcloud instance, probably by ssh
  - don't know what this means? Then this section is not for you
- `git clone --recurse-submodules https://github.com/rotdrop/nextcloud-app-dokuwiki.git` into your apps directory
- optionally change to a release-branch, but you **very probably**
  just want to build the app from the default branch (main or master)
- run `git submodule update --init --recursive` after switching branches or
  updating an existing checkout to initialize the pinned build helpers in
  `dev-scripts/lib`
- install all needed dependencies
  - a recent version of `npm`
  - GNU make
  - a recent version of `composer`
  - perhaps a lot of further utilities, watch out for error messages during the build process
    - **pull request to improve this part of the documentation are very welcome**
- build the app with
  - `make build` to generate a production version of the app
  - `make dev` to generate a development version debuging info
    compiled in. This mostly affects the generated JavaScript assets.
  - in case you run `make` repeatedly (perhaps after adjusting things
    to your liking) and it fails: try to run `make realclean` in order
    to just remove every build artifact. Then try again.
  - if you just edit individual files then `make` should just pick up
    your changes and rebuild stuff as needed

## Automated Releases

Pushing `vX.Y.Z` builds a signed GitHub release and publishes its archive to
the Nextcloud app store. Pushing `vX.Y.Z-rcW` (with `W >= 1`) creates a
GitHub pre-release and publishes it to the app store as a nightly release.
The app store replaces previous nightly releases when a new one is uploaded.
Other version suffixes are rejected.

Before tagging, set the same version (without the leading `v`) in
`appinfo/info.xml`, `package.json`, and both root version fields of
`package-lock.json`. Put a dated `## [X.Y.Z] - YYYY-MM-DD` entry (including
the `-rcW` suffix for candidates) at the top of `CHANGELOG.md`, with
non-empty release notes. An optional `[Unreleased]` section may precede it.
CI and the release workflow check consistency; invalid tags or metadata
stop the release before the build.

### Configure signing and publishing

Create a GitHub Actions environment named `release` in repository settings.
Configure required reviewers and restrict deployment tags to `v*`, so
release credentials are not available to ordinary PR builds. Add these
environment secrets:

| Secret | Value |
| --- | --- |
| `APP_PRIVATE_KEY` | The complete PEM code-signing private key for `dokuwiki` |
| `APP_PUBLIC_CRT` | The matching Nextcloud-issued PEM certificate |
| `APPSTORE_TOKEN` | A token for an app-store account authorized to publish `dokuwiki` |

The public certificate is not confidential; it is stored alongside the key
as an environment secret for configuration convenience. Obtain the token
from https://apps.nextcloud.com/account/token.

Upload the key directly from your local machine, **not through an issue,
chat, commit, or PR**. For example, with the GitHub CLI authenticated locally:

```sh
gh secret set APP_PRIVATE_KEY --env release --repo rotdrop/nextcloud-app-dokuwiki < ~/.nextcloud/certificates/dokuwiki.key
gh secret set APP_PUBLIC_CRT --env release --repo rotdrop/nextcloud-app-dokuwiki < ~/.nextcloud/certificates/dokuwiki.crt
gh secret set APPSTORE_TOKEN --env release --repo rotdrop/nextcloud-app-dokuwiki
```

The last command prompts for the token without putting it in shell history.
The private key must be usable without an interactive passphrase prompt.
Never commit a key or disable signing checks to get a release out.

The release uses the existing app-store build, signs the staged app files
with Nextcloud's `occ`, verifies their integrity, repacks the archive, and
verifies a separate SHA-512 archive signature against the certificate.
The key is exposed only to the signing step and its temporary files are
removed on exit. Signing failures and missing secrets fail the workflow.
PR builds use the same build workflow without signing secrets.

GitHub receives the ready-to-install `.tar.gz`, its binary `.sig`, and a
`.sha256` checksum. Use this attached archive, **not** GitHub's automatically
generated source archives. The publisher checks the archived metadata and
verifies that the public download matches the signed build before submitting
that URL and signature to the app store. No local tarball selection is involved.

After the release-preparation PR is merged, synchronize your local `master`
with `origin/master`, then run:

```sh
make release
```

This uses `dev-scripts/release.sh` to select the newest dated changelog
version, require matching app/npm/lockfile versions and non-empty notes,
and create and push an annotated tag. It requires Git, Bash, and Python 3,
not the app's build dependencies. It refuses dirty checkouts, branches other
than `master`, commits not already on `origin/master`, and existing local
or remote release tags. It does not push commits or build locally.

To additionally assert a specific version, use `make release VERSION=1.2.2`
or `bash dev-scripts/release.sh 1.2.2` (RC versions work the same way).
If a tag push fails, the local tag is retained: inspect the remote and the
error before retrying the push. The helper will not overwrite any tag.

Approve the `release` environment deployments when prompted. Publishing to
GitHub precedes the app-store request because the store needs a public URL.
If app-store publishing fails, the GitHub release remains available; inspect
the workflow error, resolve the problem, and use **Re-run failed jobs** to retry
only the app-store job with the existing signed artifact. Do not rerun the
entire workflow, retag, or overwrite an already published version.
