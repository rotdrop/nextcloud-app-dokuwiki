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

import { state, cloudUser } from './config.js';
import generateUrl from './toolkit/util/generate-url.js';
import jQuery from './toolkit/util/jquery.js';

const $ = jQuery;

/**
 * Actually $().
 */
function start() {

  state.refresh = function() {
    if (!(state.refreshInterval >= 30)) {
      console.error('Refresh interval too short', state.refreshInterval);
      state.refreshInterval = 30;
    }
    if (cloudUser) {
      const url = generateUrl('authentication/refresh');
      state.refresh = function() {
        if (cloudUser) {
          $.post(url, {}).always(function() {
            console.info('DokuWiki refresh scheduled', state.refreshInterval * 1000);
            state.refreshTimer = setTimeout(state.refresh, state.refreshInterval * 1000);
          });
        } else if (state.refreshTimer !== false) {
          clearTimeout(state.refreshTimer);
          state.refreshTimer = false;
        }
      };
      console.info('DokuWiki refresh scheduled', state.refreshInterval * 1000);
      state.refreshTimer = setTimeout(state.refresh, state.refreshInterval * 1000);
    } else if (state.refreshTimer !== false) {
      console.info('OC.currentUser appears unset');
      clearTimeout(state.refreshTimer);
      state.refreshTimer = false;
    }
  };

  console.info('Starting DokuWiki refresh');
  state.refresh();

}

$(start);
