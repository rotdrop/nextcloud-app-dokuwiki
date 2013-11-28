capp-dokuwikiembed
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

The following patch to DokuWiki is needed for a clean logout, and in
order to avoid infinite recursion in combination with the authowncloud
plugin for DW.

==============================================================
```
diff --git a/inc/RemoteAPICore.php b/inc/RemoteAPICore.php
index 2eb8ea4..c676105 100644
--- a/inc/RemoteAPICore.php
+++ b/inc/RemoteAPICore.php
@@ -3,7 +3,7 @@
 /**
  * Increased whenever the API is changed
  */
-define('DOKU_API_VERSION', 8);
+define('DOKU_API_VERSION', 10);
 
 class RemoteAPICore {
 
@@ -24,6 +24,15 @@ class RemoteAPICore {
                 'return' => 'int',
                 'doc' => 'Tries to login with the given credentials and sets auth cookies.',
                 'public' => '1'
+            ), 'dokuwiki.stickylogin' => array(
+                'args' => array('string', 'string'),
+                'return' => 'int',
+                'doc' => 'Tries to login with the given credentials and sets auth cookies.',
+                'public' => '1'
+            ), 'dokuwiki.logoff' => array(
+                'args' => array(),
+                'return' => 'int',
+                'doc' => 'Tries to logoff by expiring auth cookies and the associated PHP session.'
             ), 'dokuwiki.getPagelist' => array(
                 'args' => array('string', 'array'),
                 'return' => 'array',
@@ -767,6 +776,40 @@ class RemoteAPICore {
         return $ok;
     }
 
+    function stickylogin($user,$pass){
+        global $conf;
+        global $auth;
+        if(!$conf['useacl']) return 0;
+        if(!$auth) return 0;
+
+        @session_start(); // reopen session for login
+        if($auth->canDo('external')){
+            $ok = $auth->trustExternal($user,$pass,false);
+        }else{
+            $evdata = array(
+                'user'     => $user,
+                'password' => $pass,
+                'sticky'   => true,
+                'silent'   => true,
+            );
+            $ok = trigger_event('AUTH_LOGIN_CHECK', $evdata, 'auth_login_wrapper');
+        }
+        session_write_close(); // we're done with the session
+
+        return $ok;
+    }
+
+    function logoff(){
+        global $conf;
+        global $auth;
+        if(!$conf['useacl']) return 0;
+        if(!$auth) return 0;
+        
+        auth_logoff();
+
+        return 1;
+    }
+
     private function resolvePageId($id) {
         $id = cleanID($id);
         if(empty($id)) {
diff --git a/inc/auth.php b/inc/auth.php
index b793f5d..2b3fd4f 100644
--- a/inc/auth.php
+++ b/inc/auth.php
@@ -263,8 +263,12 @@ function auth_login($user, $pass, $sticky = false, $silent = false) {
             return auth_login($user, $pass, $sticky, true);
         }
     }
-    //just to be sure
-    auth_logoff(true);
+    // Bad idea in the presence of cross-dependent plugins. Only try
+    // to log-off if either $user is set or there are cookies to invalidate
+    if($user) {
+        //just to be sure
+        auth_logoff(true);
+    }
     return false;
 }

```
