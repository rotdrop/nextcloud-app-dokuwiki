/**Embed a DokuWiki instance as app into ownCloud, intentionally with
 * single-sign-on.
 * 
 * @author Claus-Justus Heine
 * @copyright 2013 Claus-Justus Heine <himself@claus-justus-heine.de>
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

var DokuWikiEmbedded = DokuWikiEmbedded || {
    appName: 'dokuwikiembed',
    refreshInterval: 300,
};

DokuWikiEmbedded.Settings = DokuWikiEmbedded.Settings || {};

(function(window, $, DokuWikiEmbedded) {
    DokuWikiEmbedded.Settings.storeSettings = function(event, id) {
	event.preventDefault();
        if ($.trim($('#dwembedsettings .msg').html()) == '') {
            $('#dwembedsettings .msg').hide();
        }
	var post = $(id).serialize();
	$.post(OC.generateUrl('/apps/dokuwikiembedded/settings/admin/set'),
               post,
               function(data){
                   console.info("Got response data", data);
                   // if (data.status == 'success') {
	           //     $('#dwembedsettings .msg').html(data.data.message);
                   // } else {
	           //     $('#dwembedsettings .msg').html(data.data.message);
                   // }
                   // $('#dwembedsettings .msg').show();
	       }, 'json');
    };

})(window, jQuery, DokuWikiEmbedded);


$(document).ready(function(){

    $('#externalLocation').blur(function (event) {
        event.preventDefault();
        DokuWikiEmbedded.Settings.storeSettings(event, '#externalLocation');
        return false;
    });

    $('#authenticationRefreshInterval').blur(function (event) {
        event.preventDefault();
        DokuWikiEmbedded.Settings.storeSettings(event, '#authenticationRefreshInterval');
        return false;
    });
});
