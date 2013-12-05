<?php
/**@author Claus-Justus Heine
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


/**DWEMBED namespace to prevent name-collisions.
 */
namespace DWEMBED
{

/**Support for internationalization.
 */
class L
{
  private static $l = false;

  /**Print the translated text.
   *
   * @param[in] $text Text to print, is finally passed to vsprintf().
   *
   * @param[in] $parameters Defaults to an empty array. @a $parameters
   * are passed on to vsprintf().
   *
   * @return The possibly translated message.
   */
  public static function t($text, $parameters = array())
  {
    if (self::$l === false) {
      self::$l = \OC_L10N::get('dokuwikiembed');
 
      // If I omit the next line then the first call to $l->t()
      // generates a spurious new-line. Why?
      //
      // Mea Culpa: don't include a new-line after end tag
      //strval(self::$l->t('blah'));
    }
    return self::$l->t($text, $parameters);
  }
};

/**Ajax specific support class. */
class Ajax
{
  public static function bailOut($msg, $tracelevel = 1, $debuglevel = \OCP\Util::ERROR)
  {
    \OCP\JSON::error(array('data' => array('message' => $msg)));
    self::debug($msg, $tracelevel, $debuglevel);
    exit();
  }
  
  public static function debug($msg, $tracelevel = 0, $debuglevel = \OCP\Util::DEBUG)
  {
    if (PHP_VERSION >= "5.4") {
      $call = debug_backtrace(false, $tracelevel+1);
    } else {
      $call = debug_backtrace(false);
    }
    
    $call = $call[$tracelevel];
    if ($debuglevel !== false) {
      \OCP\Util::writeLog(Config::APP_NAME,
                          $call['file'].'. Line: '.$call['line'].': '.$msg,
                          $debuglevel);
    }
  }
};

class Util
{
  public static function composeURL($location)
  {
    // Assume an absolute location w.r.t. to SERVERROOT
    if ($location[0] == '/') {
      $location = \OC_Helper::makeURLAbsolute($location);
    }
    return $location;
  }

  /**Try to verify a given location up to some respect ...
   *
   * @param[in] $location Either an "absolute" path relative to the
   * server root, starting with '/', or a valid HTML URL.
   */
  public static function URLIsValid($location)
  {
    $location = self::composeURL($location);
    
    \OCP\Util::writeLog(App::APPNAME, "Checking ".$location, LOG_DEBUG);

    // Don't try to access it if it is not a valid URL
    if (filter_var($location, FILTER_VALIDATE_URL) === false) {
      return false;
    }
    
    return true;
  }

  public static function cgiValue($key, $default=null, $allowEmpty = true)
  {
    $value = $default;
    if (isset($_POST["$key"])) {
      $value = $_POST["$key"];
    } elseif (isset($_GET["$key"])) {
      $value = $_GET["$key"];
    }
    if (!$allowEmpty && !is_null($default) && $value == '') {
      $value = $default;
    }
    return $value;
  }

};

} // NAMESPACE

?>
