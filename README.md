# Doku Wiki Embedded

<!-- markdown-toc start - Don't edit this section. Run M-x markdown-toc-refresh-toc -->
**Table of Contents**

- [Doku Wiki Embedded](#doku-wiki-embedded)
    - [Intro](#intro)
    - [Installation](#installation)
    - [More Documentation will follow ...](#more-documentation-will-follow-)

<!-- markdown-toc end -->

## Intro

This is a Nextcloud app which embeds a Dokuwiki instance into a
Nextcloud server installation. If Dokuwiki and Nextcloud are
configured to use the same authentication backend, then this will work
with SSO, otherwise the login window of DokuWiki will appear in the
embedding iframe.

## Installation

- ~install from the app-store~ (not yet)
- install from a (pre-)release tar-ball by extracting it into your app folder
- clone the git repository in to your app folder and run make
  - `make help` will list all targets
  - `make dev` comiles without minification or other assset-size optimizations
  - `make build` will generate optimized assets
  - there are several build-dependencies like compose, node, tar
    ... just try and install all missing tools ;)

## More Documentation will follow ...
