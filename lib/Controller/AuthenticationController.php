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

namespace OCA\DokuWiki\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface as ILogger;

use OCA\DokuWiki\Service\AuthDokuWiki as Authenticator;

/** AJAX end points for periodic authentication refresh. */
class AuthenticationController extends Controller
{
  use \OCA\DokuWiki\Toolkit\Traits\LoggerTrait;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IRequest $request,
    private ?string $userId,
    private Authenticator $authenticator,
    protected ILogger $logger,
  ) {
    parent::__construct($appName, $request);
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
   * @return void
   *
   * @todo Check whether there is a successful login which could be
   * refreshed.
   *
   * @NoAdminRequired
   */
  public function refresh():DataResponse
  {
    $response = $this->authenticator->refresh();
    if (false === $response) {
      $this->logError("DokuWiki refresh for user ".($this->userId)." failed.");
      return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
    } else {
      $this->authenticator->emitAuthHeaders();
      $this->logDebug("DokuWiki refresh ".($this->userId)." probably succeeded");
      return new DataResponse([ 'data' => $response ], Http::STATUS_OK);
    }
  }
}
