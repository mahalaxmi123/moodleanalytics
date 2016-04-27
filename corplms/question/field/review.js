/*
 * This file is part of Corplms LMS
 *
 * Copyright (C) 2010 onwards Corplms Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Alastair Munro <alastair.munro@corplmslms.com>
 * @package corplms
 * @subpackage corplms_core
 */
M.corplms_review = M.corplms_review || {

    Y: null,
    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {},

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function(Y, args){
        // save a reference to the Y instance (all of its dependencies included)
        this.Y = Y;

        if (args) {
            var jargs = Y.JSON.parse(args);
            for (var a in jargs) {
                if (Y.Object.owns(jargs, a)) {
                    this.config[a] = jargs[a];
                }
            }
        }
        // check jQuery dependency is available
        if (typeof $ === 'undefined') {
            throw new Error('M.corplms_review.init()-> jQuery dependency required for this module to function.');
        }

        var url = M.cfg.wwwroot + '/corplms/' + this.config.prefix + '/ajax/';
        if (this.config.datatype == 'goals') {
            var saveurl = url + 'reviewgoal.php?id=' + this.config.questionid + '&answerid=' + this.config.answerid +
                    '&formprefix=' + this.config.formprefix + '&subjectid=' + this.config.subjectid + '&update=';
        } else {
            var saveurl = url + 'review.php?id=' + this.config.questionid + '&answerid=' + this.config.answerid +
                    '&formprefix=' + this.config.formprefix + '&subjectid=' + this.config.subjectid + '&update=';
        }

        var handler = new corplmsDialog_handler_treeview_multiselect();
        handler.baseurl = url;

        handler._update = function(response) {
            this._dialog.hide();

            M.corplms_review.corplms_question_update(response);
        };

        handler._selectall = function() {
          $('span.clickable', '#'+this._title).each(function(){
            $(this).click();
          });
        }

        // extend handler base class function to add extra button
        handler.first_load = function() {
          // call super function first
          corplmsDialog_handler_treeview_multiselect.prototype.first_load.call(this);

          // add our extra button
          if (right_to_left()) {
            var $button = $('<button type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button" aria-disabled="false" style="float: left;"><span class="ui-button-text">' + M.util.get_string('selectall','corplms_question') + '</span></button>');
          } else {
            var $button = $('<button type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button" aria-disabled="false" style="float: right;"><span class="ui-button-text">' + M.util.get_string('selectall','corplms_question') + '</span></button>');
          }

          $button.click(function() {
            handler._selectall();
          })

          if ($('.planselector', this._container).length) {
            $('.planselector', this._container).before($button).css('width', '78%');
          } else if ($('.simpleframeworkpicker', this._container).length) {
            $('.simpleframeworkpicker', this._container).before($button).css('width', '78%');
          } else {
            $('.treeview-wrapper', this._container).before($button).css('width', '78%');
          }

        }

        var buttonsObj = {};
        buttonsObj[M.util.get_string('cancel','moodle')] = function() { handler._cancel(); }
        buttonsObj[M.util.get_string('save','corplms_core')] = function() { handler._save(saveurl); }

        corplmsDialogs[this.config.formprefix] = new corplmsDialog(
            this.config.formprefix,
            'id_' + this.config.formprefix + '_choosereviewitem',
            {
                buttons: buttonsObj,
                title: '<h2>' + M.util.get_string('choose' + this.config.datatype + 'review', 'corplms_question') + '</h2>'
            },
            url + 'reviewselect.php?id=' + this.config.questionid + '&answerid=' + this.config.answerid +
                    '&subjectid=' + this.config.subjectid,
            handler
        );

        M.corplms_review.addActions(this.config.formprefix + '_' + this.config.prefix + '_review');

        // Set up handler to keep all review statuses of the same items in sync.
        $(document).on('change', '.rating_selector', function() {
            var identifier = $(this).attr("class").match(/rating_item[\w-]*\b/);
            var newvalue = $(this).val();
            $("." + identifier).val(newvalue);
        });
    },

    /**
     * Update the table on the calling page, and remove/add no items notices
     *
     * @param   string  HTML response
     * @return  void
     */
    corplms_question_update: function(response) {
        var responseobj = $($.parseHTML(response));

        var longid = responseobj.find('h3').attr('class');

        if(typeof longid !== "undefined") {
            var thirdunderline = longid.indexOf('_', longid.indexOf('_', longid.indexOf('_') + 1) + 1);
            var shortid = longid.substr(0, thirdunderline);
            $('#id_' + shortid + '_choosereviewitem').closest('fieldset').after(responseobj.find('fieldset'));
        }

        M.corplms_review.addActions(longid);
    },

    /**
     * modal popup for deleting an item
     * @param {String} url The URL to get to delete the item.
     * @param {Object} el optional The DOM element being deleted, for fancy removal from the display.
     */
    modalDelete: function(url, id, el) {
      this.Y.use('panel', function(Y) {
        var panel = new Y.Panel({
          bodyContent: M.util.get_string('removeconfirm', 'corplms_question'),
          width        : 300,
          zIndex       : 5,
          centered     : true,
          modal        : true,
          render       : true,
          buttons: [
            {
              name: "confirm",
              value  : 'Yes',
              section: Y.WidgetStdMod.FOOTER,
              action : function (e) {
                e.preventDefault();
                $.get(url).done(function(data) {
                  if (data == 'success') {
                    el.slideUp(250, function(){
                      el.remove();
                    });
                  }
                  panel.destroy(true);
                });
              }
            },
            {
              name: "deny",
              value  : 'No',
              section: Y.WidgetStdMod.FOOTER,
              action : function (e) {
                e.preventDefault();
                panel.destroy(true);
              }
            }
          ]
        });
        panel.getButton("confirm").removeClass("yui3-button");
        panel.getButton("deny").removeClass("yui3-button");
        panel.show();

      });
    },

    addActions: function(id) {
        $('.' + id).find('a.action-icon.delete').on('click', function(){
            M.corplms_review.modalDelete($(this).attr('href'), $(this).attr('data-reviewitemid'), $(this).closest('fieldset'));
            return false;
        });
    }
};
