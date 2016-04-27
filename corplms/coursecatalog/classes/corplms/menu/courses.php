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
 * Corplms navigation edit page.
 *
 * @package    corplms
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@corplmslms.com>
 */

namespace corplms_coursecatalog\corplms\menu;

use \corplms_core\corplms\menu\menu as menu;

class courses extends \corplms_core\corplms\menu\item {

    protected function get_default_title() {
        return get_string('courses', 'corplms_coursecatalog');
    }

    protected function get_default_url() {
        global $CFG;

        if (!empty($CFG->enhancedcatalog)) {
            return '/corplms/coursecatalog/courses.php';
        } else {
            return '/course/index.php';
        }
    }

    public function get_default_sortorder() {
        return 61000;
    }

    public function get_default_visibility() {
        return menu::SHOW_WHEN_REQUIRED;
    }

    protected function get_default_parent() {
        return '\corplms_coursecatalog\corplms\menu\findlearning';
    }
}
