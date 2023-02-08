<?php
/**
 * Nextcloud DokuWiki -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
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

script($appName, $assets['js']['asset']);
style($appName, $assets['css']['asset']);

$webPrefix = $appName;

// additional CSS class
$cssClass = $webPrefix . '-' . (isset($_['cssClass']) ? $_['cssClass'] : 'fullscreen');

// additional attributes
$cnt = 0;
$tmp = preg_replace('/class="([^"]*)"/i', '${1} ' . $cssClass, $iframeAttributes, -1 , $cnt);
if ($tmp !== null) {
  $iframeAttributes = $tmp;
}
if ($cnt == 0) {
  $iframeAttributes .= 'class="' . $cssClass . ' faded"';
}

?>

<div id="<?php p($webPrefix) ?>_container" class="<?php echo $cssClass; ?>">

  <img src="<?php echo $urlGenerator->imagePath($appName, 'loader.gif'); ?>"
       id="<?php p($webPrefix) ?>Loader"
       class="<?php echo $cssClass; ?>"
  >
  <div id="<?php p($webPrefix); ?>FrameWrapper"
       class="<?php echo $cssClass; ?>"
  >
    <iframe style="overflow:auto"
            src="<?php echo $wikiURL.$wikiPath;?>"
            id="<?php p($webPrefix) ?>Frame"
            name="<?php $webPrefix ?>"
            width="100%"
            <?php echo $iframeAttributes; ?>>
    </iframe>
  </div>
</div>
