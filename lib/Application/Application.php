?php
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
use OCP\IL10N;

/*
 *
 **********************************************************
 *
 * Events and listeners
 *
 */

use OCA\DokuWikiEmbedded\Listener\Registration as ListenerRegistration;

/*
 *
 **********************************************************
 *
 */

class Application extends App implements IBootstrap {

    public function __construct (array $urlParams=array()) {
        parent::__construct('dokuwikiembedded', $urlParams);
    }

    // Called later than "register".
    public function boot(IBootContext $context): void
    {
        //$container = $context->getAppContainer();
    }

    // Called earlier than boot, so anything initialized in the
    // "boot()" method must not be used here.
    public function register(IRegistrationContext $context): void
    {
        // if ((@include_once __DIR__ . '/../../vendor/autoload.php')===false) {
        //     throw new Exception('Cannot include autoload. Did you run install dependencies using composer?');
        // }

        // Register listeners
        ListenerRegistration::register($context);
    }
}

// Local Variables: ***
// c-basic-offset: 4 ***
// indent-tabs-mode: nil ***
// End: ***
