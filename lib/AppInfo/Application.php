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

class Application extends App implements IBootstrap
{
  const APP_NAME = 'dokuwikiembedded';

  public function __construct (array $urlParams=array()) {
    parent::__construct(self::APP_NAME, $urlParams);
  }

  // Called later than "register".
  public function boot(IBootContext $context): void
  {
    $container = $context->getAppContainer();

    /* @var OCP\IConfig */
    $config = $container->query(IConfig::class);

    /* @var OCP\IInitialStateService */
    $initialState = $container->query(IInitialStateService::class);

    $initialState->provideInitialState(
      self::APP_NAME,
      'initial',
      [
        'appName' => self::APP_NAME,
        'refreshInterval' => $config->getAppValue(self::APP_NAME, 'refreshInterval', 600),
      ]
    );
    
    /* @var IEventDispatcher $eventDispatcher */
    $dispatcher = $container->query(IEventDispatcher::class);
    $dispatcher->addListener(        
      \OCP\AppFramework\Http\TemplateResponse::EVENT_LOAD_ADDITIONAL_SCRIPTS_LOGGEDIN,
      function() {
        \OCP\Util::addScript(self::APP_NAME, 'refresh');
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
