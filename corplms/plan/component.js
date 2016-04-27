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
 * @author Simon Coggins <simon.coggins@corplmslms.com>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Aaron Barnes <aaron.barnes@corplmslms.com>
 * @package corplms
 * @subpackage corplms_core
 */
M.corplms_plan_component = M.corplms_plan_component || {

    Y: null,
    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {},
    // public handler reference for the dialog
    corplmsDialog_handler_preRequisite: null,

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function(Y, args){
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
            throw new Error('M.corplms_positionuser.init()-> jQuery dependency required for this module to function.');
        }

        // Add hooks to learning plan component form elements
        // Update when form elements change
        $(document).on('change', 'table.dp-plan-component-items input, table.dp-plan-component-items select', function() {
            var data = {
                submitbutton: "1",
                ajax: "1",
                sesskey: M.corplms_plan_component.config.sesskey,
                page: M.corplms_plan_component.config.page
            };

            // Get current value
            data[$(this).attr('name')] = $(this).val();

            $.post(
                M.cfg.wwwroot + '/corplms/plan/component.php?id='+M.corplms_plan_component.config.plan_id+'&c='+M.corplms_plan_component.config.component_name+'&page='+M.corplms_plan_component.config.page,
                data,
                M.corplms_plan_component.corplms_corplms_plan_update
            );
        });

        // create the dialog
        this.corplmsDialog_handler_preRequisite = function() {
            // Base url
            var baseurl = '';
        }

        this.corplmsDialog_handler_preRequisite.prototype = new corplmsDialog_handler_treeview_multiselect();

        /**
         * Add a row to a table on the calling page
         * Also hides the dialog and any no item notice
         *
         * @param string    HTML response
         * @return void
         */
        this.corplmsDialog_handler_preRequisite.prototype._update = function(response) {
            // Hide dialog
            this._dialog.hide();
            // Update table
            M.corplms_plan_component.corplms_corplms_plan_update(response);
        };
    },

    /**
     * Update the table on the calling page, and remove/add no items notices
     *
     * @param   string  HTML response
     * @return  void
     */
    corplms_corplms_plan_update: function(response) {

        // Remove no item warning (if exists)
        $('.noitems-assign'+M.corplms_plan_component.config.component_name).remove();

        // Split response into table and div
        var new_table = $(response).find('table.dp-plan-component-items');
        var new_planbox = $(response).filter('.plan_box');

        // Grab table
        var table = $('form#dp-component-update table.dp-plan-component-items');

        // Check for no items msg
        var noitems = $(response).filter('span.noitems-assign'+M.corplms_plan_component.config.component_name);

        // Define update setting button div
        var updatesettings = $('div#dp-component-update-submit');

        if (noitems.size()) {
            // If no items, just display message
            $('form#dp-component-update div#dp-component-update-table').append(noitems);
            // Replace table with nothing
            table.empty();
            // Hide update setting button when there are no items
            updatesettings.hide();
        } else if (table.size()) {
            // If table found
            table.replaceWith(new_table);
            updatesettings.show();
        } else {
            // Add new table
            $('form#dp-component-update div#dp-component-update-table').append(new_table);
            // Show update setting button there are now rows
            updatesettings.show();
        }

        // Replace plan message box
        $('div.plan_box').replaceWith(new_planbox);

        // Add duedate datepicker
        var format = M.util.get_string('datepickerlongyeardisplayformat', 'corplms_core');
        M.corplms_core.build_datepicker(this.Y, "[id^=duedate_"+M.corplms_plan_component.config.component_name+"]", format);

    }
};
