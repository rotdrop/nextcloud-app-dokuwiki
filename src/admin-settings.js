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
import { ajaxFailData } from './doku-wiki.js';

$(function(){

  const storeSettings = function(event, $id) {
    const webPrefix = state.webPrefix;
    const msg = $('#' + webPrefix + 'settings .msg');
    if ($.trim(msg.html()) === '') {
      msg.hide();
    }
    let post = $id.serialize();
    const cbSelector = 'input:checkbox:not(:checked)';
    $id.find(cbSelector).addBack(cbSelector).each(function(index) {
      console.info('unchecked?', index, $(this));
      if (post !== '') {
        post += '&';
      }
      post += $(this).attr('name') + '=' + 'off';
    });
    $.post(OC.generateUrl('/apps/' + state.appName + '/settings/admin/set'), post)
      .done(function(data) {
        console.info('Got response data', data);
        if (data.value) {
          $id.val(data.value);
        }
        if (data.message) {
          msg.html(data.message);
          msg.show();
        }
      })
      .fail(function(xhr, status, errorThrown) {
        const response = ajaxFailData(xhr, status, errorThrown);
        console.error(response);
        if (response.message) {
          msg.html(response.message);
          msg.show();
        }
      });
    return false;
  };

  const inputs = {
    externalLocation: 'blur',
    authenticationRefreshInterval: 'blur',
    enableSSLVerify: 'change'
  };

  for (const input in inputs) {
    const $id = $('#' + input);
    const event = inputs[input];

    console.info(input, event);

    $id.on(event, function(event) {
      event.preventDefault();
      storeSettings(event, $id);
      return false;
    });
  }
});

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
