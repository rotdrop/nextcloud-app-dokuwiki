<?php
/**Embed a DokuWiki instance as app into ownCloud, intentionally with
 * single-sign-on.
 *
 * @author Claus-Justus Heine
 * @copyright 2013-2020 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

style($appName, 'doku-wiki');
script($appName, 'app');

// additional CSS class
$cssClass = $appName.'-'.(isset($_['cssClass']) ? $_['cssClass'] : 'fullscreen');

// additional attributes
$cnt = 0;
$tmp = preg_replace('/class="([^"]*)"/i', '${1} '.$cssClass, $iframeAttributes, -1 , $cnt);
if ($tmp !== null) {
  $iframeAttributes = $tmp;
}
if ($cnt == 0) {
  $iframeAttributes .= 'class="'.$cssClass.'"';
}

?>

<div id="<?php p($appName) ?>_container" class="<?php echo $cssClass; ?>">

  <img src="<?php echo $urlGenerator->imagePath($appName, 'loader.gif'); ?>"
       id="<?php p($appName) ?>Loader"
       class="<?php echo $cssClass; ?>"
  >
  <div id="<?php p($appName); ?>FrameWrapper"
       class="<?php echo $cssClass; ?>"
  >
    <iframe style="overflow:auto"
            src="<?php echo $wikiURL.$wikiPath;?>"
            id="<?php p($appName) ?>Frame"
            name="<?php $appName ?>"
            width="100%"
            <?php echo $iframeAttributes; ?>>
    </iframe>
  </div>
</div>
