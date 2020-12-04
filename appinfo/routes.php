<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\DokuWikiEmbedded\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
  'routes' => [
    [
      'name' => 'page#index',
      'url' => '/page/index',
      'verb' => 'GET',
    ],
    [
      'name' => 'page#frame',
      'url' => '/page/frame/{renderAs}',
      'verb' => 'POST',
      'default' => [
        'renderAs' => 'blank',
      ],
    ],
    [
      'name' => 'admin_settings#set',
      'url' => '/settings/admin/set',
      'verb' => 'POST',
    ],
    [
      'name' => 'authentication#refresh',
      'url' => '/authentication/refresh',
      'verb' => 'POST',
    ],
  ],
];
