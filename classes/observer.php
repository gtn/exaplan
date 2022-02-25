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
        
		/*TO Debug: $params = array(':moodleid' => 99,':companyname' => serialize($dateIds),);
    	$statement = $pdo->prepare("
        INSERT INTO mdl_block_exaplanmoodles
        (companyname, moodleid) VALUES (:companyname,:moodleid)");
     	$statement->execute($params);end to debug*/
		
        // Get user enrolment info from event.
        $cp = (object)$event->other['userenrolment'];
        $userId = $cp->userid;
        $pUserId = getPuser($userId)['id'];
        $courseId = $cp->courseid;

        if ($pUserId > 0 && $courseId > 0) {
            $pdo = getPdoConnect();

            // delete data from mdl_block_exaplanpuser_date_mm
            //$sql = 'DELETE FROM mdl_block_exaplanpuser_date_mm WHERE puserid = :puserid';
            //$statement = $pdo->prepare($sql);
            //$statement->execute([':puserid' => $pUserId]);*/

            // delete data, related to module parts
            $courseData = $DB->get_record('course', ['id' => $courseId]);
            if ($courseData->idnumber) {
                // find related modulesets
                $sql = 'SELECT ms.* 
                          FROM mdl_block_exaplanmodulesets ms                            
                          WHERE ms.courseidnumber = :idnumber';
                $statement = $pdo->prepare($sql);
                $statement->execute([':idnumber' => $courseData->idnumber]);
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                $modulesets = $statement->fetchAll();
                if (is_array($modulesets) && count($modulesets) > 0) {
                    $modulesetIds = array_map(function($ms) {return $ms['id'];}, $modulesets);
					 // get module parts
                    $sql = 'SELECT mp.* 
                          FROM mdl_block_exaplanmoduleparts mp                            
                          WHERE mp.modulesetid IN ('.implode(',', $modulesetIds).')';
                    $statement = $pdo->prepare($sql);
                    $statement->execute();
                    $statement->setFetchMode(PDO::FETCH_ASSOC);
                    $moduleparts = $statement->fetchAll();
                    if (is_array($moduleparts) && count($moduleparts) > 0) {
                        $modulepartIds = array_map(function($mp) {return $mp['id'];}, $moduleparts);
                        // get related Dates
						
		
                        $sql = 'SELECT pdmm.* 
                                    FROM mdl_block_exaplanpuser_date_mm pdmm
                                      LEFT JOIN mdl_block_exaplandates d ON d.id = pdmm.dateid 
                                    WHERE pdmm.puserid = :puserid 
                                      AND d.modulepartid IN ('.implode(',', $modulepartIds).')';
									  $sql.=' AND d.date >= '.strtotime("today", time()).' ';             
						 $statement = $pdo->prepare($sql);
                        $statement->execute([':puserid' => $pUserId]);
                        $statement->setFetchMode(PDO::FETCH_ASSOC);
						

		
                        $dates = $statement->fetchAll();
                        if ($dates && count($dates) > 0) {
                            $dateMmIds = array_map(function($d) {return $d['id'];}, $dates);
                            // delete from mdl_block_exaplanpuser_date_mm
                            $sql = 'DELETE FROM mdl_block_exaplanpuser_date_mm WHERE id IN ('.implode(',', $dateMmIds).')';
							$statement = $pdo->prepare($sql);
                            $statement->execute();
                        }

                        // delete from mdl_block_exaplandesired
                        $sql = 'DELETE FROM mdl_block_exaplandesired WHERE puserid = :puserid AND modulepartid IN ('.implode(',', $modulepartIds).')';
                        $statement = $pdo->prepare($sql);
                        $statement->execute([':puserid' => $pUserId]);
                    }
                }

            }
        }

    
    }
    
    
}
