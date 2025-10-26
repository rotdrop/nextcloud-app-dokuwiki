<?php
/**
 * Nextcloud DokuWiki -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020-2025 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 *
 * Nextcloud DokuWiki is free software: you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * Nextcloud DokuWiki is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with Nextcloud DokuWiki. If not, see
 * <http://www.gnu.org/licenses/>.
 */

namespace OCA\DokuWiki\Service;

use Throwable;
use Exception;
use PhpXmlRpc as XmlRpc;
use PhpXmlRpc\PhpXmlRpc as XmlRpcData;

use OCP\Authentication\LoginCredentials\IStore as ICredentialsStore;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\IRequest;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface as ILogger;
use OCP\IL10N;

use OCA\DokuWiki\AppInfo\Application;

/** Authenticator work-horse. */
class AuthDokuWiki
{
  use \OCA\DokuWiki\Toolkit\Traits\LoggerTrait;

  const RPCPATH = '/lib/exe/xmlrpc.php';
  const ON_ERROR_THROW = 'throw'; ///< Throw an exception on error
  const ON_ERROR_RETURN = 'return'; ///< Return boolean on error

  const STATUS_UNKNOWN = 0;
  const STATUS_LOGGED_OUT = -1;
  const STATUS_LOGGED_IN = 1;

  /**
   * Auth Levels
   */
  const AUTH_NONE = 0;
  const AUTH_READ = 1;
  const AUTH_EDIT = 2;
  const AUTH_CREATE = 4;
  const AUTH_UPLOAD = 8;
  const AUTH_DELETE = 16;
  const AUTH_ADMIN = 255;

  private const RPC_AUTH_METHODS = [
    // current API methods
    'core.login',
    'core.logoff',
    // legacy methods
    'dokuwiki.login',
    'dokuwiki.logoff',
    // sticky login plugin
    'plugin.remoteauth.stickyLogin',
  ];

  private const RPC_LOGIN_METHODS = [
    // sticky login plugin
    'plugin.remoteauth.stickyLogin',
    // current API methods
    'core.login',
    // legacy methods
    'dokuwiki.login',
  ];

  private const RPC_LOGOUT_METHODS = [
    // current API methods
    'core.logoff',
    // legacy methods
    'dokuwiki.logoff',
  ];

  /** @var string */
  private $appName;

  private $dwProto = null;
  private $dwHost = null;
  private $dwPort = null;
  private $dwPath = null;

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

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    Application $app,
    private IAppConfig $appConfig,
    private IRequest $request,
    private ICredentialsStore $credentialsStore,
    private IURLGenerator $urlGenerator,
    protected ILogger $logger,
  ) {
    $this->appName = $app->getAppName();

    $this->errorReporting = self::ON_ERROR_RETURN;

    $this->enableSSLVerify = $this->appConfig->getValueBool($this->appName, 'enableSSLVerify', true);

    $location = $this->appConfig->getValueString($this->appName, 'externalLocation');

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
      $this->dwPath  = $urlParts['path'] ?? '';
    }

    /* Construct the xml client control class */
    $this->xmlRpcClient = new XmlRpc\Client($this->wikiURL() . self::RPCPATH);
    $this->xmlRpcClient->setOption(XmlRpc\Client::OPT_VERIFY_HOST, $this->enableSSLVerify ? 2 : 0);
    $this->xmlRpcClient->setOption(XmlRpc\Client::OPT_VERIFY_PEER, $this->enableSSLVerify);

    $this->cookies = [];

    // Forward any received DW cookies from the client to our XML RPC
    // calls. This uses the cookie-storage of the web-client.
    foreach ($this->request->cookies as $cookie => $value) {
      if (preg_match('/^(DokuWiki|DW).*/', $cookie)) {
        $this->xmlRpcClient->setCookie($cookie, $value);
      }
    }

    $this->httpCode = -1;
    $this->httpStatus = '';
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
   * Return the name of the app.
   *
   * @return string
   */
  public function getAppName():string
  {
    return $this->appName;
  }

  /**
   * Modify how errors are handled.
   *
   * @param null|string $how One of self::ON_ERROR_THROW or
   * self::ON_ERROR_RETURN or null (just return the current
   * reporting).
   *
   * @return string Currently active error handling policy.
   */
  public function errorReporting(?string $how = null):string
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
        throw new Exception('Unknown error-reporting method: '.$how);
    }
    return $reporting;
  }

  /**
   * @param string $msg
   *
   * @param null|Throwable $throwable
   *
   * @return bool
   */
  private function handleError(string $msg, ?Throwable $throwable = null):bool
  {
    switch ($this->errorReporting) {
      case self::ON_ERROR_THROW:
        throw new Exception($msg, !empty($throwable) ? $throwable->getCode() : 0, $throwable);
      case self::ON_ERROR_RETURN:
        if (!empty($throwable)) {
          $this->logException($throwable, $msg);
        } else {
          $this->logError($msg);
        }
        return false;
      default:
        throw new Exception('Invalid error handling method: '.$this->errorReporting);
    }
    return false;
  }

  /**
   * Return the URL for use with an iframe or object tag
   *
   * @return string
   */
  public function wikiURL():string
  {
    return $this->dwProto.'://'.$this->dwHost.$this->dwPort.$this->dwPath;
  }

  /** @return void */
  private function cleanCookies():void
  {
    $this->cookies = [];
  }

  /**
   * Issue an RPC XML request to the configured DokuWiki instance.
   *
   * @param string $method
   *
   * @param array $data
   *
   * @return mixed
   */
  public function xmlRequest(string $method, array $data = []):mixed
  {
    $t = null;
    try {
      $result = $this->doXmlRequest($method, $data);
    } catch (Throwable $t1) {
      $t = $t1;
      $result = false;
    }
    if ($result === false) {
      if ($this->httpCode == 401) {
        try {
          $credentials = $this->loginCredentials();
          if ($this->doLogin($credentials['userId'], $credentials['password'])) {
            $this->logInfo('Re-login succeeded, cookies ' . print_r($this->cookies, true));
            foreach ($this->cookies as $cookie) {
              if ($cookie['value'] == 'deleted') {
                continue;
              }
              $this->xmlRpcClient->setCookie($cookie['name'], $cookie['value']);
            }
            return $this->doXmlRequest($method, $data);
          }
        } catch (Throwable $t1) {
          $t = $t1;
        }
      }
      return $this->handleError('xmlRequest(' . $method . ') failed (' . $this->httpCode . ')', $t);
    }
    return $result;
  }

  /**
   * Issue an RPC XML request to the configured DokuWiki instance.
   *
   * @param string $method
   *
   * @param array $data
   *
   * @return mixed
   */
  private function doXmlRequest(string $method, array $data = []):mixed
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
      return $this->handleError('XMLRPC request "' . $method. '" failed: ' . $response->faultString() . ' CODE ' . $this->httpCode . ' STATUS ' . $this->httpStatus);
    }
    $this->httpCode = 200;
    $this->httpStatus = 'OK';

    // ok, we got a valid response
    $decodedResponse = (new XmlRpc\Encoder)->decode($response->value());

    if (in_array($method, self::RPC_AUTH_METHODS)) {
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
          $cookieInfo['path'] = \OC::$WEBROOT == '' ? '/' : \OC::$WEBROOT;
          $this->cookies[] = $cookieInfo;
        }
        $this->logDebug('XMLRPC method "$method" executed with success. Got cookies '
                        . print_r($this->cookies, true));
        return true;
      } else {
        $this->logDebug('XMLRPC method "$method" to "' . ($this->wikiURL() . self::RPCPATH) . '" failed. Got Cookies '
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
    } catch (Throwable $t) {
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
   * @param null|string $userName Login name.
   *
   * @param null|string $password credentials.
   *
   * @return bool true if successful, false otherwise.
   */
  public function login(?string $userName = null, ?string $password = null):bool
  {
    if ($userName === null && $password === null) {
      $credentials = $this->loginCredentials();
      $userName = $credentials['userId'];
      $password = $credentials['password'];
    }
    return $this->doLogin($userName, $password);
  }

  /**
   * @param null|string $username Login name.
   *
   * @param null|string $password credentials.
   *
   * @return bool true if successful, false otherwise.
   */
  private function doLogin($username, $password):bool
  {
    $this->cleanCookies();
    foreach (self::RPC_LOGIN_METHODS as $method) {
      try {
        $result = $this->doXmlRequest($method, [ $username, $password ]);
      } catch (Throwable $t) {
        $this->logException($t);
        $result = false;
      }
      if ($result === true) {
        foreach ($this->cookies as $cookie) {
          if ($cookie['value'] == 'deleted') {
            continue;
          }
          $this->xmlRpcClient->setCookie($cookie['name'], $cookie['value']);
        }
        return true;
      }
    }
    return false;
  }

  /**
   * Logoff from DokuWiki with added XMLRPC dokuwiki.logoff
   * call. For this to work we have to send the DokuWiki cookies
   * alongside the XMLRPC request.
   *
   * @return bool
   */
  public function logout():bool
  {
    foreach (self::RPC_LOGOUT_METHODS as $method) {
      if ($this->doXmlRequest($method) === true) {
        return true;
      }
    }
    return false;
  }

  /**
   * Fetch the version from the DW instance in the hope that this also
   * touches the session life-time.
   *
   * @return mixed
   */
  public function version():mixed
  {
    return $this->xmlRequest("dokuwiki.getVersion");
  }

  /**
   * Ping the external application in order to extend its login
   * session.
   *
   * @return mixed
   */
  public function refresh():mixed
  {
    return $this->version();
  }

  /**
   * Rather a support function in case some other app wants to create
   * some automatic wiki-pages (e.g. overview stuff and the like,
   * maybe a changelog here and a readme there.
   *
   * @param string $pagename
   *
   * @param string $pagedata
   *
   * @param array $attr
   *
   * @return mixed
   */
  public function putPage(string $pagename, string $pagedata, array $attr = []):mixed
  {
    return $this->xmlRequest("wiki.putPage", [ $pagename, $pagedata, $attr ]);
  }

  /**
   * Rather a support function in case some other app wants to create
   * some automatic wiki-pages (e.g. overview stuff and the like,
   * maybe a changelog here and a readme there.
   *
   * @param string $pagename
   *
   * @param null|int $version
   *
   * @return mixed
   */
  public function getPage(string $pagename, ?int $version = null):mixed
  {
    if (!empty($version)) {
      return $this->xmlRequest('wiki.getPageVersion', [ $pagename, $version ]);
    } else {
      return $this->xmlRequest("wiki.getPage", [ $pagename ]);
    }
  }

  /**
   * Rather a support function in case some other app wants to create
   * some automatic wiki-pages (e.g. overview stuff and the like,
   * maybe a changelog here and a readme there.
   *
   * @param string $oldName
   *
   * @param string $newName
   *
   * @return mixed
   */
  public function renamePage(string $oldName, string $newName):mixed
  {
    return $this->xmlRequest('plugin.move.renamePage', [ $oldName, $newName ]);
  }

  /**
   * @param string $pagename
   *
   * @return mixed
   */
  public function getPageVersions(string $pagename):mixed
  {
    return $this->xmlRequest('wiki.getPageVersions', [ $pagename ]);
  }

  /**
   * @param string $pagename
   *
   * @return mixed
   */
  public function getPageInfo(string $pagename):mixed
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
  public function addAcl(string $scope, string $who, int $what):mixed
  {
    return $this->xmlRequest('plugin.acl.addAcl', [ $scope, $who, $what ]);
  }

  /**
   * Delete an ACL rule, see self::addACL().
   *
   * @param string $scope A page or namespace, use `NAMESPACE:*` to
   * grant access to an entire name-space, otherwise just the full
   * path to the target page.
   *
   * @param string $who User or group. Use `@GROUP` to define a rule
   * for a group.
   *
   * @return mixed Response from the RPC call, false on error.
   */
  public function delAcl(string $scope, string $who):mixed
  {
    return $this->xmlRequest('plugin.acl.delAcl', [ $scope, $who ]);
  }

  /**
   * List all ACLs.
   *
   * @return mixed
   */
  public function listAcls():mixed
  {
    return $this->xmlRequest('plugin.acl.listAcls');
  }

  /**
   * Parse a cookie header in order to obtain name, date of
   * expiry and path.
   *
   * @param string $header Guess what.
   *
   * @return null|array
   * Array with name, value, expires and path fields, or
   * false if $cookie was not a Set-Cookie header.
   */
  private function parseCookie(string $header):?array
  {
    $count = 0;
    $cookieString = preg_replace('/^Set-Cookie: /i', '', trim($header), -1, $count);
    if ($count != 1) {
      return null;
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
   * @param array $thisCookie Cookie info with NAME => VALUE pairs
   * where the cookie-name is one of the array-keys.
   *
   * @return void
   *
   * @todo This probably should go into the Middleware as
   * afterController() and add the headers there.
   */
  private function addCookie(array $thisCookie):void
  {
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
   * Send authentication headers previously aquired.
   *
   * @return void
   */
  public function emitAuthHeaders():void
  {
    foreach ($this->cookies as $cookie) {
      $this->addCookie($cookie);
    }
  }
}
