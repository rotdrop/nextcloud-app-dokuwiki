<?php
/**
 * TextDokuWiki -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020, 2021, 2022, 2023 Claus-Justus Heine
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

namespace OCA\TextDokuWiki\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IURLGenerator;
use OCP\Settings\IDelegatedSettings;
use OCP\IConfig;
use Psr\Log\LoggerInterface as ILogger;
use OCP\IL10N;

/** Admin settings. */
class Admin implements IDelegatedSettings
{
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  const TEMPLATE = 'admin-settings';
  const SETTINGS = [
    'externalLocation' => '',
    'authenticationRefreshInterval' => 600,
    'enableSSLVerify' => true,
  ];

  /** @var \OCP\IURLGenerator */
  private $urlGenerator;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IConfig $config,
    IURLGenerator $urlGenerator,
    ILogger $logger,
    IL10N $l10n,
  ) {
    $this->appName = $appName;
    $this->config = $config;
    $this->urlGenerator = $urlGenerator;
    $this->logger = $logger;
    $this->l = $l10n;
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /** {@inheritdoc} */
  public function getForm()
  {
    $templateParameters = [
      'appName' => $this->appName,
      'webPrefix' => $this->appName,
      'urlGenerator' => $this->urlGenerator,
    ];
    foreach (self::SETTINGS as $setting => $default) {
      $templateParameters[$setting] = $this->config->getAppValue($this->appName, $setting, $default);
    }
    return new TemplateResponse(
      $this->appName,
      self::TEMPLATE,
      $templateParameters);
  }

  /** {@inheritdoc} */
  public function getSection()
  {
    return $this->appName;
  }

  /** {@inheritdoc} */
  public function getPriority()
  {
    // @@TODO could be made a configure option.
    return 50;
  }

  /** {@inheritdoc} */
  public function getName():?string
  {
    return null;
  }

  /** {@inheritdoc} */
  public function getAuthorizedAppConfig():array
  {
    return [];
  }
}
