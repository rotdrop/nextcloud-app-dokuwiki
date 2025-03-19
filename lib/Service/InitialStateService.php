<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2025 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\DokuWiki\Service;

use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;

use OCA\DokuWiki\Constants;
use OCA\DokuWiki\Controller\SettingsController;

/**
 * Just provide the neccessary initial state.
 */
class InitialStateService
{
  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private AuthDokuWiki $authDokuWiki,
    private IConfig $config,
    private IInitialState $initialState,
    private string $appName,
  ) {
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
   * Provide the initial state for the app.
   *
   * @return void
   */
  public function provide():void
  {
    $refreshInterval = $this->config->getAppValue(
      SettingsController::AUTHENTICATION_REFRESH_INTERVAL,
      SettingsController::AUTHENTICATION_REFRESH_INTERVAL_DEFAULT,
    );
    $wikiURL = $this->authDokuWiki->wikiURL();

    $this->initialState->provideInitialState(
      Constants::INITIAL_STATE_SECTION, [
        'appName' => $this->appName,
        SettingsController::AUTHENTICATION_REFRESH_INTERVAL => $refreshInterval,
        'wikiURL' => $wikiURL,
      ],
    );
  }
}
