/**
 * DokuWikiEmbedded -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020, 2021, 2023 Claus-Justus Heine
 * @license AGPL-3.0-or-later
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

import { appName, webPrefix } from './config.js';

const jQuery = require('jquery');
const $ = jQuery;

/**
 * Called after the DokuWiki has been loaded by the iframe. We make
 * sure that external links are opened in another tab/window.
 *
 * @param {object} frame TBD.
 *
 * @param {object} frameWrapper TBD.
 *
 * @param {Function} callback TBD.
 *
 */
const loadHandler = function(frame, frameWrapper, callback) {
  const contents = frame.contents();

  contents.find('.logout').remove();
  contents.find('li:empty').remove();
  contents.find('form.btn_logout').remove();
  contents.find('#dokuwiki__usertools li.user').remove();
  contents.find('#dokuwiki__usertools a.action.profile').remove();

  // Make sure all external links are opened in another window
  contents.find('a').filter(function() {
    return this.hostname && this.hostname !== window.location.hostname;
  }).each(function() {
    $(this).attr('target', '_blank');
  });

  // make sure that links in the preview pane are NOT followed.
  contents.find('div.preview').find('a[class^="wikilink"]').off('click').on('click', function() {
    let wikiPage = $(this).attr('href');
    wikiPage = wikiPage.replace(/^\/[^?]+\?id=(.*)$/, '$1');
    OC.dialogs.alert(
      t(appName, 'Links to wiki-pages are disabled in preview mode.'),
      t(appName, 'Link to Wiki-Page') + ' "' + wikiPage + '"');
    return false;
  });

  contents.find('div.preview').find('a[class^="media"]').off('click').on('click', function() {
    let mediaPage = $(this).attr('href');
    mediaPage = mediaPage.replace(/^\/[^?]+\?id=(.*)$/, '$1');
    OC.dialogs.alert(
      t(appName, 'Links to media-files are disabled in preview mode.'),
      t(appName, 'Link to Media') + ' "' + mediaPage + '"');
    return false;
  });

  if (typeof callback === 'undefined') {
    callback = function() {};
  }

  const loader = $('#' + webPrefix + 'Loader');
  if (frameWrapper.is(':hidden')) {
    loader.fadeOut('slow', function() {
      frameWrapper.slideDown('slow', function() {
        callback(frame, frameWrapper);
      });
    });
  } else {
    loader.fadeOut('slow');
    callback(frame, frameWrapper);
  }
};

export { loadHandler };

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
