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

namespace OCA\DokuWikiEmbedded\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\IURLGenerator;
use OCP\ILogger;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IInitialStateService;

use OCA\DokuWikiEmbedded\Traits;
use OCA\DokuWikiEmbedded\Service\AuthDokuWiki as Authenticator;

class PageController extends Controller
{
  use Traits\LoggerTrait;
  use Traits\ResponseTrait;
  
  const TEMPLATE = 'doku-wiki';
  
  private $userId;

  private $authenticator;

  private $config;

  private $urlGenerator;

  private $initialStateService;
  
  public function __construct(
    $appName
    , IRequest $request
    , Authenticator $authenticator
    , IConfig $config
    , IURLGenerator $urlGenerator
    , IInitialStateService $initialStateService
    , ILogger $logger
    , IL10N $l10n
  ) {
    parent::__construct($appName, $request);
    $this->authenticator = $authenticator;
    $this->authenticator->errorReporting(Authenticator::ON_ERROR_THROW);
    $this->config = $config;
    $this->urlGenerator = $urlGenerator;
    $this->initialStateService = $initialStateService;
    $this->logger = $logger;
    $this->l = $l10n;
  }
  
  /**
   * @NoAdminRequired
   * @NoCSRFRequired
   */
  public function index()
  {
    return $this->frame('user');
  }

  /**
   * @NoAdminRequired
   */
  public function frame($renderAs = 'blank')
  {
    try {
      $this->initialStateService->provideInitialState(
        $this->appName,
        'initial',
        [
          'appName' => $this->appName,
          'refreshInterval' => $this->config->getAppValue('refreshInterval', 600),
        ]
      );
      
      $wikiURL      = $this->authenticator->wikiURL();
      $wikiPage     = $this->request->getParam('wikiPage', '');
      $popupTitle   = $this->request->getParam('popupTitle', '');
      $cssClass     = $this->request->getParam('cssClass', 'fullscreen');
      $attributes   =  $this->request->getParam('iframeAttributes', '');

      $this->authenticator->refresh(); // maybe attempt re-login
      $this->authenticator->emitAuthHeaders(); // emit auth headers s.t. web-client sets cookies

      $templateParameters = [
        'appName' => $this->appName,
        'wikiURL' => $wikiURL,
        'wikiPath' => '/doku.php?id='.$wikiPage,
        'cssClass' => $cssClass,
        'iframeAttributes' => $attributes,
        'urlGenerator' => $this->urlGenerator,
      ];
      
      $response = new TemplateResponse(
        $this->appName,
        self::TEMPLATE,
        $templateParameters,
        $renderAs);

      $policy = new ContentSecurityPolicy();
      $policy->addAllowedChildSrcDomain('*');
      $policy->addAllowedFrameDomain('*');
      $response->setContentSecurityPolicy($policy);
      
      return $response;
      
    } catch (\Throwable $t) {
      if ($renderAS == 'blank') {
        $this->logException($t);
        return self::grumble($this->exceptionChainData($t));
      } else {
        throw $t;
      }
    }

    
  }
}
