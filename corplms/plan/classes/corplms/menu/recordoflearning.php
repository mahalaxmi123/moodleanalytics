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

namespace corplms_plan\corplms\menu;

use \corplms_core\corplms\menu\menu as menu;

class recordoflearning extends \corplms_core\corplms\menu\item {

    protected function get_default_title() {
        return get_string('recordoflearning', 'corplms_plan');
    }

    protected function get_default_url() {
        return '/corplms/plan/record/index.php';
    }

    public function get_default_sortorder() {
        return 23000;
    }

    public function get_default_visibility() {
        return menu::SHOW_WHEN_REQUIRED;
    }

    protected function get_default_parent() {
        return '\corplms_core\corplms\menu\mylearning';
    }
}
