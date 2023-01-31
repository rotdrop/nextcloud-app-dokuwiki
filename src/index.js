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

import '../style/doku-wiki.scss';

const webPrefix = appName;

(callback => {
  if (document.readyState !== 'loading') {
    callback();
  } else {
    document.addEventListener('DOMContentLoaded', callback);
  }
})(() => {
  console.info('DokuWiki webPrefix', webPrefix);
  const frameWrapper = document.getElementById(webPrefix + 'FrameWrapper');
  const frame = document.getElementById(webPrefix + 'Frame');

  const setHeightCallback = function() {
    const height = window.innerHeight - frame.getBoundingClientRect().top;
    frame.style.height = height + 'px';
    const outerDelta = frame.getBoundingClientRect().height - frame.clientHeight;
    if (outerDelta) {
      frame.style.height = (height - outerDelta) + 'px';
    }
  };

  if (frame) {
    frame.addEventListener('load', () => loadHandler(frame, frameWrapper, setHeightCallback));

    let resizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(setHeightCallback);
    });
    if (frame.contentWindow.document.querySelector('.logout')) {
      loadHandler(frame, frameWrapper, setHeightCallback);
    }
  }
});
