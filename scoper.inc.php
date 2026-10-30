<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
  'prefix' => 'OCA\\DokuWiki\\Scoped',
  'expose-global-classes' => false,
  'exclude-classes' => [
    'OC',
  ],
  'exclude-namespaces' => [
    '/^(OC|OCP|NCU)(\\\\|$)/',
    '/^Psr(\\\\|$)/',
  ],
  'finders' => [
    Finder::create()
      ->files()
      ->ignoreVCS(true)
      ->in('vendor-scoped'),
  ],
];
