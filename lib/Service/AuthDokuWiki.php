<?php
/**
 * DokuWikiEmbedded -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021, 2022 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * DokuWikiEmbedded is free software: you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * DokuWikiEmbedded is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with DokuWikiEmbedded. If not, see
 * <http://www.gnu.org/licenses/>.
 */

namespace OCA\DokuWikiEmbedded\Service;

use PhpXmlRpc as XmlRpc;
use PhpXmlRpc\PhpXmlRpc as XmlRpcData;

use OCP\Authentication\LoginCredentials\IStore as ICredentialsStore;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\ILogger;
use OCP\IL10N;

use OCA\DokuWikiEmbedded\AppInfo\Application;

class AuthDokuWiki
{
  use \OCA\DokuWikiEmbedded\Traits\LoggerTrait;

  const RPCPATH = '/lib/exe/xmlrpc.php';
  const ON_ERROR_THROW = 'throw'; ///< Throw an exception on error
  const ON_ERROR_RETURN = 'return'; ///< Return boolean on error

  const STATUS_UNKNOWN = 0;
  const STATUS_LOGGED_OUT = -1;
  const STATUS_LOGGED_IN = 1;

  /**
   * Auth Levels
   * @file inc/auth.php
   */
  const AUTH_NONE = 0;
  const AUTH_READ = 1;
  const AUTH_EDIT = 2;
  const AUTH_CREATE = 4;
  const AUTH_UPLOAD = 8;
  const AUTH_DELETE = 16;
  const AUTH_ADMIN = 255;

  /** @var string */
  private $appName;

  /** @var \OCP\IConfig */
  private $config;

  /** @var \OCP\IURLGeneator */
  private $urlGenerator;

  /** @var \OCP\Authentication\LoginCredentials\IStore */
  private $credentialsStore;

  private $dwProto = null;
  private $dwHost = null;
  private $dwPort = null;
  private $dwPath = null;

  private $authHeaders; //!< Authentication headers returned by DokuWiki

  /** @var int */
  private $httpCode;

  /** @var string */
  private $httpStatus;

  /** @var string */
  private $errorReporting;

  /** @var bool */
  private $enableSSLVerify;

  /** @var XmlRpc\Client */
  private $xmlRpcClient;

  /** @var array */
  private $cookies;

  public function __construct(
    Application $app
    , IConfig $config
    , ICredentialsStore $credentialsStore
    , IURLGenerator $urlGenerator
    , ILogger $logger
    , IL10N $l10n
  ) {
    $this->appName = $app->getAppName();
    $this->config = $config;
    $this->credentialsStore = $credentialsStore;
    $this->urlGenerator = $urlGenerator;
    $this->logger = $logger;
    $this->l = $l10n;

    $this->errorReporting = self::ON_ERROR_RETURN;

    $this->enableSSLVerify = $this->config->getAppValue('enableSSLVerfiy', true);

    $location = $this->config->getAppValue($this->appName, 'externalLocation');

    if (!empty($location)) {

        if ($location[0] == '/') {
            $url = $this->urlGenerator->getAbsoluteURL($location);
        } else {
            $url = $location;
        }

        $urlParts = parse_url($url);
        $this->dwProto = $urlParts['scheme'];
        $this->dwHost  = $urlParts['host'];
        $this->dwPort  = isset($urlParts['port']) ? ':'.$urlParts['port'] : '';
        $this->dwPath  = $urlParts['path'];
    }

    /* Construct the xml client control class */
    $this->xmlRpcClient = new XmlRpc\Client($this->wikiURL() . self::RPCPATH);
    $this->xmlRpcClient->setSSLVerifyHost($this->enableSSLVerify);
    $this->xmlRpcClient->setSSLVerifyPeer($this->enableSSLVerify);

    $this->cookies = [];

    // Forward any received DW cookies from the client to our XML RPC
    // calls. This uses the cookie-storage of the web-client.
    foreach ($_COOKIE as $cookie => $value) {
      if (preg_match('/^(DokuWiki|DW).*/', $cookie)) {
        $this->xmlRpcClient->setCookie($cookie, $value);
      }
    }

    $this->httpCode = -1;
    $this->httpStatus = '';
  }


  /**
   * Return the name of the app.
   */
  public function getAppName(): string
  {
    return $this->appName;
  }

  /**
   * Modify how errors are handled.
   *
   * @param string $how One of self::ON_ERROR_THROW or
   * self::ON_ERROR_RETURN or null (just return the current
   * reporting).1
   *
   * @return string Currently active error handling policy.
   */
  public function errorReporting($how = null)
  {
    $reporting = $this->errorReporting;
    switch ($how) {
      case null:
        break;
      case self::ON_ERROR_THROW:
      case self::ON_ERROR_RETURN:
        $this->errorReporting = $how;
        break;
      default:
        throw new \Exception('Unknown error-reporting method: '.$how);
    }
    return $reporting;
  }

  private function handleError($msg, $t = null)
  {
    switch ($this->errorReporting) {
    case self::ON_ERROR_THROW:
      throw new \Exception($msg, !empty($t) ? $t->getCode() : 0, $t);
    case self::ON_ERROR_RETURN:
      if (!empty($t)) {
        $this->logException($t, $msg);
      } else {
        $this->logError($msg);
      }
      return false;
    default:
      throw new \Exception("Invalid error handling method: ".$this->errorReporting);
    }
    return false;
  }

  /**
   * Return the URL for use with an iframe or object tag
   */
  public function wikiURL()
  {
    return $this->dwProto.'://'.$this->dwHost.$this->dwPort.$this->dwPath;
  }

  private function cleanCookies()
  {
    $this->cookies = [];
    foreach ($_COOKIE as $cookie => $value) {
      if (preg_match('/^(DokuWiki|DW).*/', $cookie)) {
        unset($_COOKIE[$cookie]);
      }
    }
  }

  /**
   * Issue an RPC XML request to the configured DokuWiki instance.
   */
  public function xmlRequest($method, $data = [])
  {
    $t = null;
    try {
      $result = $this->doXmlRequest($method, $data);
    } catch (\Throwable $t1) {
      $t = $t1;
      $result = false;
    }
    if ($result === false) {
      if ($this->httpCode == 401) {
        try {
          $credentials = $this->loginCredentials();
          if ($this->_login($credentials['userId'], $credentials['password'])) {
            $this->logInfo("Re-login succeeded");
            foreach ($this->cookies as $cookie) {
              if ($cookie['value'] == 'deleted') {
                continue;
              }
              $this->xmlRpcClient->setCookie($cookie['name'], $cookie['value']);
            }
            return $this->doXmlRequest($method, $data);
          }
        } catch (\Throwable $t1) {
          $t = $t1;
        }
      }
      return $this->handleError("xmlRequest($method) failed ($this->httpCode)", $t);
    }
    return $result;
  }

  /**
   * Issue an RPC XML request to the configured DokuWiki instance.
   */
  private function doXmlRequest($method, $data = [])
  {
    $request = new XmlRpc\Request($method, (new XmlRpc\Encoder)->encode($data));
    $response = $this->xmlRpcClient->send($request);

    if ($response->faultCode() != 0) {
      if ($response->faultCode() == XmlRpcData::$xmlrpcerr['http_error']) {
        // unfortunately, the current version does not provide the http
        // error code
        preg_match('/\(([^\)]+)\)/', $response->faultString(), $matches);
        list(,$this->httpCode, $this->httpStatus) = explode(' ', $matches[1], 3);
      } else {
        $this->httpCode = -1;
        $this->httpStatus = '';
      }
      return $this->handleError('XMLRPC request failed: ' . $response->faultString());
    }

    // ok, we got a valid response
    $decodedResponse = (new XmlRpc\Encoder)->decode($response->value());

    if ($method == "dokuwiki.login" ||
        $method == "dokuwiki.stickylogin" ||
        $method == "plugin.remoteauth.stickyLogin" ||
        $method == "dokuwiki.logoff") {
      // Response _should_ be a single integer: if 0, login
      // unsuccessful, if 1: got it.
      if ($decodedResponse == 1) {
        // Store and duplicate set cookies for forwarding to the users web client
        $this->cookies = [];
        foreach ($response->cookies() as $cookieName => $cookieInfo) {
          if ($cookieName !== 'DokuWiki' && strpos($cookieName, 'DW') !== 0) {
            continue;
          }
          $cookieInfo['name'] = $cookieName;
          $cookieInfo[$cookieName] = $cookieInfo['value'];
          ksort($cookieInfo);
          $this->cookies[] = $cookieInfo;
          $cookieInfo['path'] = \OC::$WEBROOT;
          $this->cookies[] = $cookieInfo;
        }
        $this->logDebug("XMLRPC method \"$method\" executed with success. Got cookies "
                        . print_r($this->cookies, true));
        return true;
      } else {
        $this->logDebug("XMLRPC method \"$method\" to \"" . ($this->wikiURL() . self::RPCPATH) . "\" failed. Got Cookies "
                        . print_r($response->cookies(), true));
        return false;
      }
    }

    return $decodedResponse;
  }

  /**
   * Try to obtain login-credentials from Nextcloud credentials store.
   *
   * @return array|bool
   * ```
   * [
   *   'userId' => USER_ID,
   *   'password' => PASSWORD,
   * ]
   * ```
   */
  private function loginCredentials()
  {
    try {
      $credentials = $this->credentialsStore->getLoginCredentials();
      return [
        'userId' => $credentials->getUID(),
        'password' => $credentials->getPassword(),
      ];
    } catch (\Throwable $t) {
      return $this->handleError("Unable to obtain login-credentials", $t);
    }
  }

  /**
   * Perform the login by means of a RPCXML call and stash the cookies
   * storing the credentials away for later; the cookies are
   * re-emitted to the users web-client when the OC wiki-app is
   * activated. This login function itself is only meant for being
   * called during the login process.
   *
   * @param null|$username Login name
   *
   * @param null|$password credentials
   *
   * @return bool true if successful, false otherwise.
   */
  public function login($userName = null, $password = null)
  {
    if ($userName === null && $password === null) {
      $credentials = $this->loginCredentials();
      $userName = $credentials['userId'];
      $password = $credentials['password'];
    }
    return $this->_login($userName, $password);
  }

  private function _login($username, $password)
  {
    $this->cleanCookies();
    $result = $this->xmlRequest("plugin.remoteauth.stickyLogin", [ $username, $password ]);
    if ($result !== true) {
      // Fall back to "normal" login if long-life token could not be aquired.
      $result = $this->xmlRequest("dokuwiki.login", [ $username, $password ]);
      if ($result !== true) {
        return false;
      }
    }

    foreach ($this->cookies as $cookie) {
      if ($cookie['value'] == 'deleted') {
        continue;
      }
      $this->xmlRpcClient->setCookie($cookie['name'], $cookie['value']);
    }

    return true;
  }

  /**
   * Logoff from DokuWiki with added XMLRPC dokuwiki.logoff
   * call. For this to work we have to send the DokuWiki cookies
   * alongside the XMLRPC request.
   */
  public function logout()
  {
    return $this->doXmlRequest("dokuwiki.logoff");
  }

  /**
   * Fetch the version from the DW instance in the hope that this also
   * touches the session life-time.
   */
  public function version()
  {
    return $this->doXmlRequest("dokuwiki.getVersion");
  }

  /**
   * Ping the external application in order to extend its login
   * session.
   */
  public function refresh()
  {
    return $this->getPage('');
  }

  /**
   * Rather a support function in case some other app wants to create
   * some automatic wiki-pages (e.g. overview stuff and the like,
   * maybe a changelog here and a readme there.
   */
  public function putPage($pagename, $pagedata, $attr = [])
  {
    return $this->xmlRequest("wiki.putPage", [ $pagename, $pagedata, $attr ]);
  }

  /**
   * Rather a support function in case some other app wants to create
   * some automatic wiki-pages (e.g. overview stuff and the like,
   * maybe a changelog here and a readme there.
   */
  public function getPage(string $pagename, ?int $version = null)
  {
    if (!empty($version)) {
      return $this->xmlRequest('wiki.getPageVersion', [ $pagename, $version ]);
    } else {
      return $this->xmlRequest("wiki.getPage", [ $pagename ]);
    }
  }

  public function getPageVersions($pagename)
  {
    return $this->xmlRequest('wiki.getPageVersions', [ $pagename ]);
  }

  public function getPageInfo($pagename)
  {
    return $this->xmlRequest('wiki.getPageInfo', [ $pagename ]);
  }

  /**
   * Add an ACL rule
   *
   * @param string $scope A page or namespace, use `NAMESPACE:*` to
   * grant access to an entire name-space, otherwise just the full
   * path to the target page.
   *
   * @param string $who User or group. Use `@GROUP` to define a rule
   * for a group.
   *
   * @param int $what One of
   * `self::AUTH_NONE`,
   * `self::AUTH_READ`,
   * `self::AUTH_EDIT`,
   * `self::AUTH_CREATE`,
   * `self::AUTH_UPLOAD`,
   * `self::AUTH_DELETE`,
   * `self::AUTH_ADMIN`.
   *
   * @return mixed Response from the RPC call, false on error.
   */
  public function addAcl($scope, $who, int $what)
  {
    return $this->xmlRequest('plugin.acl.addAcl', [ $scope, $who, $what ]);
  }

  /**
   * Delete an ACL rule, see self::addACL().
   */
  public function delAcl($scope, $who)
  {
    return $this->xmlRequest('plugin.acl.delAcl', [ $scope, $who ]);
  }

  /**
   * List all ACLs.
   */
  public function listAcls()
  {
    return $this->xmlRequest('plugin.acl.listAcls');
  }

  /**
   * Parse a cookie header in order to obtain name, date of
   * expiry and path.
   *
   * @parm cookieHeader Guess what
   *
   * @return array
   * Array with name, value, expires and path fields, or
   * false if $cookie was not a Set-Cookie header.
   *
   */
  private function parseCookie($header)
  {
    $count = 0;
    $cookieString = preg_replace('/^Set-Cookie: /i', '', trim($header), -1, $count);
    if ($count != 1) {
      return false;
    }
    $cookie = [];
    $cookieValues = explode(';', $cookieString);
    foreach ($cookieValues as $field) {
      $cookieInfo = explode('=', $field);
      $key = trim($cookieInfo[0]);
      $value = count($cookieInfo) == 2 ? trim($cookieInfo[1]) : null;
      if (empty($cookie)) {
        $cookie['name'] = $key;
        $cookie['value'] = $value;
      }
      $cookie[$key] = $value;
    }
    ksort($cookie);
    return $cookie;
  }

  /**
   * Normally, we do NOT want to replace cookies, we need two
   * paths: one for the RC directory, one for the OC directory
   * path. However: NGINX (a web-server software) on some
   * systems has a header limit of 4k, which is not much. At
   * least, if one tries to embed several web-applications into
   * the cloud by the same techniques which are executed here.
   *
   * This function tries to reduce the header size by replacing
   * cookies with the same name and path, but adding a new
   * cookie if name or path differs.
   *
   * @param array cookie Cookie info with NAME => VALUE pairs
   * where the cookie-name is one of the array-keys.
   *
   * @todo This probably should go into the Middleware as
   * afterController() and add the headers there.
   */
  private function addCookie($thisCookie)
  {
    $found = false;
    foreach (headers_list() as $header) {
      $cookie = $this->parseCookie($header);
      if ($cookie === $thisCookie) {
        return;
      }
    }
    $this->logDebug("Emitting cookie " . print_r($thisCookie, true));
    setcookie(
      $thisCookie['name'],
      $thisCookie['value'],
      empty($thisCookie['expires']) ? 0 : strtotime($thisCookie['expires']),
      $thisCookie['path']??'',
      $thisCookie['domain']??'',
      isset($thisCookie['secure']),
      isset($thisCookie['HttpOnly'])
    );
  }

  /**
   * Send authentication headers previously aquired
   */
  public function emitAuthHeaders()
  {
    foreach ($this->cookies as $cookie) {
      $this->addCookie($cookie);
    }
  }
};

// Local Variables: ***
// c-basic-offset: 2 ***
// indent-tabs-mode: nil ***
// End: ***
