/**
 * DokuWiki -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2025 Claus-Justus Heine
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
import { generateFilePath } from '@nextcloud/router';
import { getRequestToken } from '@nextcloud/auth';
import router from './router/router.ts';

import Vue from 'vue';
import App from './App.vue';

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line
__webpack_nonce__ = btoa(getRequestToken() || '')

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath(appName, '', '');

export default new Vue({
  el: '#content',
  render: h => h(App),
  // @ts-expect-error: 2769, passing the router es extension should be allowed.
  router,
});
