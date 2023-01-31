<?php
/**
 * Nextcloud DokuWiki -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020, 2021, 2022, 2023 Claus-Justus Heine
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

use Psr\Log\LoggerInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IL10N;
use OCP\IConfig;

/**
 * Settings-controller for both, personal and admin, settings.
 */
class SettingsController extends Controller
{
  use \OCA\RotDrop\Toolkit\Traits\UtilTrait;
  use \OCA\RotDrop\Toolkit\Traits\ResponseTrait;
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  public const EXTERNAL_LOCATION = 'externalLocation';
  public const EXTERNAL_LOCATION_DEFAULT = null;

  public const AUTHENTICATION_REFRESH_INTERVAL = 'authenticationRefreshInterval';
  public const AUTHENTICATION_REFRESH_INTERVAL_DEFAULT = 600;
  public const AUTHENTICAIONT_REFRESH_INTERVAL_MIN = 10;

  public const ENABLE_SSL_VERIFY = 'enableSSLVerify';
  public const ENABLE_SSL_VERIFY_DEFAULT = true;

  /**
   * @var array<string, array>
   *
   * Admin settings with r/w flag and default value.
   */
  public const ADMIN_SETTINGS = [
    self::EXTERNAL_LOCATION => [
      'rw' => true,
      'default' => self::EXTERNAL_LOCATION_DEFAULT,
    ],
    self::AUTHENTICATION_REFRESH_INTERVAL => [
      'rw' => true,
      'default' => self::AUTHENTICATION_REFRESH_INTERVAL_DEFAULT,
    ],
    self::ENABLE_SSL_VERIFY => [
      'rw' => true,
      'default' => self::ENABLE_SSL_VERIFY_DEFAULT,
    ],
  ];

  /** @var IConfig */
  private $config;

  /** @var IURLGenerator */
  private $urlGenerator;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IRequest $request,
    LoggerInterface $logger,
    IURLGenerator $urlGenerator,
    IL10N $l10n,
    IConfig $config,
  ) {
    parent::__construct($appName, $request);
    $this->logger = $logger;
    $this->urlGenerator = $urlGenerator;
    $this->l = $l10n;
    $this->config = $config;
  }
  // phpcs:enable

  /**
   * @param string $setting
   *
   * @param mixed $value
   *
   * @param bool $force
   *
   * @return DataResponse
   *
   * @AuthorizedAdminSetting(settings=OCA\DokuWiki\Settings\Admin)
   * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
   */
  public function setAdmin(string $setting, mixed $value, bool $force = false):DataResponse
  {
    if (!isset(self::ADMIN_SETTINGS[$setting])) {
      return self::grumble($this->l->t('Unknown admin setting: "%1$s"', $setting));
    }
    if (!(self::ADMIN_SETTINGS[$setting]['rw'] ?? false)) {
      return self::grumble($this->l->t('The admin setting "%1$s" is read-only', $setting));
    }
    $oldValue = $this->config->getAppValue(
      $this->appName,
      $setting,
      self::ADMIN_SETTINGS[$setting]['default'] ?? null,
    );
    $humanValue = null;
    switch ($setting) {
      case self::EXTERNAL_LOCATION:
        if ($value === '') { // ok, reset
          $newValue = null;
          break;
        }
        if ($value[0] == '/') {
          $value = $this->urlGenerator->getAbsoluteURL($value);
        }
        $urlParts = parse_url($value);
        if (empty($urlParts['scheme']) || !preg_match('/https?/', $urlParts['scheme'])) {
          if (empty($urlParts['scheme'])) {
            return self::grumble($this->l->t(
              'Scheme of external URL must be one of "http" or "https", but nothing was specified.'));
          } else {
            return self::grumble($this->l->t(
              'Scheme of external URL must be one of "http" or "https", "%s" given.', [
                $urlParts['scheme'],
              ]));
          }
        }
        if (empty($urlParts['host'])) {
          return self::grumble($this->l->t("Host-part of external URL seems to be empty"));
        }
        $newValue = $value;
        break;
      case self::AUTHENTICATION_REFRESH_INTERVAL:
        $newValue = filter_var(
          $value,
          FILTER_VALIDATE_INT,
          ['min_range' => self::AUTHENTICAIONT_REFRESH_INTERVAL_MIN, ],
        );
        if (empty($newValue)) {
          return self::grumble($this->l->t(
            'Value "%1$s" for setting "%2$s" is either not convertible to integer or out of range (minimum is %d seconds).', [
              $value,
              $setting,
              self::AUTHENTICAIONT_REFRESH_INTERVAL_MIN,
            ]));
        }
        break;
      case self::ENABLE_SSL_VERIFY:
        $newValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, ['flags' => FILTER_NULL_ON_FAILURE]);
        if ($newValue === null) {
          return self::grumble($this->l->t(
            'Value "%1$s" for setting "%2$s" is not convertible to boolean.', [
              $value, $setting,
            ]));
        }
        if ($newValue === (self::ADMIN_SETTINGS[$setting]['default'] ?? false)) {
          $newValue = null;
        } else {
          if ($newValue === true) {
            $humanValue = $this->l->t('true');
          } elseif ($newValue === false) {
            $humanValue = $this->l->t('false');
          }
          $newValue = (int)$newValue;
        }
        break;
      default:
        return self::grumble($this->l->t('Unknown admin setting: "%1$s"', $setting));
    }

    if ($newValue === null) {
      $this->config->deleteAppValue($this->appName, $setting);
      $newValue = self::ADMIN_SETTINGS[$setting]['default'] ?? null;
    } else {
      $this->config->setAppValue($this->appName, $setting, $newValue);
    }

    if ($humanValue === null) {
      $humanValue = $newValue;
    }

    return new DataResponse([
      'newValue' => $newValue,
      'oldValue' => $oldValue,
      'humanValue' => $humanValue,
    ]);
  }

  /**
   * @param string $setting
   *
   * @return DataResponse
   *
   * @AuthorizedAdminSetting(settings=OCA\DokuWiki\Settings\Admin)
   */
  public function getAdmin(?string $setting = null):DataResponse
  {
    if ($setting === null) {
      $allSettings = self::ADMIN_SETTINGS;
    } else {
      if (!isset(self::ADMIN_SETTINGS[$setting])) {
        return self::grumble($this->l->t('Unknown admin setting: "%1$s"', $setting));
      }
      $allSettings = [ $setting => self::ADMIN_SETTINGS[$setting] ];
    }
    $results = [];
    foreach (array_keys($allSettings) as $oneSetting) {
      $value = $this->config->getAppValue(
        $this->appName,
        $oneSetting,
        self::ADMIN_SETTINGS[$oneSetting]['default'] ?? null);
      $humanValue = $value;
      switch ($oneSetting) {
        case self::EXTERNAL_LOCATION:
        case self::AUTHENTICATION_REFRESH_INTERVAL:
          break;
        case self::ENABLE_SSL_VERIFY:
          if ($humanValue !== null) {
            $humanValue = $humanValue ? $this->l->t('true') : $this->l->t('false');
          }
          $value = !!$value;
          break;
        default:
          return self::grumble($this->l->t('Unknown admin setting: "%1$s"', $oneSetting));
      }
      $results[$oneSetting] = $value;
      $results['human' . ucfirst($oneSetting)] = $humanValue;
    }

    if ($setting === null) {
      return new DataResponse($results);
    } else {
      return new DataResponse([
        'value' => $results[$setting],
        'humanValue' => $results['human' . ucfirst($setting)],
      ]);
    }
  }
}
