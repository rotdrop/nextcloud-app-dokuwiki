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

import { webPrefix } from './config.js';
import { loadHandler } from './doku-wiki.js';
import '../style/doku-wiki.css';

const jQuery = require('jquery');
const $ = jQuery;

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
