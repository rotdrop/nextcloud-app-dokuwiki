<?php
/**
 * DokuWikiEmbedded -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  private $reqHeaders;  //!< Authentication headers, cookies we send to DW

  /** @var int */
  private $httpCode;

  /** @var string */
  private $httpStatus;

  /** @var string */
  private $errorReporting;

  /** @var bool */
  private $enableSSLVerify;

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

    $this->authHeaders = [];

    // If we have cookies with AuthData, then store them in authHeaders
    $this->reqHeaders = [];
    foreach ($_COOKIE as $cookie => $value) {
      if (preg_match('/^(DokuWiki|DW).*/', $cookie)) {
        $this->reqHeaders[] = "$cookie=".urlencode($value);
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

  private function handleError($msg)
  {
    switch ($this->errorReporting) {
      case self::ON_ERROR_THROW:
        throw new \Exception($msg);
      case self::ON_ERROR_RETURN:
        $this->logError($msg);
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
    $this->authHeaders = [];
    $this->reqHeaders = [];
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
    } catch (\Throwable $t) {
      $this->logException($t);
      $result = false;
    }
    if ($result === false) {
      if ($this->httpCode == 401) {
        $this->logInfo("CODE: ".$this->httpCode);
        try {
          $credentials = $this->loginCredentials();
          if ($this->login($credentials['userId'], $credentials['password'])) {
            $this->logInfo("Re-login succeeded");
            return $this->doXmlRequest($method, $data);
          }
        } catch (\Throwable $t) {
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
    // Generate the request
    $request = xmlrpc_encode_request($method, $data, [ "encoding" => "UTF-8",
                                                       "escaping" => "markup",
                                                       "version" => "xmlrpc" ]);
    // Construct the header with any relevant cookies
    $httpHeader = "Content-Type: text/xml; charset=UTF-8".
                  (empty($this->reqHeaders)
                 ? ""
                 : "\r\n"."Cookie: ".join("; ", $this->reqHeaders));

    // Compose the context with method, headers and data
    $context = stream_context_create([
      'http' => [
        'method' => 'POST',
        'header' => $httpHeader,
        'content' => $request,
      ],
      'ssl' => [
        'verify_peer' => $this->enableSSLVerify,
        'verify_peer_name' => $this->enableSSLVerify,
      ],
    ]);
    $url  = $this->wikiURL().self::RPCPATH;

    $this->httpCode = -1;
    $fp = fopen($url, 'rb', false, $context);
    $responseHdr = $http_response_header??[];
    if (count($responseHdr) > 0) {
      list(,$this->httpCode, $this->httpStatus) = explode(' ', $responseHdr[0], 3);
    } else {
      $this->httpCode = -1;
      $this->httpStatus = '';
    }
    if ($fp !== false) {
      $result = stream_get_contents($fp);
      fclose($fp);
    } else {
      $error = error_get_last();
      return $this->handleError(
        "URL fopen to $url failed: "
        .print_r($error, true)
        .($responseHdr[0]??'')
      );
    }

    $response = xmlrpc_decode($result, 'UTF-8');
    if (is_array($response) && \xmlrpc_is_fault($response)) {
      $this->authHeaders = []; // nothing
      return $this->handleError("Error: xmlrpc: $response[faultString] ($response[faultCode])");
    }

    if ($method == "dokuwiki.login" ||
        $method == "dokuwiki.stickylogin" ||
        $method == "plugin.remoteauth.stickyLogin" ||
        $method == "dokuwiki.logoff") {
      // Response _should_ be a single integer: if 0, login
      // unsuccessful, if 1: got it.
      if ($response == 1) {
        $this->authHeaders = [];
        // Store and duplicate set cookies for forwarding to the users web client
        $this->reqHeaders = [];
        foreach ($responseHdr as $header) {
          if (preg_match('/^Set-Cookie:\s*(DokuWiki|DW).*/', $header)) {
            $this->reqHeaders[] = trim(strtok(preg_replace('/^Set-Cookie:\s*/i', '', $header), ';'));
            $this->authHeaders[] = $header;
            $this->authHeaders[] = preg_replace('|path=([^;]+);|i', 'path='.\OC::$WEBROOT.'/;', $header);
          }
        }
        $this->logDebug("XMLRPC method \"$method\" executed with success. Got cookies ".
                        print_r($this->reqHeaders, true).
                        print_r($this->authHeaders, true).
                        ". Sent cookies ".$httpHeader);
        return true;
      } else {
        $this->logDebug("XMLRPC method \"$method\" to \"$url\" failed. Got headers ".
                        //print_r($responseHdr, true).
                        " request: ".print_r($data, true).
                        " data: ".$result.
                        " response: ".print_r($response, true).
                        " false: ".($response === false));
        return false;
      }
    }

    return $response;
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
   * @param $username Login name
   *
   * @param $password credentials
   *
   * @return bool true if successful, false otherwise.
   */
  public function login($username, $password)
  {
    $this->cleanCookies();
    $result = $this->xmlRequest("plugin.remoteauth.stickyLogin", [ $username, $password ]);
    if ($result === true) {
      return $result;
    }
    // Fall back to "normal" login if long-life token could not be aquired.
    $result = $this->xmlRequest("dokuwiki.login", [ $username, $password ]);
    if ($result === true) {
      return true;
    }
    return false;
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
  public function getPage($pagename)
  {
    return $this->xmlRequest("wiki.getPage", [ $pagename ]);
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
      $cookie[trim($cookieInfo[0])] =
        count($cookieInfo) == 2 ? trim($cookieInfo[1]) : true;
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
   * @param cookieHeader The raw header holding the cookie.
   *
   * @todo This probably should go into the Middleware as
   * afterController() and add the headers there.
   */
  private function addCookie($cookieHeader)
  {
    $thisCookie = $this->parseCookie($cookieHeader);
    $found = false;
    foreach (headers_list() as $header) {
      $cookie = $this->parseCookie($header);
      if ($cookie === $thisCookie) {
        return;
      }
    }
    $this->logDebug("Emitting cookie ".$cookieHeader);
    header($cookieHeader, false);
  }

  /**
   * Send authentication headers previously aquired
   */
  public function emitAuthHeaders()
  {
    foreach ($this->authHeaders as $header) {
      //header($header, false);
      $this->addCookie($header);
    }
  }
};

// Local Variables: ***
// c-basic-offset: 2 ***
// indent-tabs-mode: nil ***
// End: ***
