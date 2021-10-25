<?php
// This file is part of Exabis Planning Tool
//
// (c) 2021 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

require __DIR__.'/inc.php';

$action = required_param('action', PARAM_TEXT);

//require_login($course); // TODO: needed?
$isAdmin = block_exaplan_is_admin();

require_sesskey();

switch($action) {
    case 'addUserDisiredDate':
        $pUserId = getPuser($USER->id)['id'];
      $date = optional_param('date', date('Y-m-d'), PARAM_TEXT);
      $modulepartId = required_param('modulepartId', PARAM_INT);
      $dateTS = DateTime::createFromFormat('Y-m-d', $date)->setTime(0, 0)->getTimestamp();
      if ($dateTS < strtotime("today", time())) {
        // selected date must be not in past
        echo 'ERROR';
        exit;
      }
      $middayType = optional_param('middayType', BLOCK_EXAPLAN_MIDDATE_ALL, PARAM_INT);
      $newDateId = setDesiredDate($modulepartId, $pUserId, $dateTS, $middayType, $pUserId);
      $allUserData = block_exaplan_get_data_for_calendar(getPuser($USER->id)['id'], 'all');
      echo json_encode($allUserData);
      exit;
      break;
    case 'addUserDate': // TODO: deprecated?
        $pUserId = getPuser($USER->id)['id'];
//        $dateId = required_param('dateId', PARAM_INT);
//        $dateId = 1;
        $date = optional_param('date', date('Y-m.-d'), PARAM_TEXT);
        $modulepartId = required_param('modulepartId', PARAM_INT);
        $dateTS = DateTime::createFromFormat('Y-m-d', $date)->setTime(0, 0)->getTimestamp();
        if ($dateTS < strtotime("today", time())) {
            // selected date must be not in past
            echo 'ERROR';
            exit;
        }
        $middayType = optional_param('middayType', BLOCK_EXAPLAN_MIDDATE_ALL, PARAM_INT);
        $newDateId = setPrefferedDate($modulepartId, $pUserId, $dateTS, $middayType);
        if ($newDateId != '_NEW' && $newDateId > 0) {
            // add a new relation to existing date
            $newRelation = addPUserToDate($newDateId, $pUserId);
            if ($newRelation != '_NEW' && $newRelation > 0) {
                // delete existing relation. If the user clicked again on the date
                removePUserFromDate($newDateId, $pUserId);
                // if the "date" is empty (no related users) - delete it:
                removeDateIfNoUsers($newDateId);
            }
        }
        $allUserData = block_exaplan_get_calendar_data(getPuser($USER->id)['id']);
        echo json_encode($allUserData);
        exit;
        break;
}