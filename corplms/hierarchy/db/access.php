<?php
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
 * @author Ciaran Irvine <ciaran@catalyst.net.nz>
 * @package corplms
 * @subpackage hierarchy
 */

/*
 * The capabilities are loaded into the database table when the module is
 * installed or updated. Whenever the capability definitions are updated,
 * the module version number should be bumped up.
 *
 * The system has four possible values for a capability:
 * CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
 */

$capabilities = array(

        // Viewing and managing a competency.
        'corplms/hierarchy:viewcompetency' => array(
            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'user' => CAP_ALLOW
                ),
            ),
        'corplms/hierarchy:createcompetency' => array(
            'captype'       => 'write',
            'contextlevel'  => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
                ),
            ),
        'corplms/hierarchy:updatecompetency' => array(
            'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
            'captype'       => 'write',
            'contextlevel'  => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
                ),
            ),
        'corplms/hierarchy:deletecompetency' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:createcompetencytype' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:updatecompetencytype' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:deletecompetencytype' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:createcompetencyframeworks' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:updatecompetencyframeworks' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:deletecompetencyframeworks' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:createcompetencytemplate' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:updatecompetencytemplate' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:deletecompetencytemplate' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:createcompetencycustomfield' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:updatecompetencycustomfield' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:deletecompetencycustomfield' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:viewcompetencyscale' => array(
                'captype'       => 'read',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                'clonepermissionsfrom' => 'corplms/hierarchy:viewcompetencyframeworks'
                ),
        'corplms/hierarchy:createcompetencyscale' => array(
                'riskbitmask'   => RISK_SPAM,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                'clonepermissionsfrom' => 'corplms/hierarchy:createcompetencyframeworks'
                ),
        'corplms/hierarchy:updatecompetencyscale' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                'clonepermissionsfrom' => 'corplms/hierarchy:updatecompetencyframeworks'
                ),
        'corplms/hierarchy:deletecompetencyscale' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                'clonepermissionsfrom' => 'corplms/hierarchy:deletecompetencyframeworks'
                ),

        // Viewing and managing positions.
        'corplms/hierarchy:viewposition' => array(
                'riskbitmask' => RISK_PERSONAL,
                'captype'      => 'read',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW,
                    'student' => CAP_ALLOW,
                    'user' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:createposition' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:updateposition' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:deleteposition' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:createpositiontype' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:updatepositiontype' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:deletepositiontype' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:createpositionframeworks' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:updatepositionframeworks' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:deletepositionframeworks' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:createpositioncustomfield' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:updatepositioncustomfield' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:deletepositioncustomfield' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),

        // Viewing and managing organisations.
        'corplms/hierarchy:vieworganisation' => array(
                'riskbitmask' => RISK_PERSONAL,
                'captype'      => 'read',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW,
                    'student' => CAP_ALLOW,
                    'user' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:createorganisation' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:updateorganisation' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:deleteorganisation' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:createorganisationtype' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:updateorganisationtype' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:deleteorganisationtype' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:createorganisationframeworks' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:updateorganisationframeworks' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:deleteorganisationframeworks' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:createorganisationcustomfield' => array(
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:updateorganisationcustomfield' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:deleteorganisationcustomfield' => array(
                'riskbitmask'   => RISK_PERSONAL | RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),

        // Assign a position to yourself.
        'corplms/hierarchy:assignselfposition' => array(
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            ),

        // Assign a position to a user.
        'corplms/hierarchy:assignuserposition' => array(
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
                ),
            ),

        // Goals permissions - Management.
        'corplms/hierarchy:viewgoal' => array(
            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW,
                'student' => CAP_ALLOW,
                'user' => CAP_ALLOW
                ),
            'clonepermissionsfrom' => 'corplms/hierarchy:viewcompetency'
            ),
        'corplms/hierarchy:creategoal' => array(
            'riskbitmask' => RISK_SPAM,
            'captype'       => 'write',
            'contextlevel'  => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
                ),
            'clonepermissionsfrom' => 'corplms/hierarchy:createcompetency'
            ),
        'corplms/hierarchy:updategoal' => array(
            'riskbitmask'   => RISK_DATALOSS,
            'captype'       => 'write',
            'contextlevel'  => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
                ),
            'clonepermissionsfrom' => 'corplms/hierarchy:updatecompetency'
            ),
        'corplms/hierarchy:deletegoal' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'corplms/hierarchy:deletecompetency'
                ),
        'corplms/hierarchy:creategoaltype' => array(
            'riskbitmask' => RISK_SPAM,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'corplms/hierarchy:createcompetencytype'
                ),
        'corplms/hierarchy:updategoaltype' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'corplms/hierarchy:updatecompetencytype'
                ),
        'corplms/hierarchy:deletegoaltype' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'corplms/hierarchy:deletecompetencytype'
                ),
        'corplms/hierarchy:creategoalframeworks' => array(
            'riskbitmask' => RISK_SPAM,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'corplms/hierarchy:createcompetencyframeworks'
                ),
        'corplms/hierarchy:updategoalframeworks' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'corplms/hierarchy:updatecompetencyframeworks'
                ),
        'corplms/hierarchy:deletegoalframeworks' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'wrireadte',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'corplms/hierarchy:deletecompetencyframeworks'
                ),
        'corplms/hierarchy:creategoalcustomfield' => array(
            'riskbitmask' => RISK_SPAM,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'corplms/hierarchy:createcompetencycustomfield'
                ),
        'corplms/hierarchy:updategoalcustomfield' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'corplms/hierarchy:updatecompetencycustomfield'
                ),
        'corplms/hierarchy:deletegoalcustomfield' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
            'clonepermissionsfrom' => 'corplms/hierarchy:deletecompetencycustomfield'
                ),
        'corplms/hierarchy:viewgoalscale' => array(
                'captype'       => 'read',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:creategoalscale' => array(
                'riskbitmask'   => RISK_SPAM,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:updategoalscale' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:deletegoalscale' => array(
                'riskbitmask'   => RISK_DATALOSS,
                'captype'       => 'write',
                'contextlevel'  => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                    ),
                ),
        'corplms/hierarchy:viewgoalreport' => array(
                'riskbitmask' => RISK_PERSONAL,
                'captype' => 'read',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => array('manager' => CAP_ALLOW),
                'clonepermissionsfrom' => 'corplms/hierarchy:viewgoal'
        ),
        'corplms/hierarchy:editgoalreport' => array(
                'riskbitmask' => RISK_PERSONAL | RISK_CONFIG,
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => array('manager' => CAP_ALLOW),
                'clonepermissionsfrom' => 'corplms/hierarchy:updategoal'
        ),

        // User goals self management permissions.
        'corplms/hierarchy:viewownpersonalgoal' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_USER,
            'archetypes' => array(
                'user' => CAP_ALLOW
                )
            ),
        'corplms/hierarchy:viewowncompanygoal' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_USER,
            'archetypes' => array(
                'user' => CAP_ALLOW
                )
            ),
        'corplms/hierarchy:manageownpersonalgoal' => array(
            'captype' => 'write',
            'contextlevel' => CONTEXT_USER,
            'archetypes' => array(
                'user' => CAP_ALLOW
                )
            ),
        'corplms/hierarchy:manageowncompanygoal' => array(
            'captype' => 'write',
            'contextlevel' => CONTEXT_USER,
            'archetypes' => array(
                'user' => CAP_ALLOW
                )
            ),

        // Manager team goal management permissions.
        'corplms/hierarchy:viewstaffpersonalgoal' => array(
            'riskbitmask'   => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_USER,
                'archetypes' => array(
                    'staffmanager' => CAP_ALLOW
                    ),
            ),
        'corplms/hierarchy:viewstaffcompanygoal' => array(
            'riskbitmask'   => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_USER,
                'archetypes' => array(
                    'staffmanager' => CAP_ALLOW
                    ),
            ),
        'corplms/hierarchy:managestaffpersonalgoal' => array(
            'riskbitmask'   => RISK_PERSONAL | RISK_SPAM | RISK_DATALOSS,
            'captype' => 'write',
            'contextlevel' => CONTEXT_USER,
                'archetypes' => array(
                    'staffmanager' => CAP_ALLOW
                    ),
            ),
        'corplms/hierarchy:managestaffcompanygoal' => array(
            'riskbitmask'   => RISK_SPAM | RISK_DATALOSS,
            'captype' => 'write',
            'contextlevel' => CONTEXT_USER,
                'archetypes' => array(
                    'staffmanager' => CAP_ALLOW
                    ),
            ),

        // Admin site goal management permissions.
        'corplms/hierarchy:managegoalassignments' => array(
            'riskbitmask'   => RISK_SPAM,
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
                ),
            ),

        // Additional view framework permissions.
        'corplms/hierarchy:viewcompetencyframeworks' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'corplms/hierarchy:updatecompetencyframeworks'
        ),
        'corplms/hierarchy:viewpositionframeworks' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'corplms/hierarchy:updatepositionframeworks'
        ),
        'corplms/hierarchy:vieworganisationframeworks' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'corplms/hierarchy:updateorganisationframeworks'
        ),

        'corplms/hierarchy:viewgoalframeworks' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => array(
                'manager' => CAP_ALLOW,
                'staffmanager' => CAP_ALLOW,
                'user' => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'corplms/hierarchy:updategoalframeworks'
        ),
    );
