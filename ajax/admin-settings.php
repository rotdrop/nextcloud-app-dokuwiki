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
use DWEMBED\L;
use DWEMBED\Util;

$appName = App::APPNAME;

OCP\User::checkAdminUser();
OCP\JSON::callCheck();

if (isset($_POST['DW_Location'])) {
  $location = trim($_POST['DW_Location']);
  \OC_AppConfig::setValue($appName, 'wikilocation', $location);

  // TODO: insert checks here which check whether the DokuWiki can
  // indeed be found at this location and report possible success and
  // error states back to the user
  if ($location == '') {
    $message = L::t("Got an empty wiki location: `%s'.", array($location));  
  } else if (!Util::URLIsValid($location)) {
    $message = L::t("Setting wiki location to `%s' but the location seems to be invalid.",
                    array($location));
  } else {
    $message = L::t("Setting wiki location to `%s'.", array($location));
  }
  
  OC_JSON::success(array("data" => array("message" => $message)));

  return true;
}

OC_JSON::error(
  array("data" => array("message" => L::t('Unknown request.'))));

return false;

