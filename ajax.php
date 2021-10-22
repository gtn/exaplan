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
    case 'addUserDate':
//        $dateId = required_param('dateId', PARAM_INT);
        $dateId = 1;
        $date = optional_param('date', '', PARAM_TEXT);
        $dateTS = DateTime::createFromFormat('Y-m-d', $date)->getTimestamp();
        $middayType = optional_param('middayType', BLOCK_EXAPLAN_MIDDATE_ALL, PARAM_INT);
        setPrefferedDate($dateId, getPuser($USER->id)['id'], $dateTS, $middayType);
        $allUserData = block_exaplan_get_calendar_data(getPuser($USER->id)['id']);
        echo json_encode($allUserData);
        exit;
        break;
}