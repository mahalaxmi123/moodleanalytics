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

namespace corplms_reportbuilder\rb\aggregate;

/**
 * Class describing column aggregation options.
 */
class minimum extends base {
    protected static function get_field_aggregate($field) {
        return "MIN($field)";
    }

    public static function get_displayfunc(\rb_column $column) {
        return $column->displayfunc;
    }

    public static function is_column_option_compatible(\rb_column_option $option) {
        return ($option->dbdatatype === 'integer' or $option->dbdatatype === 'decimal' or $option->dbdatatype === 'timestamp');
    }
}
