<?php
/**
 * DokuWikiEmbedded -- Embed DokuWik into NextCloud with SSO.
 *
 * @author Claus-Justus Heine
 * @copyright 2020 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\DokuWikiEmbedded\Service;

use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\ILogger;
use OCP\IL10N;

class AuthDokuWiki
{
  use \OCA\DokuWikiEmbedded\Traits\LoggerTrait;

  const APP_NAME = 'dokuwikiembedded';
  const RPCPATH = '/lib/exe/xmlrpc.php';

  private $userId;
  
  private $appName;

  private $config;

  private $urlGenerator;
  
  private $dwProto;
  private $dwHost;
  private $dwPort;
  private $dwPath;  

  private $authHeaders; //!< Authentication headers returned by DokuWiki
  private $reqHeaders;  //!< Authentication headers, cookies we send to DW

  public function __construct(
    /*$appname
       ,*/ IConfig $config
  , IURLGenerator $urlGenerator
  , $userId
  , ILogger $logger
  , IL10N $l10n
  ) {
    $this->userId = $userId;
    $this->appName = self::APP_NAME;
    $this->config = $config;
    $this->urlGenerator = $urlGenerator;
    $this->logger = $logger;
    $this->l = $l10n;
    
    $location = $this->config->getAppValue($this->appName, 'externalLocation');
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

    $this->authHeaders = [];

    // If we have cookies with AuthData, then store them in authHeaders
    $this->reqHeaders = [];
    foreach ($_COOKIE as $cookie => $value) {
      if (preg_match('/^(DokuWiki|DW).*/', $cookie)) {
        $this->reqHeaders[] = "$cookie=".urlencode($value);
      }
    }
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
    $this->authHeaders = array();
    $this->reqHeaders = array();
    foreach ($_COOKIE as $cookie => $value) {
      if (preg_match('/^(DokuWiki|DW).*/', $cookie)) {
        unset($_COOKIE[$cookie]);
      }
    }
  }

  private function xmlRequest($method, $data = [])
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
      'http' => [ 'method' => 'POST',
                  'header' => $httpHeader,
                  'content' => $request,
      ],
      'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
      ],
    ]);
    $url  = $this->wikiURL().self::RPCPATH;
    $fp   = fopen($url, 'rb', false, $context);
    if ($fp !== false) {
      $result = stream_get_contents($fp);
      fclose($fp);
      $responseHdr = $http_response_header;
    } else {
      $this->logError("URL fopen to $url failed");      
      $result = '';
      $responseHdr = '';
    }

    $response = xmlrpc_decode($result);
    if (is_array($response) && \xmlrpc_is_fault($response)) {
      $this->logError("Error: xlmrpc: $response[faultString] ($response[faultCode])");
      $this->authHeaders = array(); // nothing
      return false;
    }

    if ($method == "dokuwiki.login" ||
        $method == "dokuwiki.stickylogin" ||
        $method == "plugin.remoteauth.stickyLogin" ||
        $method == "dokuwiki.logoff") {
      // Response _should_ be a single integer: if 0, login
      // unsuccessful, if 1: got it.
      if ($response == 1) {
        $this->authHeaders = array();
        // Store and duplicate set cookies for forwarding to the users web client
        foreach ($responseHdr as $header) {
          if (preg_match('/^Set-Cookie:\s*(DokuWiki|DW).*/', $header)) {
            $this->authHeaders[] = $header;
            $this->authHeaders[] = preg_replace('|path=([^;]+);|i', 'path='.\OC::$WEBROOT.'/;', $header);
          }
        }
        $this->logDebug("XMLRPC method \"$method\" executed with success. Got cookies ".
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
    
    return $result == '' ? false : $response;
  }  

  /**
   * Perform the login by means of a RPCXML call and stash the cookies
   * storing the credentials away for later; the cookies are
   * re-emitted to the users web-client when the OC wiki-app is
   * activated. This login function itself is only meant for being
   * called during the login process.
   *
   * @param[in] $username Login name
   *
   * @param[in] $password credentials
   *
   * @return true if successful, false otherwise.
   */
  public function login($username, $password)
  {
    $this->cleanCookies();
    if (!empty($_POST["remember_login"])) { // @TODO : DO NOT USE POST here
      $result = $this->xmlRequest("plugin.remoteauth.stickyLogin", [ $username, $password ]);
      if ($result !== false) {
        return $result;
      }
    }
    // Fall back to "normal" login if long-life token could not be aquired.
    return $this->xmlRequest("dokuwiki.login", [ $username, $password ]);
  }

  /**
   * Logoff from DokuWiki with added XMLRPC dokuwiki.logoff
   * call. For this to work we have to send the DokuWiki cookies
   * alongside the XMLRPC request.
   */
  public function logout()
  {
    return $this->xmlRequest("dokuwiki.logoff");
  }

  /**
   * Fetch the version from the DW instance in the hope that this also
   * touches the session life-time.
   */
  public function version()
  {
    return $this->xmlRequest("dokuwiki.getVersion");
  }

  /**
   * Ping the external application in order to extend its login
   * session.
   */
  public function refresh():bool
  {
    return $this->version() !== false;
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
   * some automatic wiki-pages (e.g. overview stuff and the like, may
   * a changelog here and a readme there.
   */
  public function getPage($pagename)
  {
    return $this->xmlRequest("wiki.getPage", [ $pagename ]);
  }

  /**
   * Parse a cookie header in order to obtain name, date of
   * expiry and path.
   *
   * @parm cookieHeader Guess what
   *
   * @return Array with name, value, expires and path fields, or
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
