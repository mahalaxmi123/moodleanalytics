<?php // $Id$
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
 * @package corplms
 * @subpackage corplms_hierarchy
 */

// This file defines settingpages and externalpages under the "hierarchies" category


    // Positions.
    $ADMIN->add('hierarchies', new admin_category('positions', get_string('positions', 'corplms_hierarchy')));

    $ADMIN->add('positions', new admin_externalpage('positionmanage', get_string('positionmanage', 'corplms_hierarchy'),
            "{$CFG->wwwroot}/corplms/hierarchy/framework/index.php?prefix=position",
            array('corplms/hierarchy:createpositionframeworks', 'corplms/hierarchy:updatepositionframeworks', 'corplms/hierarchy:deletepositionframeworks',
                  'corplms/hierarchy:createposition', 'corplms/hierarchy:updateposition', 'corplms/hierarchy:deleteposition')));

    $ADMIN->add('positions', new admin_externalpage('positiontypemanage', get_string('managepositiontypes', 'corplms_hierarchy'),
            "{$CFG->wwwroot}/corplms/hierarchy/type/index.php?prefix=position",
            array('corplms/hierarchy:createpositiontype', 'corplms/hierarchy:updatepositiontype', 'corplms/hierarchy:deletepositiontype')));

    $ADMIN->add('positions', new admin_externalpage('positionsettings', get_string('settings'),
            "{$CFG->wwwroot}/corplms/hierarchy/prefix/position/settings.php",
            array('moodle/site:config')));


    // Organisations.
    $ADMIN->add('hierarchies', new admin_category('organisations', get_string('organisations', 'corplms_hierarchy')));

    $ADMIN->add('organisations', new admin_externalpage('organisationmanage', get_string('organisationmanage', 'corplms_hierarchy'),
            "{$CFG->wwwroot}/corplms/hierarchy/framework/index.php?prefix=organisation",
            array('corplms/hierarchy:createorganisationframeworks', 'corplms/hierarchy:updateorganisationframeworks', 'corplms/hierarchy:deleteorganisationframeworks',
                  'corplms/hierarchy:createorganisation', 'corplms/hierarchy:updateorganisation', 'corplms/hierarchy:deleteorganisation')));

    $ADMIN->add('organisations', new admin_externalpage('organisationtypemanage', get_string('manageorganisationtypes', 'corplms_hierarchy'),
            "{$CFG->wwwroot}/corplms/hierarchy/type/index.php?prefix=organisation",
            array('corplms/hierarchy:createorganisationtype', 'corplms/hierarchy:updateorganisationtype', 'corplms/hierarchy:deleteorganisationtype')));


    // Competencies.
    $ADMIN->add('hierarchies', new admin_category('competencies', get_string('competencies', 'corplms_hierarchy')));

    $ADMIN->add('competencies', new admin_externalpage('competencymanage', get_string('competencymanage', 'corplms_hierarchy'),
            "{$CFG->wwwroot}/corplms/hierarchy/framework/index.php?prefix=competency",
            array('corplms/hierarchy:createcompetencyframeworks', 'corplms/hierarchy:updatecompetencyframeworks', 'corplms/hierarchy:deletecompetencyframeworks',
                  'corplms/hierarchy:createcompetency', 'corplms/hierarchy:updatecompetency', 'corplms/hierarchy:deletecompetency',
                  'corplms/hierarchy:createcompetencyscale', 'corplms/hierarchy:updatecompetencyscale', 'corplms/hierarchy:deletecompetencyscale')));

    $ADMIN->add('competencies', new admin_externalpage('competencytypemanage', get_string('managecompetencytypes', 'corplms_hierarchy'),
            "{$CFG->wwwroot}/corplms/hierarchy/type/index.php?prefix=competency",
            array('corplms/hierarchy:createcompetencytype', 'corplms/hierarchy:updatecompetencytype', 'corplms/hierarchy:deletecompetencytype')));

//    $ADMIN->add('competencies', new admin_externalpage('competencyglobalsettings', get_string('globalsettings', 'competency'), "$CFG->wwwroot/hierarchy/prefix/competency/adminsettings.php",
//            array('corplms/hierarchy:updatecompetency')));

    // Goals.

    $ADMIN->add('hierarchies', new admin_category('goals', get_string('goals', 'corplms_hierarchy'),
        corplms_feature_disabled('goals')
    ));

    $ADMIN->add('goals', new admin_externalpage('goalmanage', get_string('goalmanage', 'corplms_hierarchy'),
            "{$CFG->wwwroot}/corplms/hierarchy/framework/index.php?prefix=goal",
            array('corplms/hierarchy:creategoalframeworks', 'corplms/hierarchy:updategoalframeworks', 'corplms/hierarchy:deletegoalframeworks',
                  'corplms/hierarchy:creategoal', 'corplms/hierarchy:updategoal', 'corplms/hierarchy:deletegoal',
                  'corplms/hierarchy:creategoalscale', 'corplms/hierarchy:updategoalscale', 'corplms/hierarchy:deletegoalscale'),
            corplms_feature_disabled('goals')));

    $ADMIN->add('goals', new admin_externalpage('goaltypemanage', get_string('managegoaltypes', 'corplms_hierarchy'),
            "{$CFG->wwwroot}/corplms/hierarchy/type/index.php?prefix=goal",
            array('corplms/hierarchy:creategoaltype', 'corplms/hierarchy:updategoaltype', 'corplms/hierarchy:deletegoaltype'),
            corplms_feature_disabled('goals')));

    $ADMIN->add('goals', new admin_externalpage('goalreport', get_string('goalreports', 'corplms_hierarchy'),
            "{$CFG->wwwroot}/corplms/hierarchy/prefix/goal/reports.php",
            array('corplms/hierarchy:viewgoalreport'), corplms_feature_disabled('goals')));
