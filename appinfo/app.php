<?php
/**
 * ownCloud - dokuwiki plugin
 *
 * @author Claus-Justus Heine
 * @copyright 2013 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * Embed a DokuWiki instance into an ownCloud instance by means of an
 * iframe (or maybe slightly more up-to-date: an object) tag.
 *
 * This was inspired by the Roundcube plugin by Martin Reinhardt and
 * David Jaedke, but as that one stores passwords -- and even in a
 * data-base -- and even more or less unencrypted, this is now a
 * complete rewrite.
 *
 * We implement part-of a single-sign-on strategy: when the user logs
 * into the ownCloud instance, we execute a login-hook (which has the
 * passphrase or other credentials readily available) and log into the
 * DokuWiki instance by means of their xmlrpc protocol. The cookies
 * returned by the DokuWiki instance are then simply forwarded the
 * web-browser of the user. Not password information is stored on the
 * host. So this should be as secure or insecure as DokuWiki behaves
 * itself.
 *
 * There are still some issues:
 *
 * - There is already a DokuWiki plugin with that name for
 *   OC. Therefor the slightly longer name DokuWikiEmbed
 * 
 * - DokuWiki stores the user and passphrase in encrypted form in the
 *   Cookies it returns; the password and user is remembered. However,
 *   we actually do not care what the DokuWiki-server presents us. We
 *   simply assume that the cookies it returns "magically" allow to
 *   remember our successful login attempt
 *
 * - Currently there is no log-off xmlrpc call. We probably would like
 *   to add one to DW, have to investigate that. Will probably post
 *   something to the DW mailing list. Maybe one can force log-off by
 *   asserting an invalid login attempt, but with correct session and
 *   credential cookies. Have to check that.
 *
 * - We probably allow a differing user-IDs for OC and DW, but the
 *   passphrases have to be the same. Therefore there is a very short
 *   application db table which stores the mapping, but not the
 *   passphrase (and leave that for later)
 *
 * - There is an additional DW auth-plugin which uses the OC
 *   auth-functions. If that is in use then we have true SSO. An
 *   alternative would be to use the same LDAP back-end for DW and
 *   OC. But we do not care. The task of this app is simply to try to
 *   login with the OC user ID and passphrase into DW. That this
 *   actually works has to be accomplished by other means. So this is
 *   one-half of a SSO implementation.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$appName = 'dokuwikiembed';

$l = new OC_L10N($appName);

OC::$CLASSPATH['DWEMBED\L'] = OC_App::getAppPath($appName) . '/lib/util.php';
OC::$CLASSPATH['DWEMBED\Util'] = OC_App::getAppPath($appName) . '/lib/util.php';
OC::$CLASSPATH['DWEMBED\App'] = OC_App::getAppPath($appName) . '/lib/dokuwikiembed.php';
OC::$CLASSPATH['DWEMBED\AuthHooks'] = OC_App::getAppPath($appName) . '/lib/auth.php';

OCP\Util::connectHook('OC_User', 'post_login', 'DWEMBED\AuthHooks', 'login');
OCP\Util::connectHook('OC_User', 'logout', 'DWEMBED\AuthHooks', 'logout');
OCP\BackgroundJob::AddRegularTask('DWEMBED\AuthHooks', 'refresh');

OCP\App::registerAdmin($appName, 'admin-settings');

// Add global JS routines; this one triggers a session refresh for DW.
OCP\Util::addScript($appName, 'routes');


OCP\App::addNavigationEntry(array(
		'id' => $appName, 
		'order' => 10, 
		'href' => OCP\Util::linkTo($appName, 'index.php'), 
		'icon' => OCP\Util::imagePath($appName, 'dokuwiki-logo-gray.png'),
		'name' => $l->t('DokuWiki')));

?>
