<?php
/*
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

namespace OCA\DokuWikiEmbedded\Listener;

use OCP\User\Events\BeforeUserLoggedOutEvent as HandledEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;
use OCP\IL10N;

use OCA\DokuWikiEmbedded\Service\AuthDokuWiki;
use OCA\DokuWikiEmbedded\Service\Constants;

class UserLoggedOutEventListener implements IEventListener
{
  use \OCA\DokuWikiEmbedded\Traits\LoggerTrait;

  const EVENT = HandledEvent::class;

  /** @var string */
  private $appName;

  /** @var OCA\DokuWikiEmbedded\Service\AuthDokuWiki */
  private $authenticator;

  public function __construct(
    AuthDokuWiki $authenticator
    , ILogger $logger
    , IL10N $l10n
  ) {
    $this->authenticator = $authenticator;
    $this->appName = $this->authenticator->getAppName();
    $this->logger = $logger;
    $this->l = $l10n;
  }

  public function handle(Event $event): void {
    if (!($event instanceOf HandledEvent)) {
      return;
    }

    if ($this->authenticator->logout()) {
      $this->authenticator->emitAuthHeaders();
      $this->logInfo("DokuWiki logoff probably succeeded.");
    }
  }
}

// Local Variables: ***
// c-basic-offset: 2 ***
// indent-tabs-mode: nil ***
// End: ***
