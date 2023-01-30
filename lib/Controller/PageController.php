<?php
/**
 * TextDokuWiki -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020, 2021, 2023 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 *
 * TextDokuWiki is free software: you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * TextDokuWiki is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with TextDokuWiki. If not, see
 * <http://www.gnu.org/licenses/>.
 */

namespace OCA\TextDokuWiki\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Controller;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface as ILogger;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IInitialStateService;

use OCA\RotDrop\Toolkit\Traits;
use OCA\TextDokuWiki\Service\AuthDokuWiki as Authenticator;
use OCA\TextDokuWiki\Service\AssetService;
use OCA\TextDokuWiki\Constants;

/** Main entry point for web frontend. */
class PageController extends Controller
{
  use Traits\LoggerTrait;
  use Traits\ResponseTrait;

  const TEMPLATE = 'doku-wiki';
  const ASSET = 'app';

  /** @var Authenticator */
  private $authenticator;

  /** @var AssetService */
  private $assetService;

  /** @var IConfig */
  private $config;

  /** @var IURLGenerator */
  private $urlGenerator;

  /** @var IInitialStateService */
  private $initialStateService;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IRequest $request,
    Authenticator $authenticator,
    AssetService $assetService,
    IConfig $config,
    IURLGenerator $urlGenerator,
    IInitialStateService $initialStateService,
    ILogger $logger,
    IL10N $l10n,
  ) {
    parent::__construct($appName, $request);
    $this->authenticator = $authenticator;
    $this->authenticator->errorReporting(Authenticator::ON_ERROR_THROW);
    $this->assetService = $assetService;
    $this->config = $config;
    $this->urlGenerator = $urlGenerator;
    $this->initialStateService = $initialStateService;
    $this->logger = $logger;
    $this->l = $l10n;
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
   * @return Response
   *
   * @NoAdminRequired
   * @NoCSRFRequired
   */
  public function index():Response
  {
    return $this->frame('user');
  }

  /**
   * @param string $renderAs
   *
   * @return Response
   *
   * @NoAdminRequired
   */
  public function frame(string $renderAs = 'blank'):Response
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
      $cssClass     = $this->request->getParam('cssClass', 'fullscreen');
      $attributes   =  $this->request->getParam('iframeAttributes', '');

      $this->authenticator->refresh(); // maybe attempt re-login
      $this->authenticator->emitAuthHeaders(); // emit auth headers s.t. web-client sets cookies

      $templateParameters = [
        'appName' => $this->appName,
        'wikiURL' => $wikiURL,
        'wikiPath' => '/doku.php?id=' . $wikiPage,
        'cssClass' => $cssClass,
        'iframeAttributes' => $attributes,
        'urlGenerator' => $this->urlGenerator,
        'assets' => [
          Constants::JS => $this->assetService->getJSAsset(self::ASSET),
          Constants::CSS => $this->assetService->getCSSAsset(self::ASSET),
        ],
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
      if ($renderAs == 'blank') {
        $this->logException($t);
        return self::grumble($this->exceptionChainData($t));
      } else {
        throw $t;
      }
    }
  }
}
