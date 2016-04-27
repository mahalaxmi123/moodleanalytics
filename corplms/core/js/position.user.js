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
 * @author Dave Wallace <dave.wallace@kineo.co.nz>
 * @package corplms
 * @subpackage corplms_core
 */
M.corplms_positionuser = M.corplms_positionuser || {

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

        ///
        /// Position dialog
        ///
        (function() {
            var url = M.cfg.wwwroot+'/corplms/hierarchy/prefix/position/assign/';
            corplmsSingleSelectDialog(
                'position',
                M.util.get_string('chooseposition', 'corplms_hierarchy') + M.corplms_positionuser.config.dialog_display_position,
                url+'position.php?',
                'positionid',
                'positiontitle',
                undefined,
                M.corplms_positionuser.config.can_edit           //Make selection deletable
            );
        })();

        ///
        /// Organisation dialog
        ///
        (function() {
            var url = M.cfg.wwwroot+'/corplms/hierarchy/prefix/organisation/assign/';
            corplmsSingleSelectDialog(
                'organisation',
                M.util.get_string('chooseorganisation', 'corplms_hierarchy') + M.corplms_positionuser.config.dialog_display_organisation,
                url+'find.php?',
                'organisationid',
                'organisationtitle',
                undefined,
                M.corplms_positionuser.config.can_edit            // Make selection deletable
            );
        })();

        ///
        /// Manager dialog
        ///
        (function() {
            var url = M.cfg.wwwroot+'/corplms/hierarchy/prefix/position/assign/';

            corplmsSingleSelectDialog(
                'manager',
                M.util.get_string('choosemanager', 'corplms_hierarchy') + M.corplms_positionuser.config.dialog_display_manager,
                url+'manager.php?userid='+M.corplms_positionuser.config.userid,
                'managerid',
                'managertitle',
                undefined,
                M.corplms_positionuser.config.can_edit            // Make selection deletable
            );
        })();

        ///
        /// Temporary manager dialog
        ///
        (function() {
            var url = M.cfg.wwwroot+'/corplms/hierarchy/prefix/position/assign/';

            corplmsSingleSelectDialog(
                'tempmanager',
                M.util.get_string('choosetempmanager', 'corplms_core') + M.corplms_positionuser.config.dialog_display_tempmanager,
                url+'tempmanager.php?userid='+M.corplms_positionuser.config.userid,
                'tempmanagerid',
                'tempmanagertitle',
                undefined,
                M.corplms_positionuser.config.can_edit_tempmanager            // Make selection deletable
            );
        })();

        ///
        /// Appraiser dialog
        ///
        (function() {
            var url = M.cfg.wwwroot+'/corplms/hierarchy/prefix/position/assign/';

            corplmsSingleSelectDialog(
                'appraiser',
                M.util.get_string('chooseappraiser', 'corplms_hierarchy') + M.corplms_positionuser.config.dialog_display_appraiser,
                url+'manager.php?userid='+M.corplms_positionuser.config.userid,
                'appraiserid',
                'appraisertitle',
                undefined,
                M.corplms_positionuser.config.can_edit            // Make selection deletable
            );
        })();

    }
};
