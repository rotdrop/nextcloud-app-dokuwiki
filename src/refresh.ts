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

import { getCurrentUser } from '@nextcloud/auth';
import axios from '@nextcloud/axios';
import onDocumentLoaded from './toolkit/util/on-document-loaded.js';
import { generateUrl } from './toolkit/util/generate-url.js';
import { getInitialState } from './toolkit/services/InitialStateService.js';

const state = getInitialState();
let refreshInterval = state.authenticationRefreshInterval;

if (!(refreshInterval >= 30)) {
  console.error('Refresh interval too short', refreshInterval);
  refreshInterval = 30;
}

let refreshTimer = null;
const url = generateUrl('authentication/refresh');

const refreshHandler = async function() {
  await axios.post(url);
  console.info('DokuWiki refresh scheduled', refreshInterval * 1000);
  refreshTimer = setTimeout(refreshHandler, refreshInterval * 1000);
};

onDocumentLoaded(() => {
  if (getCurrentUser()) {
    console.info('Starting DokuWiki authentication refresh.');
    refreshTimer = setTimeout(refreshHandler, refreshInterval * 1000);
  } else {
    console.info('cloud-user appears unset.');
    clearTimeout(refreshTimer);
    refreshTimer = false;
  }
});
