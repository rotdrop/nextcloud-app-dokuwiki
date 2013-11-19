<?php

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

?>

<div id="dokuwiki_container">

<!-- <pre>
<?php echo $_['wikiURL']?>
</pre> -->

<img src="<?php echo \OCP\Util::imagePath($_['app'], 'loader.gif'); ?>" id="loader">
<iframe style="display:none;overflow:auto"
        src="<?php echo $_['wikiURL'];?>"
        id="dokuwikiFrame"
        name="dokuwikiembed" width="100%">
</iframe>

</div>

