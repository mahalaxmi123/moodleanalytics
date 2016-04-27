<?php

if ($hassiteconfig) { // speedup for non-admins, add all caps used on this page

    $optionalsubsystems->add(new admin_setting_configcheckbox('enableoutcomes', new lang_string('enableoutcomes', 'grades'), new lang_string('enableoutcomes_help', 'grades'), 0));
    $optionalsubsystems->add(new admin_setting_configcheckbox('usecomments', new lang_string('enablecomments', 'admin'), new lang_string('configenablecomments', 'admin'), 1));

    $optionalsubsystems->add(new admin_setting_configcheckbox('usetags', new lang_string('usetags','admin'),new lang_string('configusetags', 'admin'), '1'));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablenotes', new lang_string('enablenotes', 'notes'), new lang_string('configenablenotes', 'notes'), 1));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enableportfolios', new lang_string('enabled', 'portfolio'), new lang_string('enableddesc', 'portfolio'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablewebservices', new lang_string('enablewebservices', 'admin'), new lang_string('configenablewebservices', 'admin'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('messaging', new lang_string('messaging', 'admin'), new lang_string('configmessaging','admin'), 1));

    $optionalsubsystems->add(new admin_setting_configcheckbox('messaginghidereadnotifications', new lang_string('messaginghidereadnotifications', 'admin'), new lang_string('configmessaginghidereadnotifications','admin'), 0));

    $options = array(DAYSECS=>new lang_string('secondstotime86400'), WEEKSECS=>new lang_string('secondstotime604800'), 2620800=>new lang_string('nummonths', 'moodle', 1), 15724800=>new lang_string('nummonths', 'moodle', 6),0=>new lang_string('never'));
    $optionalsubsystems->add(new admin_setting_configselect('messagingdeletereadnotificationsdelay', new lang_string('messagingdeletereadnotificationsdelay', 'admin'), new lang_string('configmessagingdeletereadnotificationsdelay', 'admin'), 604800, $options));

    $optionalsubsystems->add(new admin_setting_configcheckbox('messagingallowemailoverride', new lang_string('messagingallowemailoverride', 'admin'), new lang_string('configmessagingallowemailoverride','admin'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablestats', new lang_string('enablestats', 'admin'), new lang_string('configenablestats', 'admin'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablerssfeeds', new lang_string('enablerssfeeds', 'admin'), new lang_string('configenablerssfeeds', 'admin'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enableblogs', new lang_string('enableblogs', 'admin'), new lang_string('configenableblogs', 'admin'), 1));

    $options = array('off'=>new lang_string('off', 'mnet'), 'strict'=>new lang_string('on', 'mnet'));
    $optionalsubsystems->add(new admin_setting_configselect('mnet_dispatcher_mode', new lang_string('net', 'mnet'), new lang_string('configmnet', 'mnet'), 'off', $options));

    // Conditional activities: completion and availability
    $optionalsubsystems->add(new admin_setting_configcheckbox('enablecompletion',
        new lang_string('enablecompletion','completion'),
        new lang_string('configenablecompletion','completion'), 1));

    $options = array(
        1 => get_string('completionactivitydefault', 'completion'),
        0 => get_string('completion_none', 'completion')
    );
    $optionalsubsystems->add(new admin_setting_configselect('completiondefault', new lang_string('completiondefault', 'completion'),
            new lang_string('configcompletiondefault', 'completion'), 1, $options));

    $optionalsubsystems->add($checkbox = new admin_setting_configcheckbox('enableavailability',
            new lang_string('enableavailability', 'availability'),
            new lang_string('enableavailability_desc', 'availability'), 0));
    $checkbox->set_affects_modinfo(true);

    // Course RPL
    $optionalsubsystems->add(new admin_setting_configcheckbox('enablecourserpl', new lang_string('enablecourserpl', 'completion'), new lang_string('configenablecourserpl', 'completion'), 1));

    // Module RPLs
    // Get module list
    if ($modules = $DB->get_records("modules")) {
        // Default to all
        $defaultmodules = array();

        foreach ($modules as $module) {
            $strmodulename = new lang_string("modulename", "$module->name");
            // Deal with modules which are lacking the language string
            if ($strmodulename == '[[modulename]]') {
                $strmodulename = $module->name;
            }
            $modulebyname[$module->id] = $strmodulename;
            $defaultmodules[$module->id] = 1;
        }
        asort($modulebyname, SORT_LOCALE_STRING);

        $optionalsubsystems->add(new admin_setting_configmulticheckbox(
                        'enablemodulerpl',
                        new lang_string('enablemodulerpl', 'completion'),
                        new lang_string('configenablemodulerpl', 'completion'),
                        $defaultmodules,
                        $modulebyname
        ));
    }

    $optionalsubsystems->add(new admin_setting_configcheckbox('enableplagiarism', new lang_string('enableplagiarism','plagiarism'), new lang_string('configenableplagiarism','plagiarism'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablebadges', new lang_string('enablebadges', 'badges'), new lang_string('configenablebadges', 'badges'), 1));

    // Report caching
    $optionalsubsystems->add(new admin_setting_configcheckbox('enablereportcaching', new lang_string('enablereportcaching','corplms_reportbuilder'), new lang_string('configenablereportcaching','corplms_reportbuilder'), 0));

    // Audience visibility.
    $optionalsubsystems->add(new admin_setting_configcheckbox('audiencevisibility', new lang_string('enableaudiencevisibility', 'corplms_cohort'), new lang_string('configenableaudiencevisibility', 'corplms_cohort'), 0));

    // Enchanced catalog.
    // Was upgrade - old catalog by default, otherwise - new catalog.
    $defaultenhanced = (isset($CFG->upgradetofaceted) && $CFG->upgradetofaceted == 1) ? 0 : 1;
    $optionalsubsystems->add(new admin_setting_configcheckbox('enhancedcatalog',
            new lang_string('enhancedcatalog', 'corplms_core'),
            new lang_string('configenhancedcatalog', 'corplms_core'), $defaultenhanced));
    // Corplms Settings.
    $featureoptions = array(
        CORPLMS_SHOWFEATURE => new lang_string('showfeature', 'corplms_core'),
        CORPLMS_HIDEFEATURE => new lang_string('hidefeature', 'corplms_core'),
        CORPLMS_DISABLEFEATURE => new lang_string('disablefeature', 'corplms_core')
    );

    // If adding or removing the settings below, be sure to update the array in
    // corplms_advanced_features_list() in corplms/core/corplms.php.

    $optionalsubsystems->add(new admin_setting_configselect('enablegoals',
        new lang_string('enablegoals', 'corplms_hierarchy'),
        new lang_string('configenablegoals', 'corplms_hierarchy'),
        CORPLMS_SHOWFEATURE, $featureoptions));

    $optionalsubsystems->add(new admin_setting_configselect('enableappraisals',
        new lang_string('enableappraisals', 'corplms_appraisal'),
        new lang_string('configenableappraisals', 'corplms_appraisal'),
        CORPLMS_SHOWFEATURE, $featureoptions));

    $optionalsubsystems->add(new admin_setting_configselect('enablefeedback360',
        new lang_string('enablefeedback360', 'corplms_feedback360'),
        new lang_string('configenablefeedback360', 'corplms_feedback360'),
        CORPLMS_SHOWFEATURE, $featureoptions));

    $optionalsubsystems->add(new admin_setting_configselect('enablelearningplans',
        new lang_string('enablelearningplans', 'corplms_plan'),
        new lang_string('configenablelearningplans', 'corplms_plan'),
        CORPLMS_SHOWFEATURE, $featureoptions));

    $optionalsubsystems->add(new admin_setting_configselect('enableprograms',
        new lang_string('enableprograms', 'corplms_program'),
        new lang_string('configenableprograms', 'corplms_program'),
        CORPLMS_SHOWFEATURE, $featureoptions));

    $optionalsubsystems->add(new admin_setting_configselect('enablecertifications',
        new lang_string('enablecertifications', 'corplms_program'),
        new lang_string('configenablecertifications', 'corplms_program'),
        CORPLMS_SHOWFEATURE, $featureoptions));
}
