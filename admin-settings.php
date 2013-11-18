<?php

/**Embed a DokuWiki instance as app into ownCloud, intentionally with
 * single-sign-on.
 * 
 * @author Claus-Justus Heine
 * @copyright 2013 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU GENERAL PUBLIC LICENSE
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
 */

use DWEMBED\App;
use DWEMBED\Util;
use DWEMBED\L;

$appName = App::APPNAME;

OCP\User::checkAdminUser();

OCP\Util::addScript($appName, "dokuwikiembed");
OCP\Util::addScript($appName, "admin-settings");

OCP\Util::addStyle($appName, "admin-settings");

$tmpl = new OCP\Template($appName, 'admin-settings');

$tmpl->assign('wikilocation', OCP\Config::GetAppValue($appName, 'wikilocation', ''));

return $tmpl->fetchPage();
