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

function block_exaplan_select_period_view() {
    $content = '<div id="block_exaplan_dashboard_calendar">';
    $content .= '<table>';
    $content .= '<tr>';
    $content .= '<td><div id="month1"></div></td>';
    $content .= '<td><div id="month2"></div></td>';
    $content .= '</tr>';
    $content .= '</table>';
//    $content .= '<div id="month3"></div>';
    $content .= '</div>';

    return $content;
}

