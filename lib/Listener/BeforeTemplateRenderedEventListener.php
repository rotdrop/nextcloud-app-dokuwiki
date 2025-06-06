<?php
/**
 * Nextcloud DokuWiki -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2024, 2025 Claus-Justus Heine
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

namespace OCA\DokuWiki\Listener;

use Throwable;

use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent as HandledEvent;
use OCP\AppFramework\IAppContainer;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IRequest;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface as ILogger;

use OCA\DokuWiki\Constants;
use OCA\DokuWiki\Controller\SettingsController;
use OCA\DokuWiki\Service\AssetService;
use OCA\DokuWiki\Service\InitialStateService;

/** Load additional scripts while running interactively. */
class BeforeTemplateRenderedEventListener implements IEventListener
{
  use \OCA\DokuWiki\Toolkit\Traits\LoggerTrait;
  use \OCA\DokuWiki\Toolkit\Traits\ApiRequestTrait;

  const EVENT = HandledEvent::class;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(protected IAppContainer $appContainer)
  {
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /** {@inheritdoc} */
  public function handle(Event $event):void
  {
    if (!($event instanceof HandledEvent)) {
      return;
    }
    /** @var HandledEvent $event */

    $this->logger = $this->appContainer->get(ILogger::class);

    $request = $this->appContainer->get(IRequest::class);
    if ($this->isNonInteractiveRequest($request, LogLevel::DEBUG)) {
      return;
    }

    if (!$event->isLoggedIn()) {
      // this app does not provide any public pages
      return;
    }

    try {
      /** @var InitialStateService $initialStateService */
      $initialStateService = $this->appContainer->get(InitialStateService::class);

      $initialStateService->provide();

      /** @var string $appName */
      $appName = $this->appContainer->get('appName');

      /** @var AssetService $assetService */
      $assetService = $this->appContainer->get(AssetService::class);

      \OCP\Util::addScript($appName, $assetService->getJSAsset('refresh')['asset']);
      $this->logDebug('Loaded ' . $assetService->getJSAsset('refresh')['asset']);
    } catch (Throwable $t) {
      $this->logException($t, 'Unable add the refresh java script while running interactively.');
    }
  }
}
