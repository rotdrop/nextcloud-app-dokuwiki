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

import { appName } from '../config.ts';
import Vue from 'vue';
import Router from 'vue-router';
import type { RouterOptions } from 'vue-router';
import { generateUrl } from '@nextcloud/router';

Vue.use(Router);

const base = generateUrl('/apps/' + appName);

const options: RouterOptions = {
  mode: 'history',
  base,
  linkActiveClass: 'active',
  routes: [
    {
      path: '/:wikiPage',
      component: () => import('../DokuWikiWrapper.vue'),
      name: 'home',
      props: route => ({
        wikiPage: route.params.wikiPage,
        query: Object.fromEntries(Object.entries(route.query || {}).filter(([key]) => key !== 'id')),
      }),
    },
  ],
  scrollBehavior(to, _from, savedPosition) {
    if (savedPosition) {
      return { behavior: 'smooth', ...savedPosition };
    } else if (to.hash) {
      return {
        selector: to.hash,
        behavior: 'smooth',
      };
    }
  },
};

const router = new Router(options);

export default router;

// /camerata:administration:vorstandssitzungen:vorstandssitzungen
