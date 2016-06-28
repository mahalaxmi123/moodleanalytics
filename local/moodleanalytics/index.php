<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require('../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('lib.php');
require_login();

$charttype = optional_param('type', 0, PARAM_ALPHANUM);
$PAGE->set_url(new moodle_url("/local/moodleanalytics/index.php", array()));
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('moodleanalytics', 'local_moodleanalytics'));
$PAGE->set_heading(get_string('moodleanalytics', 'local_moodleanalytics'));
echo $OUTPUT->header();
$params = (object) array(
            'userid' => 0,
            'courseid' => 0,
            'timestart' => 0,
            'timefinish' => time()
);
//$plugin = new $class();
//$plugin->teacher_roles = '3,4';
//$plugin->learner_roles = '5';
$c = new curl;
$intelliboard = json_decode($c->post('#', $params));
$countries = get_dashboard_countries();

$json_countries = array();
foreach ($countries as $country) {
    $json_countries[] = "['" . ucfirst($country->country) . "', $country->users]";
}
$data = //  16 => json_encode($plugin->get_system_courses($params)),
        get_enrollments_per_course($params);
// 18 => json_encode($plugin->get_new_courses_per_day($params)),
// 8 => json_encode($plugin->get_most_visited_courses($params)),
//7 => json_encode($plugin->get_most_visited_courses($params)),
//5 => json_encode($plugin->get_unique_sessions($params)),
//9 => json_encode($plugin->get_no_visited_courses($params))


$params = array(
    'url' => $CFG->wwwroot,
    'email' => $USER->email,
    'firstname' => $USER->firstname,
    'lastname' => $USER->lastname,
    'reports' => get_config('local_moodleanalytics', 'reports'),
    'data' => json_encode($data),
    'type' => 'courses',
    'do' => 'widgets',
    'userid' => 0,
    'courseid' => 0,
    'timestart' => 0,
    'timefinish' => time()
);
$json_enrols = array();
foreach ($data as $enrollment) {
    $json_enrols[] = '[' . '"' . $enrollment->fullname . '"' . ',' . $enrollment->nums . ']';
}
$chartoptions = array('BarChart', 'GeoChart', 'ColumnChart', 'Histogram', 'PieChart');
$formcontent = html_writer::start_tag('div');
$formcontent .= html_writer::start_tag('form', array('action' => new moodle_url($CFG->wwwroot . '/local/moodleanalytics/index.php'), 'method' => 'post'));
$formcontent .= html_writer::select($chartoptions, 'type', $charttype, '', array('OnChange' => 'document.forms[0].submit();return false;'));
$formcontent .= html_writer::end_tag('form');
$formcontent .= html_writer::end_tag('div');
echo $formcontent;
?>
<script type="text/javascript"
        src="https://www.google.com/jsapi?autoload={
        'modules':[{
        'name':'visualization',
        'version':'1',
        'packages':['corechart','geochart']
        }]
}"></script>
<div>
    <div class="box45 pull-left">
        <h3>Registrations</h3>
        <div id="countries" style="width:500px; height:500px;"></div>
    </div>
    <div class="box45 pull-right">
        <h3>Enrollment per-course</h3>
        <div id="enrollmentpercourse" style="width: 400px; height:400px;"></div>
    </div>
</div>
<script type="text/javascript">
            google.setOnLoadCallback(drawRegionsMap);
            function drawRegionsMap() {
            var data = google.visualization.arrayToDataTable([['Country', 'Users'], <?php echo ($json_countries) ? implode(",", $json_countries) : ""; ?>]);
                    var chart = new google.visualization.<?php echo $chartoptions[$charttype]; ?>(document.getElementById('countries'));
                    chart.draw(data, {});
            }
</script>
<script type="text/javascript">
    google.setOnLoadCallback(drawEnrolments);
            function drawEnrolments() {
            var data = google.visualization.arrayToDataTable([
                    ['fullname', 'nums'],<?php echo ($json_enrols) ? implode(",", $json_enrols) : ""; ?> ]);
                    var options = {
                    backgroundColor:{fill:"transparent"},
                            title: '',
                            pieHole: 0.4,
                            chartArea: {
                            width: '100%'
                            }
                    };
                    var chart = new google.visualization.<?php echo $chartoptions[$charttype]; ?>(document.getElementById('enrollmentpercourse'));
                    chart.draw(data, options);
            }

</script>

<?php
echo $OUTPUT->footer();
