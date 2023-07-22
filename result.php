<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Version information
 *
 * @package   block_selfevalution
 * @copyright 2023 Rohit {rx18008@edu.rta.lv}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
global $CFG, $USER, $DB, $OUTPUT, $PAGE;
require_once($CFG->libdir . '/filestorage/file_system.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot.'/blocks/selfevaluation/PDFparser/alt_autoload.php-dist');
require_once('lib.php');
require_login();

$PAGE->set_url(new moodle_url('/blocks/selfevaluation/result.php'));
$PAGE->set_title('View Result Here');
$courseDetails = getEnrolledStudents();
$value = get_config('block_selfevaluation','allUsers');
$authenticatedUser = convertStringToArray($value);
if(in_array($USER->id,$authenticatedUser))
{
    $configStatus = true;
    $userArray = getUserArray();
}
else{
    $configStatus = false;
    $userArray = checkFileExistence($USER->id);
}
$PAGE->requires->js_call_amd('block_selfevaluation/result','init',array($courseDetails,$configStatus,$userArray));
$context = context_user::instance($USER->id);

$templatecontext = [
    'courseDetails' => $courseDetails,
];
echo $OUTPUT->header();

echo $OUTPUT->render_from_template('block_selfevaluation/result', $templatecontext);

echo $OUTPUT->footer();