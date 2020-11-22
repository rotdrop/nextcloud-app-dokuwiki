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
use OCP\IConfig;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\ILogger;
use OCP\IL10N;

use OCA\DokuWikiEmbedded\Settings\Admin;

class AdminSettingsController extends Controller
{
  use \OCA\DokuWikiEmbedded\Traits\LoggerTrait;
  use \OCA\DokuWikiEmbedded\Traits\ResponseTrait;

  private $userId;

  private $containerConfig;
  
  public function __construct(
    $appName
    , IRequest $request
    , IConfig $config
    , $UserId
    , ILogger $logger
    , IL10N $l10n
  ) {
    parent::__construct($appName, $request);
    $this->config = $config;
    $this->userId = $UserId;
    $this->logger = $logger;
    $this->l = $l10n;
  }

  public function set()
  {
    foreach (Admin::SETTINGS as $setting) {
      if (!empty($this->request[$setting])) {
        $value = trim($this->request[$setting]);
        $this->logInfo("Got setting ".$setting.": ".$value);
        switch ($setting) {
          case 'externalLocation':
            if ($value[0] == '/') {
              $value = $this->urlGenerator->getAbsoluteURL($value);
            }
            $urlParts = parse_url($value);
            if (empty($urlParts['scheme']) || !preg_match('/https?/', $urlParts['scheme'])) {
              return self::grumble($this->l->t("Scheme of external URL must be one of `http' or `https', `%s' given.", [$urlParts['scheme']]));
            }
            if (empty($urlParts['host'])) {
              return self::grumble($this->l->t("Host-part of external URL seems to be empty"));
            }
            break;
          case 'authenticationRefreshInterval':
            break;
        }
        $this->config->setAppValue($this->appName, $setting, $value);
        return new DataResponse([
          'value' => $value,
          'message' => $this->l->t("Parameter %s set to %s", [ $setting, $value ]),
        ]);
      }
      if (isset($this->request[$setting]))   {
        return self::grumble($this->l->t("No data submitted for setting `%s'.", [$setting]));
      }
    }
    return new DataResponse([ 'message' => $this->l->t('Unknown Request') ], Http::STATUS_BAD_REQUEST);
  }
}
