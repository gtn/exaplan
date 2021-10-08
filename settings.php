<?php
//admin_setting_configpasswordunmask
//if (!class_exists('exaplan_admin_setting_configstandardtext')) {
//    class exaplan_admin_setting_configstandardtext extends admin_setting_configtext {
//        // check needed, because moodle includes this file twice
//
//
//        public function write_setting($data) {
//            // Different parameters can have different data convertion
//            $ret = parent::write_setting($data);
//
//            if ($ret != '') {
//                return $ret;
//            }
//
//            return '';
//        }
//    }
//}

$settings->add(new admin_setting_configtext('exaplan/company_name',
    'Firmenname',
    'Bitte einen Firmennamen eingeben',
    "", PARAM_TEXT));

$settings->add(new admin_setting_configtext('exaplan/moodle_id', 'Moodle ID',
    'Bitte eine MoodleID wählen', 0, PARAM_INT));


?>