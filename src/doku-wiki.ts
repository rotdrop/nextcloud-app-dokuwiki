/**
 * DokuWikiEmbedded -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020, 2021, 2023, 2025 Claus-Justus Heine
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

import { appName } from './config.ts';
import { translate as t } from '@nextcloud/l10n';

import '../style/doku-wiki.scss';

const webPrefix = appName;

interface LoadHandlerArgs {
  frame: HTMLIFrameElement,
  frameWrapper: HTMLElement,
  callback?: (frame: HTMLIFrameElement, frameWrapper: HTMLElement) => void,
}

/**
 * Called after the DokuWiki has been loaded by the iframe. We make
 * sure that external links are opened in another tab/window.
 *
 * @param data Destructuring object.
 *
 * @param data.frame TBD.
 *
 * @param data.frameWrapper TBD.
 *
 * @param data.callback TBD.
 *
 */
const loadHandler = function({ frame, frameWrapper, callback }: LoadHandlerArgs) {

  const frameDocument = frame.contentWindow!.document;

  frameDocument.querySelectorAll('.logout').forEach(el => el.remove());
  frameDocument.querySelectorAll('li:empty').forEach(el => el.remove());
  frameDocument.querySelectorAll('form.btn_logout').forEach(el => el.remove());
  frameDocument.querySelectorAll(':scope #dokuwiki__usertools li.user').forEach(el => el.remove());
  frameDocument.querySelectorAll(':scope #dokuwiki__usertools li.action.profile').forEach(el => el.remove());

  frameDocument.querySelectorAll('a').forEach(el => {
    if (el.hostname && el.hostname !== window.location.hostname) {
      el.setAttribute('target', '_blank');
    }
  });

  const previewDiv = frameDocument.querySelector('div.preview');
  if (previewDiv) {
    // make sure that links in the preview pane are NOT followed.
    previewDiv.querySelectorAll('a[class^="wikilink"]').forEach((el) => {
      el.addEventListener('click', function(event) {
        if (!event.target) {
          return;
        }
        event.stopPropagation();
        event.preventDefault();
        const target = event.target as HTMLAnchorElement;
        const href = target.getAttribute('href')!.replace(/^\/[^?]+\?id=(.*)$/, '$1');
        OC.dialogs.alert(
          t(appName, 'Links to wiki pages are disabled in preview mode.'),
          t(appName, 'Link to wiki page') + ' "' + href + '"');
      }, true);
    });
    previewDiv.querySelectorAll('a[class^="media"]').forEach(el => {
      el.addEventListener('click', function(event) {
        if (!event.target) {
          return;
        }
        event.stopPropagation();
        event.preventDefault();
        const target = event.target as HTMLAnchorElement;
        const href = target.getAttribute('href')!.replace(/^\/[^?]+\?id=(.*)$/, '$1');
        OC.dialogs.alert(
          t(appName, 'Links to media files are disabled in preview mode.'),
          t(appName, 'Link to wiki page') + ' "' + href + '"');
      }, true);
    });
  }

  if (typeof callback === 'undefined') {
    callback = function() {};
  }

  const loader = document.getElementById(webPrefix + 'Loader');
  if (loader) {
    loader.classList.add('fading');
  }
  frame.classList.remove('faded');
  callback(frame, frameWrapper);
};

export { loadHandler };
