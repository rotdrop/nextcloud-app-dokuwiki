/**
 * DokuWikiEmbedded -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2020, 2021, 2022, 2023, 2025 Claus-Justus Heine
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
import { loadHandler } from './doku-wiki.ts';
import { failData as ajaxFailData } from './toolkit/util/ajax.js';
import { generateUrl } from './toolkit/util/generate-url.js';
import jQuery from './toolkit/util/jquery.js';
import { translate as t } from '@nextcloud/l10n';

import '../style/doku-wiki.scss';
import '../style/doku-wiki-popup.scss';

require('jquery-ui');
// require('jquery-ui/ui/effect');
require('jquery-ui/ui/widgets/dialog');

const $ = jQuery;
const webPrefix = appName;

interface EnhancedJQTextArea extends JQuery {
  oldwidth?: string,
  oldheight?: string,
  resize_timeout?: ReturnType<typeof setTimeout>,
}

interface EnhancedHTMLIFrameElement extends HTMLIFrameElement {
  contentHeight?: number,
  heightTimer?: ReturnType<typeof setInterval>,
  heightChecker?: () => void,
}

/**
 * Unfortunately, the textare element does not fire a resize
 * event. This function emulates one.
 *
 * @param textarea jQuery descriptor for the textarea element
 *
 * @param delay Optional, defaults to 50. If true, fire the event
 * immediately, if set, then this is a delay in ms.
 */
const textareaResize = function(textarea: EnhancedJQTextArea, delay?: number) {
  if (delay === undefined) {
    delay = 50; // ms
  }
  textarea.off('mouseup mousemove');
  textarea.on('mouseup mousemove', function() {
    if (textarea.oldwidth === undefined) {
      textarea.oldwidth = textarea[0].style.width;
    }
    if (textarea.oldheight === undefined) {
      textarea.oldheight = textarea[0].style.height;
    }
    if (textarea[0].style.width !== textarea.oldwidth
        || textarea[0].style.height !== textarea.oldheight) {
      if (delay > 0) {
        if (textarea.resize_timeout) {
          clearTimeout(textarea.resize_timeout);
        }
        textarea.resize_timeout = setTimeout(function() {
          textarea.trigger('resize');
        }, delay);
      } else {
        textarea.trigger('resize');
      }
      textarea.oldwidth = textarea[0].style.width;
      textarea.oldheight = textarea[0].style.height;
    }
  });
};

/**
 * Show the given wiki-page in a jQuery dialog popup. The page name
 * is sent to an Ajax callback which generates a suitable iframe
 * which then finally holds the wiki contents.
 *
 * @param options Object object
 *
 * @param options.wikiPage TBD.
 *
 * @param options.popupTitle TBD.
 *
 * @param options.modal TBD.
 *
 * @param options.cssClass TBD.
 *
 * @param openCallback Optional callback to be call on
 * open. The callback will get the element holding the dialog content
 * as argument and the dialog widget itself. The callback is called
 * BEFORE the iframe is loaded.
 *
 * @param closeCallback TBD.
 */
const wikiPopup = function(
  options: { wikiPage: string, popupTitle?: string, modal: boolean, cssClass: string },
  openCallback: (dialogHolder: JQuery, dialogWidget: JQuery) => void,
  closeCallback: () => void,
) {
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
    .done(function(htmlContent, _textStatus, _request) {
      const containerId = webPrefix + '_popup';
      const dialogHolder = $('<div id="' + containerId + '"></div>');

      dialogHolder.html(htmlContent);
      $('body').append(dialogHolder);
      // dialogHolder = $(containerSel);
      // @ts-expect-error 2339
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
        classes: {
          'ui-dialog': [
            webPrefix + '-page-popup',
            options.cssClass,
          ].join(' '),
        },
        resizable: false,
        open() {
          const dialogHolder = $(this);
          // @ts-expect-error 2339
          const dialogWidget = dialogHolder.dialog('widget');
          const frameWrapper = dialogHolder.find('#' + webPrefix + 'FrameWrapper');
          const frame = dialogHolder.find('#' + webPrefix + 'Frame') as JQuery<HTMLIFrameElement>;
          const titleHeight = dialogWidget.find('.ui-dialog-titlebar').outerHeight();

          dialogWidget.draggable('option', 'containment', '#content');

          if (typeof openCallback === 'function') {
            openCallback(dialogHolder, dialogWidget);
          }

          frame.on('load', function() {
            // eslint-disable-next-line @typescript-eslint/no-this-alias
            const self = this as EnhancedHTMLIFrameElement;
            const contents = $(self).contents();

            // Remove some more stuff. The popup is meant for a
            // single page.
            contents.find('#dokuwiki__header div.pad').remove();
            contents.find('#dokuwiki__header').css('padding', '2.5em 0px 0px');
            contents.find('#dokuwiki__footer').remove();

            // <HACK REASON="determine the height of the iframe contents">
            dialogHolder.height('');

            const scrollHeight = self.contentWindow?.document.body.scrollHeight || 0;
            frame.css({
              height: scrollHeight + 'px',
              overflow: 'hidden',
            });
            if (frameWrapper.css('height') === '0px') {
              frameWrapper.css({
                height: 'auto',
                // display: 'none',
              });
            }
            // </HACK>

            self.contentHeight = -1;

            loadHandler({
              frame: frame[0],
              frameWrapper: frameWrapper[0],
              callback: () => {
                // dialogHolder.dialog('option', 'height', 'auto');
                // dialogHolder.dialog('option', 'width', 'auto');
                const newHeight = dialogWidget.height() - titleHeight;
                dialogHolder.height(newHeight);

                const editForm = contents.find('form#dw__editform');
                const editArea = editForm.find('textarea');
                if (editArea.length > 0) {
                  const wysiwygArea = editForm.find('.prosemirror_wrapper');
                  const wysiwygToggle = editForm.find('.button.plugin_prosemirror_useWYSIWYG');
                  wysiwygArea.css({
                    overflow: 'auto',
                  });
                  editForm.css({
                    float: 'right',
                  });
                  // wysiwygArea.css('max-height', dialogHolder.height() + 'px');

                  self.heightChecker = function() {
                    if (self.contentWindow === undefined) {
                      if (self.heightTimer !== undefined) {
                        clearInterval(self.heightTimer);
                        self.heightTimer = undefined;
                      }
                      return;
                    }
                    const height = self.contentWindow?.document.body.scrollHeight || 0;
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
                    const scrollHeight = self.contentWindow?.document.body.scrollHeight || 0;
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
              },
            });
          });
        },
        close() {
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
};

export { wikiPopup };
