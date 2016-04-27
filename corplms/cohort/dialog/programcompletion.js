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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package corplms
 * @subpackage cohort
 */
/**
 * This file contains the javascript that lets you set the program completion date
 * for a program which is in a cohort's enrolled learning
 */

M.corplms_cohortprogramcompletion = M.corplms_cohortprogramcompletion || {

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
    init: function(Y, args) {
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

        // check jQuery dependency is available
        if (typeof $ === 'undefined') {
            throw new Error('M.corplms_cohortcompletion.init()-> jQuery dependency required for this module.');
        }

        /// the completion dialog definition
        corplmsDialog_completion = function() {

            //this.item = item;
            //this.url = url + 'choose_completion.php';

            // Setup the handler
            var handler = new corplmsDialog_completion_handler();

            // Store reference to this
            var thisdialog = this;

            var dbuttons = {};
            dbuttons[M.util.get_string('cancel','moodle')] = function() { handler._cancel() }

            // Call the parent dialog object and link us
            corplmsDialog.call(
                this,
                'completion-dialog',
                'unused', // buttonid unused
                {
                    buttons: dbuttons,
                    title: '<h2>' + M.util.get_string('completioncriteria', 'corplms_program') + '</h2>'
                },
                this.url,
                handler
            );

            this.handler._container = $('#completion-dialog');

            this.old_open = this.open;
            this.open = function() {
                this.old_open();
                this.dialog.height(150);
            }

            this.update_completiontime = function(completiontime, completionevent, completioninstance) {
            // Update the hidden inputs

                var url = M.cfg.wwwroot + '/corplms/cohort/dialog/updateprogramcompletion.php' +
                          '?programid=' + this.programid +
                          '&cohortid=' + this.cohortid +
                          '&completiontime=' + completiontime +
                          '&completionevent=' + completionevent +
                          '&completioninstance=' + completioninstance +
                          '&sesskey=' + M.cfg.sesskey;

                var original = this.completionlink.html();
                this.completionlink.html('Loading..');

                $.get(url, function(data) {
                    if (data == 'error') {
                        // Put back to the original
                        this.completionlink.html(original);
                    } else {
                        thisdialog.completionlink.html(data);
                        thisdialog.completiontime.val(completiontime);
                        thisdialog.completionevent.val(completionevent);
                        thisdialog.completioninstance.val(completioninstance);
                    }
                });
            }

            this.handler._container.on('click', '.fixeddate', function() {
                var completiontime = $('.completiontime', thisdialog.handler._container).val();
                var completionevent = M.corplms_cohortprogramcompletion.config.COMPLETION_EVENT_NONE;
                var completioninstance = 0;

                var dateformat = new RegExp(M.util.get_string('datepickerlongyearregexjs', 'corplms_core'));
                if (dateformat.test(completiontime) == false) {
                    alert(M.util.get_string('pleaseentervaliddate', 'corplms_program', M.util.get_string('datepickerlongyearplaceholder', 'corplms_core')));
                }
                else {
                    thisdialog.update_completiontime(completiontime, completionevent, completioninstance);
                    thisdialog.hide();
                }
            });

            this.handler._container.on('click', '.relativeeventtime', function() {

                var timeunit = $('#timeamount', thisdialog.handler._container).val();
                var timeperiod = $('#timeperiod option:selected', thisdialog.handler._container).val();
                var completiontime = timeunit + " " + timeperiod;

                var completionevent = $('#eventtype option:selected', thisdialog.handler._container).val();
                var completioninstance = $('#instance', thisdialog.handler._container).val();

                var unitformat = /^\d{1,3}$/;
                if (unitformat.test(timeunit) == false) {
                    alert(M.util.get_string('pleaseentervalidunit', 'corplms_program'));
                }
                else if (completioninstance == 0 && completionevent != M.corplms_cohortprogramcompletion.config.COMPLETION_EVENT_FIRST_LOGIN &&
                    completionevent != M.corplms_cohortprogramcompletion.config.COMPLETION_EVENT_ENROLLMENT_DATE) {
                    alert(M.util.get_string('pleasepickaninstance', 'corplms_program'));
                }
                else {
                    thisdialog.update_completiontime(completiontime, completionevent, completioninstance);
                    thisdialog.hide();
                }
            });
        }

        /// Handler definition
        corplmsDialog_completion_handler = function() {};

        corplmsDialog_completion_handler.prototype = new corplmsDialog_handler();

        corplmsDialog_completion_handler.prototype.every_load = function() {
            var handler = this;

            $('.completiontime').datepicker({
                dateFormat: M.util.get_string('datepickerlongyeardisplayformat', 'corplms_core'),
                showOn: 'both',
                buttonImage: M.util.image_url('t/calendar', 'theme'),
                buttonImageOnly: true,
                beforeShow: function() { $('#ui-datepicker-div').css('z-index',1600); },
                constrainInput: true
            });

            var completiontime = this._dialog.completiontime.val();
            var completionevent = this._dialog.completionevent.val();
            var completioninstance = this._dialog.completioninstance.val();

            if (typeof completionevent === 'undefined' ||
                completionevent == M.corplms_cohortprogramcompletion.config.COMPLETION_EVENT_NONE) {
                if (typeof completionevent === 'undefined' ||
                    completiontime != M.corplms_cohortprogramcompletion.config.COMPLETION_TIME_NOT_SET) {
                    $('.completiontime').val(completiontime);
                }
            } else {
                var parts = completiontime.split(" ");
                $('#timeamount').val(parts[0]);
                $('#timeperiod').val(parts[1]);
                $('#eventtype').val(completionevent);
                $('#instance').val(completioninstance);
                $('#instancetitle').text(completioneventname);
            }
        }

        /// the completion event dialog definition
        corplmsDialog_completion_event = function() {

            // Setup the handler
            var handler = new corplmsDialog_handler_treeview_singleselect('instance', 'instancetitle');

            // Store reference to this
            var self = this;

            var dbuttons = {};
            dbuttons[M.util.get_string('cancel','moodle')] = function() { handler._cancel() }
            dbuttons[M.util.get_string('save','corplms_core')] = function() { self.save() }

            // Call the parent dialog object and link us
            corplmsDialog.call(
            this,
            'program-completion-event-dialog',
            'unused2', // buttonid unused
            {
                buttons: dbuttons,
                title: '<h2>' + M.util.get_string('chooseitem', 'corplms_program') + M.corplms_cohortprogramcompletion.config.selected_program + '</h2>'
            },
            'unused2', // default_url unused
            handler
            );

            this.save = function() {
                var selected_val = $('#treeview_selected_val_'+this.handler._title).val();
                var selected_text = $('#treeview_selected_text_'+this.handler._title).text();

                $('#instance').val(selected_val);
                $('#instancetitle').text(selected_text);

                this.hide();
            }

            this.clear = function() {
                $('#instance').val(0);
                $('#instancetitle').text('');
            }

            $(document).on('change', '#eventtype', function() {
                if ($('#eventtype').val() != M.corplms_cohortprogramcompletion.config.COMPLETION_EVENT_FIRST_LOGIN &&
                    $('#eventtype').val() != M.corplms_cohortprogramcompletion.config.COMPLETION_EVENT_ENROLLMENT_DATE) {
                    $('#instance').val(0);
                    $('#instancetitle').text(M.util.get_string('none', 'moodle'));
                }
            });
        }

        // Store the current completion name (contained between the single quotes).
        var completioneventname;

        // Init the completion dialogs.
        corplmsDialogs['completion'] = new corplmsDialog_completion();
        $(document).on('click', '.completionlink', function(event){
            event.preventDefault();
            var td = $(this).parent('td');

            var dialog = corplmsDialogs['completion'];

            var i, completionname = $(this).text(); // Get the completion name currently selected.

            completioneventname = '';
            // Check if the completion name contains single quotes.
            if (completionname.indexOf("'") != -1) {
                // Get the name contained between the single quotes.
                completionname = completionname.substring(completionname.indexOf("'"))
                for (i=1; i<completionname.length-1; i++) {
                    completioneventname = completioneventname + completionname[i];
                }
            }

            dialog.cohortid = M.corplms_cohortprogramcompletion.config.cohortid;
            dialog.programid = $('input[name^="programid"]', td).val();

            dialog.completiontime = $('input[name^="completiontime"]', td);
            dialog.completionevent = $('input[name^="completionevent"]', td);
            dialog.completioninstance = $('input[name^="completioninstance"]', td);
            dialog.completionlink = td;

            dialog.default_url = M.cfg.wwwroot + '/corplms/program/assignment/set_completion.php';
            corplmsDialogs['completion'].open();
        });
        // Add handler to remove completion dates.
        $(document).on('click', '.deletecompletiondatelink', function(event) {
            event.preventDefault();
            var dialog = corplmsDialogs['completion'];

            var td = $(this).parent('td');
            dialog.programid = $('input[name^="programid"]', td).val();
            dialog.cohortid = M.corplms_cohortprogramcompletion.config.cohortid;
            dialog.completiontime = $('input[name^="completiontime"]', td);
            dialog.completionevent = $('input[name^="completionevent"]', td);
            dialog.completioninstance = $('input[name^="completioninstance"]', td);
            dialog.completionlink = td;

            dialog.update_completiontime('', 0, 0);
        });
        corplmsDialogs['completionevent'] = new corplmsDialog_completion_event();

    }  // Init.
}
