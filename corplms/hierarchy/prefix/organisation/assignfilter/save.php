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
 * @author Simon Coggins <simon.coggins@corplmslms.com>
 * @package corplms
 * @subpackage hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
require_once($CFG->dirroot.'/corplms/core/utils.php');
require_once($CFG->dirroot.'/corplms/reportbuilder/filters/lib.php');
require_once($CFG->dirroot.'/corplms/reportbuilder/filters/hierarchy_multi.php');

$ids = required_param('ids', PARAM_SEQUENCE);
$ids = explode(',', $ids);
$filtername = required_param('filtername', PARAM_TEXT);

require_login();
$PAGE->set_context(context_system::instance());

echo $OUTPUT->container_start('list-' . $filtername);
list($ids_sql, $ids_params) = $DB->get_in_or_equal($ids);
if (!empty($ids) && $items = $DB->get_records_select('org', "id {$ids_sql}", $ids_params)) {
    foreach ($items as $item) {
        echo display_selected_hierarchy_item($item, $filtername);
    }
}
echo $OUTPUT->container_end();
