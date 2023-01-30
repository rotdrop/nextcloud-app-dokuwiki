/**
 * DokuWikiEmbedded -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
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

import { appName } from './config.js';
import { loadHandler } from './doku-wiki.js';
import jQuery from './toolkit/util/jquery.js';

import '../style/doku-wiki.scss';

const $ = jQuery;
const webPrefix = appName;

$(function() {

  console.info('DokuWiki webPrefix', webPrefix);
  const container = $('#' + webPrefix + '_container');
  const frameWrapper = $('#' + webPrefix + 'FrameWrapper');
  const frame = $('#' + webPrefix + 'Frame');
  const contents = frame.contents();

  const setHeightCallback = function() {
    container.height($('#content').height());
  };

  if (frame.length > 0) {
    frame.on('load', function() {
      loadHandler($(this), frameWrapper, setHeightCallback);
    });

    let resizeTimer;
    $(window).resize(function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(setHeightCallback);
    });
  }
  if (contents.find('.logout')) {
    loadHandler(frame, frameWrapper, setHeightCallback);
  }

});

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
