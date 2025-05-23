/**
 * @copyright Copyright (c) 2022-2025 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { appName } from '../../config.ts';
import {
  generateUrl as nextcloudGenerateUrl,
  generateOcsUrl as nextcloudGenerateOcsUrl,
} from '@nextcloud/router';
import type { UrlOptions } from '@nextcloud/router';

/**
 * Generate an absolute URL for this app.
 *
 * @param url The locate URL without app-prefix.
 *
 * @param urlParams Object holding url-parameters if url
 * contains parameters. "Excess" parameters will be appended as query
 * parameters to the URL.
 *
 * @param urlOptions Object with query parameters
 */
export const generateUrl = <T extends string>(url: T, urlParams?: Record<string, string|number|boolean|null>, urlOptions?: UrlOptions) => {
  // const str = '/image/{joinTable}/{ownerId}';
  let generated = nextcloudGenerateUrl('/apps/' + appName + '/' + url, urlParams, urlOptions);
  const queryParams = { ...(urlParams || {}) };
  for (const urlParam of url.matchAll(/{([^{}]*)}/g)) {
    delete queryParams[urlParam[1]];
  }
  const queryArray: string[] = [];
  for (const [key, value] of Object.entries(queryParams)) {
    try {
      queryArray.push(key + '=' + encodeURIComponent(value?.toString() || ''));
    } catch (e) {
      console.debug('STRING CONVERSION ERROR', e);
    }
  }
  if (queryArray.length > 0) {
    generated += '?' + queryArray.join('&');
  }
  return generated;
};

export const generateOcsUrl = <T extends string>(url: T, urlParams?: Record<string, string|number|boolean|null>, urlOptions?: UrlOptions) => {
  let generated = nextcloudGenerateOcsUrl('/apps/' + appName + '/' + url, urlParams, urlOptions);
  const queryParams = { ...urlParams };
  for (const urlParam of url.matchAll(/{([^{}]*)}/g)) {
    delete queryParams[urlParam[1]];
  }
  const queryArray: string[] = [];
  for (const [key, value] of Object.entries(queryParams)) {
    try {
      queryArray.push(key + '=' + encodeURIComponent(value?.toString() || ''));
    } catch (e) {
      console.debug('STRING CONVERSION ERROR', e);
    }
  }
  if (queryArray.length > 0) {
    generated += '?' + queryArray.join('&');
  }
  return generated;
};

export default generateUrl;
