/**
 * DokuWikiEmbedded -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021 Claus-Justus Heine <himself@claus-justus-heine.de>
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

import { appName, webPrefix } from './config.js';
import { loadHandler } from './doku-wiki.js';
import ajaxFailData from './ajax.js';
import generateUrl from './generate-url.js';

import '../style/doku-wiki.css';
import '../style/doku-wiki-popup.css';

const jQuery = require('jquery');
const $ = jQuery;
require('jquery-ui');
// require('jquery-ui/ui/effect');
require('jquery-ui/ui/widgets/dialog');
require('./nextcloud/jquery/requesttoken.js');

/**
 * Unfortunately, the textare element does not fire a resize
 * event. This function emulates one.
 *
 * @param {Object} textarea jQuery descriptor for the textarea element
 *
 * @param {Float} delay Optional, defaults to 50. If true, fire the event
 * immediately, if set, then this is a delay in ms.
 */
const textareaResize = function(textarea, delay) {
  if (delay === undefined) {
    delay = 50; // ms
  }
  textarea.off('mouseup mousemove');
  textarea.on('mouseup mousemove', function() {
    if (textarea.oldwidth === null) {
      textarea.oldwidth = textarea.style.width;
    }
    if (textarea.oldheight === null) {
      textarea.oldheight = textarea.style.height;
    }
    if (textarea.style.width !== textarea.oldwidth
        || textarea.style.height !== textarea.oldheight) {
      if (delay > 0) {
        if (textarea.resize_timeout) {
          clearTimeout(textarea.resize_timeout);
        }
        textarea.resize_timeout = setTimeout(function() {
          $(textarea).resize();
        }, delay);
      } else {
        $(textarea).resize();
      }
      textarea.oldwidth = textarea.style.width;
      textarea.oldheight = textarea.style.height;
    }
  });
};

/**
 * Show the given wiki-page in a jQuery dialog popup. The page name
 * is sent to an Ajax callback which generates a suitable iframe
 * which then finally holds the wiki contents.
 *
 * @param options Object with the following components:
 * {
 *   wikiPage: 'page',
 *   popupTitle: 'title',
 *   modal: true/false
 * }
 *
 * @param {function} openCallback Optional callback to be call on
 * open. The callback will get the element holding the dialog content
 * as argument and the dialog widget itself. The callback is called
 * BEFORE the iframe is loaded.
 *
 * @param {function} closeCallback TBD.
 *
 * @returns bool
 */
const wikiPopup = function(options, openCallback, closeCallback) {
  const parameters = {
    wikiPage: options.wikiPage,
    popupTitle: options.popupTitle,
    cssClass: 'popup',
    iframeAttributes: 'scrolling="no"',
  };
  $.post(
    generateUrl('page/frame/blank'),
    parameters)
    .fail(function(xhr, status, errorThrown) {
      const response = ajaxFailData(xhr, status, errorThrown);
      // console.log(response);
      let info = '';
      if (typeof response.message !== 'undefined') {
        info = response.message;
      } else {
        info = t(appName, 'Unknown error :(');
      }
      if (typeof response.error !== 'undefined' && response.error === 'exception') {
        info += '<p><pre>' + response.exception + '</pre>';
        info += '<p><pre>' + response.trace + '</pre>';
      }
      OC.dialogs.alert(info, t('dokluwikiembed', 'Error'));
    })
    .done(function(htmlContent, textStatus, request) {
      const containerId = webPrefix + '_popup';
      const dialogHolder = $('<div id="' + containerId + '"></div>');

      dialogHolder.html(htmlContent);
      $('body').append(dialogHolder);
      // dialogHolder = $(containerSel);
      dialogHolder.dialog({
        title: options.popupTitle,
        position: {
          my: 'middle top',
          at: 'middle bottom+50px',
          of: '#header',
        },
        width: 'auto',
        height: 'auto',
        modal: options.modal,
        closeOnEscape: false,
        dialogClass: webPrefix + '-page-popup ' + options.cssClass,
        resizable: false,
        open() {
          const dialogHolder = $(this);
          const dialogWidget = dialogHolder.dialog('widget');
          const frameWrapper = dialogHolder.find('#' + webPrefix + 'FrameWrapper');
          const frame = dialogHolder.find('#' + webPrefix + 'Frame');
          const titleHeight = dialogWidget.find('.ui-dialog-titlebar').outerHeight();

          dialogWidget.draggable('option', 'containment', '#content');

          if (typeof openCallback === 'function') {
            openCallback(dialogHolder, dialogWidget);
          }

          frame.on('load', function() {
            const self = this;
            const contents = $(self).contents();

            // Remove some more stuff. The popup is meant for a
            // single page.
            contents.find('#dokuwiki__header div.pad').remove();
            contents.find('#dokuwiki__header').css('padding', '2.5em 0px 0px');
            contents.find('#dokuwiki__footer').remove();

            // <HACK REASON="determine the height of the iframe contents">
            dialogHolder.height('');

            const scrollHeight = self.contentWindow.document.body.scrollHeight;
            frame.css({
              height: scrollHeight + 'px',
              overflow: 'hidden',
            });
            if (frameWrapper.css('height') === '0px') {
              frameWrapper.css({
                height: 'auto',
                display: 'none',
              });
            }
            // </HACK>

            self.contentHeight = -1;

            loadHandler(frame, frameWrapper, function() {
              // dialogHolder.dialog('option', 'height', 'auto');
              // dialogHolder.dialog('option', 'width', 'auto');
              const newHeight = dialogWidget.height() - titleHeight;
              dialogHolder.height(newHeight);

              const editArea = contents.find('textarea');
              if (editArea.length > 0) {
                const wysiwygArea = contents.find('.prosemirror_wrapper');
                const wysiwygToggle = contents.find('.button.plugin_prosemirror_useWYSIWYG');
                wysiwygArea.css('overflow', 'auto');
                // wysiwygArea.css('max-height', dialogHolder.height() + 'px');

                self.heightChecker = function() {
                  if (self.contentWindow === undefined) {
                    if (self.heightTimer !== undefined) {
                      clearInterval(self.heightTimer);
                      self.heightTimer = undefined;
                    }
                    return;
                  }
                  const height = self.contentWindow.document.body.scrollHeight;
                  if (height !== self.contentHeight) {
                    console.debug('new height', height, self.contentHeight, frame.css('height'));
                    self.contentHeight = height;
                    frame.css({ height: height + 'px' });
                    dialogHolder.dialog('option', 'height', 'auto');
                    dialogHolder.dialog('option', 'width', 'auto');
                    const newHeight = dialogWidget.height() - titleHeight;
                    dialogHolder.height(newHeight);
                  }
                };
                self.heightTimer = setInterval(self.heightChecker, 100);

                wysiwygToggle.on('click', function() {
                  if (editArea.is(':visible')) {
                    // const editAreaHeight = editArea.height();
                    // wysiwygArea.height(editAreaHeight+28); // button height
                  } else {
                    wysiwygArea.css({ height: '' });
                  }
                });

                // Unfortunately, there is no resize event on
                // textareas. We simulate one
                textareaResize(editArea);

                editArea.on('resize', function() {
                  const scrollHeight = self.contentWindow.document.body.scrollHeight;
                  frame.css({
                    height: scrollHeight + 'px',
                    overflow: 'hidden',
                  });
                  dialogHolder.dialog('option', 'height', 'auto');
                  dialogHolder.dialog('option', 'width', 'auto');
                  const newHeight = dialogWidget.height() - titleHeight;
                  dialogHolder.height(newHeight);
                });
              } else {
                if (self.heightTimer !== undefined) {
                  clearInterval(self.heightTimer);
                  self.heightTimer = undefined;
                }
              }

            });
          });
        },
        close() {
          $('.tipsy').remove();
          const dialogHolder = $(this);

          dialogHolder.dialog('close');
          dialogHolder.dialog('destroy').remove();

          if (typeof closeCallback === 'function') {
            closeCallback();
          }

          return false;
        },
      });
      return false;
    });
  return true;
};

export { wikiPopup };

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
