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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Aaron Barnes <aaron.barnes@corplmslms.com>
 * @author Alastair Munro <alastair.munro@corplmslms.com>
 * @package corplms
 * @subpackage corplms_core
 */
M.corplms_plan_course_find = M.corplms_plan_course_find || {

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
            throw new Error('M.corplms_plan_course_find.init()-> jQuery dependency required for this module to function.');
        }

        var url = M.cfg.wwwroot + '/corplms/plan/components/course/';
        var saveurl = url + 'update.php?id='+this.config.plan_id+'&update=';

        var handler = new M.corplms_plan_component.corplmsDialog_handler_preRequisite();
        handler.baseurl = url;
        var buttonsObj = {};
        buttonsObj[M.util.get_string('cancel','moodle')] = function() { handler._cancel() }
        buttonsObj[M.util.get_string('save','corplms_core')] = function() { handler._save(saveurl) }

        corplmsDialogs['evidence'] = new corplmsDialog(
            'assigncourses',
            'show-course-dialog',
            {
                buttons: buttonsObj,
                title: '<h2>' + M.util.get_string('addcourses', 'corplms_plan') + '</h2>'
            },
            url+'find.php?id='+this.config.plan_id,
            handler
        );
    }
};
