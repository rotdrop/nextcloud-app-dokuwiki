ocapp-dokuwikiembed
===================

OwnCloud "app" which embeds an existing DokuWiki instance into
OwnCloud. Intended for SSO. Inspired by RoundCube-app, but does not
store any pasword :)

More detailed thoughts:

ownCloud - dokuwiki plugin

@author Claus-Justus Heine
@copyright 2013 Claus-Justus Heine <himself@claus-justus-heine.de>

Embed a DokuWiki instance into an ownCloud instance by means of an
iframe (or maybe slightly more up-to-date: an object) tag.

This was inspired by the Roundcube plugin by Martin Reinhardt and
David Jaedke, but as that one stores passwords -- and even in a
data-base -- and even more or less unencrypted, this is now a
complete rewrite.

We implement part-of a single-sign-on strategy: when the user logs
into the ownCloud instance, we execute a login-hook (which has the
passphrase or other credentials readily available) and log into the
DokuWiki instance by means of their xmlrpc protocol. The cookies
returned by the DokuWiki instance are then simply forwarded the
web-browser of the user. No password information is stored on the
host. So this should be as secure or insecure as DokuWiki behaves
itself.

There are still some issues:

- There is already a DokuWiki plugin with that name for
  OC. Therefor the slightly longer name DokuWikiEmbed

- DokuWiki stores the user and passphrase in encrypted form in the
  Cookies it returns; the password and user is remembered. However,
  we actually do not care what the DokuWiki-server presents us. We
  simply assume that the cookies it returns "magically" allow to
  remember our successful login attempt

- Currently there is no log-off xmlrpc call. However, it is easy to
  add one. I have contacted the mailing list and will see what comes
  out of it.

- We only allow the case when each OC user correspond to one DW user
  with the same name and password. User ids and passwords are by no
  means remembered by this app. We simply forward the auth call to DW
  by the post-login hook and the logout attempt via the logout hook.

- There is an additional DW auth-plugin which uses the OC
  auth-functions. If that is in use then we have true SSO. An
  alternative would be to use the same LDAP back-end for DW and
  OC. But we do not care. The task of this app is simply to try to
  login with the OC user ID and passphrase into DW. That this
  actually works has to be accomplished by other means. So this is
  one-half of a SSO implementation.

