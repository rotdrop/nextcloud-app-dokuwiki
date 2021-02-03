/**
 * Embed a DokuWiki instance as app into ownCloud, intentionally with
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

import { state, appName } from './config.js';

const jQuery = require('jquery');
const $ = jQuery;

/**
 * Fetch data from an error response.
 *
 * @param {Object} xhr jqXHR, see fail() method of jQuery ajax.
 *
 * @param {Object} status from jQuery, see fail() method of jQuery ajax.
 *
 * @param {Object} errorThrown, see fail() method of jQuery ajax.
 *
 * @returns {Array}
 */
const ajaxFailData = function(xhr, status, errorThrown) {
  const ct = xhr.getResponseHeader('content-type') || '';
  let data = {
    error: errorThrown,
    status,
    message: t(appName, 'Unknown JSON error response to AJAX call: {status} / {error}'),
  };
  if (ct.indexOf('html') > -1) {
    console.debug('html response', xhr, status, errorThrown);
    console.debug(xhr.status);
    data.message = t(appName, 'HTTP error response to AJAX call: {code} / {error}', {
      code: xhr.status, error: errorThrown,
    });
  } else if (ct.indexOf('json') > -1) {
    const response = JSON.parse(xhr.responseText);
    // console.info('XHR response text', xhr.responseText);
    // console.log('JSON response', response);
    data = {...data, ...response };
  } else {
    console.log('unknown response');
  }
  // console.info(data);
  return data;
};

/**
 * Called after the DokuWiki has been loaded by the iframe. We make
 * sure that external links are opened in another tab/window.
 *
 * @param {Object} frame TBD.
 *
 * @param {Object} frameWrapper TBD.
 *
 * @param {Function} callback TBD.
 *
 */
const loadCallback = function(frame, frameWrapper, callback) {
  const contents = frame.contents();
  const webPrefix = state.webPrefix;

  contents.find('.logout').remove();
  contents.find('li:empty').remove();
  contents.find('form.btn_logout').remove();
  contents.find('#dokuwiki__usertools li.user').remove();
  contents.find('#dokuwiki__usertools a.action.profile').remove();

  // Make sure all external links are opened in another window
  contents.find('a').filter(function() {
    return this.hostname && this.hostname !== window.location.hostname;
  }).each(function() {
    $(this).attr('target','_blank');
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

export { loadCallback, ajaxFailData };

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
