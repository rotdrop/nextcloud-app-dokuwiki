<?php
/**
 * Nextcloud DokuWiki -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020-2026 Claus-Justus Heine
 * @license   AGPL-3.0-or-later
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

// phpcs:disable PSR1.Files.SideEffects
// phpcs:ignore PSR1.Files.SideEffects

namespace OCA\DokuWiki\AppInfo;

/*-********************************************************
 *
 * Bootstrap
 *
 */

use OCP\AppFramework\Bootstrap\IRegistrationContext;

use OCA\DokuWiki\Listener\Registration as ListenerRegistration;
use OCA\DokuWiki\Toolkit\AppInfo\AbstractApplication;

include_once __DIR__ . '/../Toolkit/AppInfo/AbstractApplication.php';

/**
 * App entry point.
 */
class Application extends AbstractApplication
{
  /** {@inheritdoc} */
  public function register(IRegistrationContext $context):void
  {
    parent::register($context);
    ListenerRegistration::register($context);
  }
}
