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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Dave Wallace <dave.wallace@kineo.co.nz>
 * @package corplms
 * @subpackage program
 */
M.corplms_programmessages = M.corplms_programmessages || {

    Y: null,
    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {},

    //
    corplmsDialog_savechanges: null,

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function(Y, args){

        var module = this;

        // save a reference to the Y instance (all of its dependencies included)
        this.Y = Y;

        // if defined, parse args into this module's config object
        if (args) {
            var jargs = Y.JSON.parse(args);
            for (var a in jargs) {
                if (Y.Object.owns(jargs, a)) {
                    this.config[a] = jargs[a];
                }
            }
        }

        // check if required param id is available
        if (!this.config.id) {
            throw new Error('M.corplms_programmessages.init()-> Required config \'id\' not available.');
        }

        // check jQuery dependency and continue with setup
        if (typeof $ === 'undefined') {
            throw new Error('M.corplms_programmessages.init()-> jQuery dependency required for this module to function.');
        }


        // The save changes confirmation dialog
        this.corplmsDialog_savechanges = function() {

            // Setup the handler
            var handler = new corplmsDialog_handler();

            // Store reference to this
            var self = this;

            var buttonsObj = {};
            buttonsObj[M.util.get_string('editmessages', 'corplms_program')] = function() { handler._cancel() };
            buttonsObj[M.util.get_string('saveallchanges', 'corplms_program')] = function() { self.save() };

            // Call the parent dialog object and link us
            corplmsDialog.call(
            this,
            'savechanges-dialog',
            'unused', // buttonid unused
            {
                buttons: buttonsObj,
                title: '<h2>'+ M.util.get_string('confirmmessagechanges', 'corplms_program') +'</h2>'
            },
            'unused', // default_url unused
            handler
            );

            this.old_open = this.open;
            this.open = function(html, table, rows) {
                // Do the default open first to get everything ready
                this.old_open();

                this.dialog.height(150);

                // Now load the custom html content
                this.dialog.html(html);

                this.table = table;
                this.rows = rows;
            }

            // Don't load anything
            this.load = function(url, method) {
            }
        }

        // attach a function to the page to prevent unsaved changes from being lost
        // when navigating away
        window.onbeforeunload = function(e) {

            var modified = module.isFormModified();
            var str = M.util.get_string('youhaveunsavedchanges', 'corplms_program');
            var e = e || window.event;

            if (modified == true) {
                // For IE and Firefox (before version 4)
                if (e) {
                    e.returnValue = str;
                }
                // For Safari
                return str;
            }
        };

        // remove the 'unsaved changes' confirmation when submitting the form
        $('form[name="form_prog_messages"]').submit(function(){
            window.onbeforeunload = null;
        });

        // Remove the 'unsaved changes' confirmation when clicking th 'Cancel program management' link
        $('#cancelprogramedits').click(function(){
            window.onbeforeunload = null;
            return true;
        });

        // Add a function to launch the save changes dialog
        $('input[name="savechanges"]').click(function() {
            return module.handleSaveChanges();
        });

        corplmsDialogs['savechanges'] = new this.corplmsDialog_savechanges();


        // Set up the display of messages
        $('input[name=cancel]').css('display', 'none');
        $('input[name=update]').css('display', 'none');

        this.storeInitialFormValues();
    },

    /**
     *
     */
    handleSaveChanges: function(){

        // no need to display the confirmation dialog if there are no changes to save
        if (!this.isFormModified()) {
            window.onbeforeunload = null;
            return true;
        }

        var dialog = corplmsDialogs['savechanges'];

        if (dialog.savechanges == true) {
            window.onbeforeunload = null;
            return true;
        }

        dialog.open(M.util.get_string('tosavemessages', 'corplms_program'));
        dialog.save = function() {
            dialog.savechanges = true;
            this.hide();
            $('input[name="savechanges"]').trigger('click');
        }

        return false;
    },

    /**
     * Stores the initial values of the form when the page is loaded
     */
    storeInitialFormValues: function(){
        var form = $('form[name="form_prog_messages"]');

        $('input[type="text"], textarea, select', form).each(function() {
            $(this).attr('initialValue', $(this).val());
        });

        $('input[type="checkbox"]', form).each(function() {
            var checked = $(this).is(':checked') ? 1 : 0;
            $(this).attr('initialValue', checked);
        });
    },

    /**
     * Checks if the form is modified by comparing the initial and current values
     */
    isFormModified: function(){
        var form = $('form[name="form_prog_messages"]');
        var isModified = false;

        // Check if text inputs or selects have been changed
        $('input[type="text"], select', form).each(function() {
            if ($(this).attr('initialValue') != $(this).val()) {
                isModified = true;
            }
        });

        // Check if check boxes have changed
        $('input[type="checkbox"]', form).each(function() {
            var checked = $(this).is(':checked') ? 1 : 0;
            if ($(this).attr('initialValue') != checked) {
                isModified = true;
            }
        });

        // Check if textareas have been changed
        $('textarea', form).each(function() {
            // See if there's a tiny MCE instance for this text area
            var instance = undefined;
            if (typeof(tinyMCE) != 'undefined') {
                instance = tinyMCE.getInstanceById($(this).attr('id'));
            }
            if (instance != undefined  && typeof instance.isDirty == 'function') {
                if (instance.isDirty()) {
                    isModified = true;
                }
            } else {
                // normal textarea (not tinyMCE)
                if ($(this).attr('initialValue') != $(this).val()) {
                    isModified = true;
                }
            }
        });

        // Check if messages ahve been changed as a result of the form being submitted
        var messageschanged = $('input[name="messageschanged"]').val();
        if (messageschanged == 1) {
            isModified = true;
        }

        return isModified;
    }
};

