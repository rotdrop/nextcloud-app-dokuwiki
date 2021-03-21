<?php
/**
 * DokuWikiEmbedded -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * DokuWikiEmbedded is free software: you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * DokuWikiEmbedded is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with DokuWikiEmbedded. If not, see
 * <http://www.gnu.org/licenses/>.
 */

namespace OCA\DokuWikiEmbedded;

script($appName, 'admin-settings');

?>

<div class="section">
  <h2><?php p($l->t('Embedded DokuWiki')) ?></h2>
  <form id="<?php p($webPrefix); ?>settings">
    <input type="text"
           name="externalLocation"
           id="externalLocation"
           class="externalLocation"
           value="<?php echo $externalLocation; ?>"
           placeholder="<?php echo $l->t('Location');?>"
           title="<?php echo $l->t('Please enter the location of the already installed DokuWiki
instance. This should either be an abolute path relative to the
root of the web-browser, or a complete URL which points to the
web-location of the DokuWiki. In order to make things work your
have to enable the XMLRPC protocol in your DokuWiki.'); ?>"
    />
    <label for="externalLocation"><?php echo $l->t('DokuWiki Location');?></label>
    <br/>
    <input type="number"
           name="authenticationRefreshInterval"
           id="authenticationRefreshInterval"
           class="authenticationRefreshInterval"
           value="<?php echo $authenticationRefreshInterval; ?>"
           placeholder="<?php echo $l->t('Refresh Time [s]'); ?>"
           title="<?php echo $l->t('Please enter the desired session-refresh interval here. The interval is measured in seconds and should be somewhat smaller than the configured session life-time for the DokuWiki instance in use.'); ?>"
    />
    <label for="authenticationRefreshInterval"><?php echo $l->t('DokuWiki Session Refresh Interval [s]'); ?></label>
    <br/>
    <input type="checkbox"
           name="enableSSLVerify"
           id="enableSSLVerify"
           class="checkbox"
	   <?php if ($enableSSLVerify) { echo 'checked="checked"'; } ?>
    />
    <label title="<?php p($l->t('Disable SSL verification, e.g. for self-signed certification or known mis-matching host-names like "localhost".')); ?>"
           for="enableSSLVerify">
      <?php p($l->t('Enable SSL verification.')); ?>
    </label>
    <br/>
    <span class="msg"></span>
  </form>
</div>
