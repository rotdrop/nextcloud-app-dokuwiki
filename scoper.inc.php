<?php
/**
 * DokuWiki integration for Nextcloud.
 *
 * @license AGPL-3.0-or-later
 */

use Isolated\Symfony\Component\Finder\Finder;

$infoXml = file_get_contents(__DIR__ . '/appinfo/info.xml');
$matches = null;
preg_match('|<namespace>([^<]+)</namespace>|', $infoXml, $matches);
$nameSpace = $matches[1];
preg_match('|<scopednamespace>([^<]+)</scopednamespace>|', $infoXml, $matches);
$scopedNameSpace = $matches[1];

return [
  'prefix' => 'OCA\\' . $nameSpace . '\\' . $scopedNameSpace,
  'exclude-classes' => [
    'OC',
    'Symfony\\Component\\Console',
  ],
  'expose-global-classes' => false,
  'exclude-namespaces' => [
    '/^PHPUnit/',
    'Psr\\Clock',
    'Psr\\Log',
    '/^Symfony\\\\Component\\\\(Console|Process)(\\\\|$)/',
    '/^Psr\\\\Container(\\\\|$)/',
    '/^GuzzleHttp(\\\\|$)/',
    '/^(OC|OCP|NCU)(\\\\|$)/',
    '/^OCA\\\\(GroupFolders|RotDrop\\\\DevScripts)(\\\\|$)/',
    '/^MathPHP(\\\\|$)/',
    '/^Spatie\\\\TypeScriptTransformer(\\\\|$)/',
  ],
  'finders' => [
    Finder::create()
      ->files()
      ->ignoreVCS(true)
      ->followLinks(true)
      ->in('vendor-scoped'),
  ],
  'patchers' => [
    function(string $filePath, string $prefix, string $content): string {
      return str_replace("trigger_deprecation('", '\\' . $prefix . "\\trigger_deprecation('", $content);
    },
  ],
];
