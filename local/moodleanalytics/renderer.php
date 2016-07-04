<?php

class local_charts_renderer extends plugin_renderer_base {

    /**
     * Renders a course enrolment table
     *
     * @param course_enrolment_table $table
     * @param moodleform $mform Form that contains filter controls
     * @return string
     */
    public function render_date_filter() {
        global $CFG;
        $starttime = optional_param('startdate', '', PARAM_TEXT);
        $endtime = optional_param('enddate', '',PARAM_TEXT);
        $pageparams = array();
        $content = '';
        $content .= html_writer::start_tag('form', array('action' => new moodle_url($CFG->wwwroot . '/local/charts/userenrol_pie.php?startdate='.$starttime.'$enddate='.$endtime), 'method' => 'post'));
        $content .= html_writer::start_tag('div', array('class' => 'date'));
        $content .= get_string('startdate','local_charts');
        $content .= html_writer::empty_tag('input', array('type' => 'date', 'name' => 'startdate', 'value' => $starttime));
        $content .= get_string('enddate','local_charts');
        $content .= html_writer::empty_tag('input', array('type' => 'date', 'name' => 'enddate', 'value' => $endtime));
        $content .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('filter')));
        $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('form');
        return $content;
    }

}
