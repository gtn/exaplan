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
        $dateId = required_param('dateId', PARAM_INT);
        $date = optional_param('date', '', PARAM_TEXT);
        $result = [$dateId, $date];
        echo json_encode($result);
        exit;
        break;
}