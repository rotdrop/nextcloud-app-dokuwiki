DokuWiki Integration for Nextcloud
==================================

<!-- markdown-toc start - Don't edit this section. Run M-x markdown-toc-refresh-toc -->
**Table of Contents**

- [Intro](#intro)
- [Installation](#installation)
- [Single Sign On](#single-sign-on)
    - [Using LDAP](#using-ldap)
    - [Using a DokuWiki Authentication Plugin](#using-a-dokuwiki-authentication-plugin)
- [More Documentation should follow ...](#more-documentation-should-follow-)

<!-- markdown-toc end -->

# Intro

This is a Nextcloud app which embeds a Dokuwiki instance into a
Nextcloud server installation. If Dokuwiki and Nextcloud are
configured to use the same authentication backend, then this will work
with SSO, otherwise the login window of DokuWiki will appear in the
embedding iframe.

# Installation

- ~install from the app-store~ (not yet)
- install from a (pre-)release tar-ball by extracting it into your app folder
- clone the git repository in to your app folder and run make
  - `make help` will list all targets
  - `make dev` comiles without minification or other assset-size optimizations
  - `make build` will generate optimized assets
  - there are several build-dependencies like compose, node, tar
    ... just try and install all missing tools ;)

# Single Sign On

If DokuWiki and Nextcloud share a common user-base and authentication
scheme then the current user is just silently logged into the
configured DokuWiki instance and later the DokuWiki contents will just
be presented in an IFrame to the user.

## Using LDAP

The idea is here to use LDAP for the authentication for Nextcloud as
well as DokuWiki. In this case the user-names and passwords just
coincide.

It is stiff possible to have "local" accounts for Nextcloud and
DokuWiki, e.g. in order to have an administrator account which is
independent from LDAP in order not to run into a chicken-and-egg
problem.

## Using a DokuWiki Authentication Plugin

There is an experimental DokuWiki auth plugin using Nextcloud as
authentication source. Please refer to the original repository:

https://github.com/santifa/authnc.git

or to my own private fork

https://github.com/rotdrop/authnc

# More Documentation should follow ...
