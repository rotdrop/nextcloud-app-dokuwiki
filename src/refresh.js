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

import { state } from './state.js';

$(function() {

  state.refresh = function() {
    if (!(state.refreshInterval >= 30)) {
      console.error('Refresh interval too short', state.refreshInterval);
      state.refreshInterval = 30;
    }
    if (OC.currentUser) {
      const url = OC.generateUrl('apps/'+state.appName+'/authentication/refresh');
      state.refresh = function(){
        if (OC.currentUser) {
          $.post(url, {}).always(function () {
            console.info('DokuWiki refresh scheduled', state.refreshInterval * 1000);
            state.refreshTimer = setTimeout(state.refresh, state.refreshInterval*1000);
          });
        } else if (state.refreshTimer !== false) {
          clearTimeout(state.refreshTimer);
          state.refreshTimer = false;
        }
      };
      console.info('DokuWiki refresh scheduled', state.refreshInterval * 1000);
      state.refreshTimer = setTimeout(state.refresh, state.refreshInterval*1000);
    } else if (state.refreshTimer !== false) {
      console.info('OC.currentUser appears unset');
      clearTimeout(state.refreshTimer);
      state.refreshTimer = false;
    }
  };

  console.info('Starting DokuWiki refresh');
  state.refresh();
});

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
