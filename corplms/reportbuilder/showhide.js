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
 * @package corplms
 * @subpackage reportbuilder
 */

/**
 * Javascript file containing JQuery bindings for show/hide popup dialog box
 */

M.corplms_reportbuilder_showhide = M.corplms_reportbuilder_showhide || {

    Y: null,

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function(Y, args) {
        // save a reference to the Y instance (all of its dependencies included)
        this.Y = Y;

        // check jQuery dependency is available
        if (typeof $ === 'undefined') {
            throw new Error('M.corplms_reportbuilder_showhide.init()-> jQuery dependency required for this module to function.');
        }

        ///
        /// first, hide columns that should be hidden!
        ///
        if (args.length) {
            for (col in args) {
                $(args[col]).hide();
            }
        }

        ///
        /// show/hide column dialog
        ///

        // id not set when zero results
        // http://verens.com/2005/07/25/isset-for-javascript/#comment-332
        if (window.id === undefined) {return;}

        $('#show-showhide-dialog').css('display','inline');
        var path = M.cfg.wwwroot + '/corplms/reportbuilder/';

        var handler = new corplmsDialog_handler();
        var name = 'showhide';
        var buttons = {};
        buttons[M.util.get_string('ok', 'moodle')] = function() { handler._cancel() };

        var querystring = window.location.search;

        corplmsDialogs[name] = new corplmsDialog(
            name,
            'show-'+name+'-dialog',
            {
                buttons: buttons,
                title: '<h2>' + M.util.get_string('showhidecolumns', 'corplms_reportbuilder') + '</h2>'
            },
            path + 'showhide.php?id=' + id.toString() + querystring.replace('?', '&'),
            handler
        );
    }
}
