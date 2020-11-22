<?php
/**
 * Nextcloud - DokuWikiEmbedded
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Claus-Justus Heine 2020
 */

namespace OCA\DokuWikiEmbedded\AppInfo;

/**********************************************************
 *
 * Bootstrap
 *
 */

use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\App;
use OCP\IConfig;
use OCP\IInitialStateService;

/*
 *
 **********************************************************
 *
 * Events and listeners
 *
 */

use OCP\EventDispatcher\IEventDispatcher;
use OCA\DokuWikiEmbedded\Listener\Registration as ListenerRegistration;

/*
 *
 **********************************************************
 *
 */
use OCA\DokuWikiEmbedded\Service\Constants;

class Application extends App implements IBootstrap
{
  public function __construct (array $urlParams=array()) {
    parent::__construct(Constants::APP_NAME, $urlParams);
  }

  // Called later than "register".
  public function boot(IBootContext $context): void
  {
    $container = $context->getAppContainer();

    /* @var OCP\IConfig */
    $config = $container->query(IConfig::class);
    $refreshInterval = $config->getAppValue(Constants::APP_NAME, 'refreshInterval', 600);

    /* @var OCP\IInitialStateService */
    $initialState = $container->query(IInitialStateService::class);
    
    /* @var IEventDispatcher $eventDispatcher */
    $dispatcher = $container->query(IEventDispatcher::class);
    $dispatcher->addListener(        
      \OCP\AppFramework\Http\TemplateResponse::EVENT_LOAD_ADDITIONAL_SCRIPTS_LOGGEDIN,
      function() use ($initialState, $refreshInterval) {

        $initialState->provideInitialState(
          Constants::APP_NAME,
          'initial',
          [
            'appName' => Constants::APP_NAME,
            'webPrefix' => Constants::APP_PREFIX,
            'refreshInterval' => $refreshInterval,    
          ]
        );
        
        \OCP\Util::addScript(Constants::APP_NAME, 'refresh');
      }
    );
  }

  // Called earlier than boot, so anything initialized in the
  // "boot()" method must not be used here.
  public function register(IRegistrationContext $context): void
  {
    ListenerRegistration::register($context);
  }
}

// Local Variables: ***
// c-basic-offset: 2 ***
// indent-tabs-mode: nil ***
// End: ***
