<?php
/**
 * DokuWikiEmbedded -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020, 2021, 2022, 2023 Claus-Justus Heine
 * @license AGPL-3.0-or-later
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

namespace OCA\DokuWikiEmbedded\Controller;

use OCP\IRequest;
use OCP\IConfig;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use Psr\Log\LoggerInterface as ILogger;
use OCP\IL10N;

use OCA\DokuWikiEmbedded\Settings\Admin;

/** AJAX end points for admin settings. */
class AdminSettingsController extends Controller
{
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;
  use \OCA\DokuWikiEmbedded\Traits\ResponseTrait;

  private $userId;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IRequest $request,
    IConfig $config,
    ?string $userId,
    ILogger $logger,
    IL10N $l10n,
  ) {
    parent::__construct($appName, $request);
    $this->config = $config;
    $this->userId = $userId;
    $this->logger = $logger;
    $this->l = $l10n;
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
   * @return DataResponse
   *
   * @AuthorizedAdminSetting(settings=OCA\DokuWikiEmbedded\Settings\Admin)
   */
  public function set():DataResponse
  {
    foreach (array_keys(Admin::SETTINGS) as $setting) {
      if (!isset($this->request[$setting])) {
        continue;
      }
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
        case 'enableSSLVerify':
          $realValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, ['flags' => FILTER_NULL_ON_FAILURE]);
          if ($realValue === null) {
            return self::grumble($this->l->t('Value "%1$s" for set "%2$s" is not convertible to boolean.', [$value, $setting]));
          }
          $value = $realValue;
          $strValue = $value ? 'on' : 'off';
          break;
      }
      $this->config->setAppValue($this->appName, $setting, $value);
      if (empty($strValue)) {
        $strValue = $value;
      }
      return new DataResponse([
        'value' => $value,
        'message' => $this->l->t("Parameter %s set to %s", [ $setting, $strValue ]),
      ]);
    }
    return new DataResponse([ 'message' => $this->l->t('Unknown Request') ], Http::STATUS_BAD_REQUEST);
  }
}
