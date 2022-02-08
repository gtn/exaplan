<?php

defined('MOODLE_INTERNAL') || die();
require_once __DIR__.'/../inc.php';

/**
 * Event observer for block_exacomp.
 */
class block_exaplan_observer {
    
    /**
     * Observer for \core\event\course_created event.
     *
     * @param \core\event\course_created $event
     * @return void
     */
    public static function enrolment_canceled(\core\event\user_enrolment_deleted $event) {
        global $DB;
        
        $pdo = getPdoConnect();
    		$params = array(
        ':moodleid' => 99,
        ':companyname' => serialize($event);
    		);
    		$statement = $pdo->prepare("
        INSERT INTO mdl_block_exaplanmoodles
        (companyname, moodleid) VALUES (:companyname,:moodleid)
     		");
    //$statement->execute($params);
    //$course = $event->get_record_snapshot('course', $event->objectid);
    }
    
    
}
