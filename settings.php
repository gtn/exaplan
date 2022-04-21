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

$settings->add(new admin_setting_heading('exaplan/heading_main',
    'Haupteinstellungen',
    ''));

// company name
$settings->add(new admin_setting_configtext('exaplan/company_name',
    'Firmenname',
    'Bitte einen Firmennamen eingeben',
    "", PARAM_TEXT));

// Moodle ID
$settings->add(new admin_setting_configtext('exaplan/moodle_id', 'Moodle ID',
    'Bitte eine MoodleID wählen', 0, PARAM_INT));

// apifonica SMS configuration
$settings->add(new admin_setting_heading('exaplan/heading_apifonica',
    'SMS-Verteilung über Apifonica',
    ''));
$settings->add(new admin_setting_configtext('exaplan/apifonica_sms_absender_name', 'absender Name',
    'Name oder Telefon', 'skillswork', PARAM_TEXT));
$settings->add(new admin_setting_configtext('exaplan/apifonica_sms_account_sid', 'Account SID',
    'Unique account identifier (also the username)', '', PARAM_TEXT));
$settings->add(new admin_setting_configtext('exaplan/apifonica_sms_auth_token', 'Account token',
    'Password for authentification', '', PARAM_TEXT));
$settings->add(new admin_setting_configtext('exaplan/apifonica_sms_app_sid', 'Application SID',
    'Unique application identifier', '', PARAM_TEXT));


