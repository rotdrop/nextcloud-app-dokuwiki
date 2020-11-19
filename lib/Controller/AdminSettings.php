<?php

namespace OCA\DokuWikiEmbedded\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class AdminSettingsController extends Controller {

  private $userId;

  private $appName;
  
  public function __construct(
    $appName
    , IRequest $request
    , $UserId
  ) {
    parent::__construct($appName, $request);
    $this->appName = $appName;    
    $this->userId = $UserId;
  }

  /**
   */
  public function set() {
    return new DataResponse([ 'value' => 'I am a value!']);
  }

}
