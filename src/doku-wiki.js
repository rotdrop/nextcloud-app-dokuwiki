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

import { state, appName } from './state.js';

/**
 * Fetch data from an error response.
 *
 * @param {Object} xhr jqXHR, see fail() method of jQuery ajax.
 *
 * @param {Object} status from jQuery, see fail() method of jQuery ajax.
 *
 * @param {Object} errorThrown, see fail() method of jQuery ajax.
 *
 * @returns {Array}
 */
const ajaxFailData = function(xhr, status, errorThrown) {
  const ct = xhr.getResponseHeader('content-type') || '';
  let data = {
    error: errorThrown,
    status,
    message: t(appName, 'Unknown JSON error response to AJAX call: {status} / {error}'),
  };
  if (ct.indexOf('html') > -1) {
    console.debug('html response', xhr, status, errorThrown);
    console.debug(xhr.status);
    data.message = t(appName, 'HTTP error response to AJAX call: {code} / {error}', {
      code: xhr.status, error: errorThrown,
    });
  } else if (ct.indexOf('json') > -1) {
    const response = JSON.parse(xhr.responseText);
    // console.info('XHR response text', xhr.responseText);
    // console.log('JSON response', response);
    data = {...data, ...response };
  } else {
    console.log('unknown response');
  }
  // console.info(data);
  return data;
};

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
  if (typeof delay == 'undefined') {
    delay = 50; // ms
  }
  textarea.off('mouseup mousemove');
  textarea.on('mouseup mousemove', function() {
    if (textarea.oldwidth === null) {
      textarea.oldwidth  = textarea.style.width;
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
 * Called after the DokuWiki has been loaded by the iframe. We make
 * sure that external links are opened in another tab/window.
 *
 * @param {Object} frame TBD.
 *
 * @param {Object} frameWrapper TBD.
 *
 * @param {Function} callback TBD.
 *
 */
const loadCallback = function(frame, frameWrapper, callback) {
  const contents = frame.contents();
  const webPrefix = state.webPrefix;

  contents.find('.logout').remove();
  contents.find('li:empty').remove();
  contents.find('form.btn_logout').remove();
  contents.find('#dokuwiki__usertools li.user').remove();
  contents.find('#dokuwiki__usertools a.action.profile').remove();

  // Make sure all external links are opened in another window
  contents.find('a').filter(function() {
    return this.hostname && this.hostname !== window.location.hostname;
  }).each(function() {
    $(this).attr('target','_blank');
  });

  // make sure that links in the preview pane are NOT followed.
  contents.find('div.preview').find('a[class^="wikilink"]').off('click').on('click', function() {
    let wikiPage = $(this).attr('href');
    wikiPage = wikiPage.replace(/^\/[^?]+\?id=(.*)$/, '$1');
    OC.dialogs.alert(
      t(appName, 'Links to wiki-pages are disabled in preview mode.'),
      t(appName, 'Link to Wiki-Page') + ' "' + wikiPage + '"');
    return false;
  });

  contents.find('div.preview').find('a[class^="media"]').off('click').on('click', function() {
    let mediaPage = $(this).attr('href');
    mediaPage = mediaPage.replace(/^\/[^?]+\?id=(.*)$/, '$1');
    OC.dialogs.alert(
      t(appName, 'Links to media-files are disabled in preview mode.'),
      t(appName, 'Link to Media') + ' "' + mediaPage + '"');
    return false;
  });

  if (typeof callback === 'undefined') {
    callback = function() {};
  }

  const loader = $('#' + webPrefix + 'Loader');
  if (frameWrapper.is(':hidden')) {
    loader.fadeOut('slow', function() {
      frameWrapper.slideDown('slow', function() {
        callback(frame, frameWrapper);
      });
    });
  } else {
    loader.fadeOut('slow');
    callback(frame, frameWrapper);
  }
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
 * @param {Function} openCallback Optional callback to be call on
 * open. The callback will get the element holding the dialog content
 * as argument and the dialog widget itself. The callback is called
 * BEFORE the iframe is loaded.
 */
const wikiPopup = function(options, openCallback, closeCallback) {
  const parameters = {
    wikiPage: options.wikiPage,
    popupTitle: options.popupTitle,
    cssClass: 'popup',
    iframeAttributes: 'scrolling="no"',
  };
  const webPrefix = state.webPrefix;
  $.post(
    OC.generateUrl('/apps/' + appName + '/page/frame/blank'),
    parameters)
    .fail(function(xhr, status, errorThrown) {
      const response = ajaxFailData(xhr, status, errorThrown);
      console.log(response);
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
        dialogClass: webPrefix + '-page-popup '+options.cssClass,
        resizable: false,
        open() {
          const dialogHolder = $(this);
          const dialogWidget = dialogHolder.dialog('widget');
          const frameWrapper = dialogHolder.find('#' + webPrefix + 'FrameWrapper');
          const frame = dialogHolder.find('#' + webPrefix + 'Frame');
          const titleHeight = dialogWidget.find('.ui-dialog-titlebar').outerHeight();

          dialogWidget.draggable('option', 'containment', '#content');

          if (typeof openCallback == 'function') {
            openCallback(dialogHolder, dialogWidget);
          }

          frame.load(function() {
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
              overflow: 'hidden'
            });
            if (frameWrapper.css('height') == '0px') {
              frameWrapper.css({
                height: 'auto',
                display: 'none',
              });
            }
            // </HACK>

            self.contentHeight = -1;

            loadCallback(frame, frameWrapper, function() {
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
                  if (self.contentWindow == undefined) {
                    if (self.heightTimer != undefined) {
                      clearInterval(self.heightTimer);
                      self.heightTimer = undefined;
                    }
                    return;
                  }
                  const height = self.contentWindow.document.body.scrollHeight;
                  if (height != self.contentHeight) {
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
                if (self.heightTimer != undefined) {
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

          if (typeof closeCallback == 'function') {
            closeCallback();
          }

          return false;
        },
      });
      return false;
    });
  return true;
};

export { loadCallback, wikiPopup, ajaxFailData };

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
