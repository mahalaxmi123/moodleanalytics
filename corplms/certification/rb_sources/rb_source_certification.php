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
 * @author Valerii Kuznetsov <valerii.kuznetsov@corplmslms.com>
 * @package corplms
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/corplms/program/rb_sources/rb_source_program.php');
require_once($CFG->dirroot . '/corplms/cohort/lib.php');

class rb_source_certification extends rb_source_program {

    /**
     * Overwrite instance type value of corplms_visibility_where() in rb_source_program->post_config().
     */
    protected $instancetype = 'certification';

    public function __construct() {
        parent::__construct();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_certification');
        $this->sourcewhere = $this->define_sourcewhere();
    }
    protected function define_columnoptions() {

        // Include some standard columns.
        $this->add_program_fields_to_columns($columnoptions, 'base', 'corplms_certification');
        $this->add_course_category_fields_to_columns($columnoptions, 'course_category', 'base', 'certifcount');
        $this->add_cohort_program_fields_to_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_sourcewhere() {
        $sourcewhere = '(base.certifid IS NOT NULL)';

        return $sourcewhere;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        // Include some standard filters.
        $this->add_program_fields_to_filters($filteroptions, 'corplms_certification');
        $this->add_course_category_fields_to_filters($filteroptions, 'base', 'category');
        $this->add_cohort_program_fields_to_filters($filteroptions);

        return $filteroptions;
    }
}
