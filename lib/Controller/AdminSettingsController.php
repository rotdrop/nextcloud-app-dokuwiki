<?php

namespace OCA\DokuWikiEmbedded\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\ILogger;
use OCP\IL10N;

class AdminSettingsController extends Controller
{
  use \OCA\DokuWikiEmbedded\Traits\LoggerTrait;

  const SETTINGS = [
    'externalLocation',
    'authenticationRefreshInterval',
  ];

  private $userId;

  public function __construct(
    $appName
    , IRequest $request
    , $UserId
    , ILogger $logger
    , IL10N $l10n
  ) {
    parent::__construct($appName, $request);
    $this->userId = $UserId;
    $this->logger = $logger;
    $this->l = $l10n;
  }

  public function set()
  {
    foreach (self::SETTINGS as $setting) {
      if (isset($request[$setting])) {
        $this->logInfo("Got setting ".$setting.": ".$rquest[$setting]);
      }
    }
    return new DataResponse([ 'value' => 'I am a value!']);
  }

}
