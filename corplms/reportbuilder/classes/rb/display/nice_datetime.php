<?php
/*
 * This file is part of Corplms LMS
 *
 * Copyright (C) 2014 onwards Corplms Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@corplmslms.com>
 * @package corplms_reportbuilder
 */

namespace corplms_reportbuilder\rb\display;

/**
 * Class describing column display formatting.
 *
 * @author Petr Skoda <petr.skoda@corplmslms.com>
 * @package corplms_reportbuilder
 */
class nice_datetime extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        if (!is_numeric($value) or $value == 0) {
            return '';
        }

        if ($format === 'excel') {
            $dateformat = new \MoodleExcelFormat();
            $dateformat->set_num_format(22);
            return array('date', $value, $dateformat);
        }

        if ($format === 'ods') {
            $dateformat = new \MoodleOdsFormat();
            $dateformat->set_num_format(22);
            return array('date', $value, $dateformat);
        }

        return userdate($value, get_string('strfdateattime', 'langconfig'));
    }
}
