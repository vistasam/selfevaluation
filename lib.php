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
 * @package   local_selfevalution
 * @copyright 2023 Rohit {rx18008@edu.rta.lv}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use core\output\notification;
global $CFG;
require_once($CFG->libdir . '/filestorage/file_system.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot.'/blocks/selfevaluation/PDFparser/alt_autoload.php-dist');
function block_selfevaluation_before_footer(){
    global $PAGE,$USER;

    if ($PAGE->url->compare(new moodle_url('/my/'))) {
        $value = get_config('block_selfevaluation', 'allUsers');
        $authenticatedUser = convertStringToArray($value);
        $userArray = checkFileExistence($USER->id);
        $allUserArray = getUserArray();
        if(!empty($allUserArray)) {
            if (in_array($USER->id, $authenticatedUser) or $userArray) {
                $url = new moodle_url('/blocks/selfevaluation/result.php');
                $link = '<a href="' . $url . '">Click Here</a>';

                // Display the notification with the link.
                $message = 'View Result for Fit4Internet files. ' . $link;
                \core\notification::add($message, notification::NOTIFY_SUCCESS);
            }
        }
        $url = new moodle_url('/blocks/selfevaluation/upload.php');
        $link = '<a href="' . $url . '">Click Here</a>';

        // Display the notification with the link.
        $message = 'Manage Fit4Internet File. ' . $link;
        \core\notification::add($message, notification::NOTIFY_SUCCESS);
    }
}
function allUser()
{
    global $DB;

    // Fetch all user records from the Moodle user table.
    $userRecords = $DB->get_records('user', null, '', 'id, username');

    // Initialize an empty array to store the user IDs and usernames.
    $users = array();
    // Geting all course IDs
    $courseIds = $DB->get_records('course');

    foreach ($courseIds as $courseid => $courserecord) {
        // Check if the user is a teacher in this course
        foreach ($userRecords as $user) {
            if (has_capability('moodle/course:manageactivities', context_course::instance($courseid), $user->id)) {
                $users[$user->id] = $user->username;
            }
        }
    }
    return $users;
}

function convertStringToArray($string) {
    $array = explode(',', $string);
    $array = array_map('intval', $array);
    return $array;
}

function getUserArray()
{
    global $DB;
    $component = 'block_selfevaluation';
    $filearea = 'self_evaluation_doc';
    $userIds = $DB->get_fieldset_select('files', 'DISTINCT userid', 'component = ? AND filearea = ? AND filename != ?', [$component, $filearea, '.']);

    // Initialize the array to store the user details
    $userArray = [];

    foreach ($userIds as $userId) {
        $users = $DB->get_records('user', ['id' => $userId]);
        if ($users) {
            foreach ($users as $user) {
                $userArray[] = [
                    'userId' => $user->id,
                    'userFullName' => $user->firstname . ' ' . $user->lastname,
                ];
            }
        }
    }
    return $userArray;
}

function checkFileExistence($userId)
{
    global $DB, $USER;

    $sql = "SELECT *
        FROM {files}
        WHERE filearea = 'self_evaluation_doc'
        AND component = 'block_selfevaluation'
        AND userid = ?
        AND filename != '.'";

    $params = [$userId];
    $recordExists = $DB->get_records_sql($sql, $params);

    if ($recordExists) {
        $user = $DB->get_record('user', ['id' => $userId]);
        if ($user) {
            $userArray = [
                'userId' => $user->id,
                'userFullName' => $user->firstname . ' ' . $user->lastname
            ];
            return [$userArray]; // Return as an indexed array
        }
    }

    return []; // Return an empty array when record does not exist
}


/*function checkFileRecords($userId)
{
    global $DB, $USER;

    $sql = "SELECT *
        FROM {files}
        WHERE filearea = 'self_evaluation_doc'
        AND component = 'block_selfevaluation'
        AND userid = ?
        AND filename != '.'";

    $params = [$userId];
    $recordExists = $DB->get_records_sql($sql, $params);

    if ($recordExists) {
        return true;
    } else {
        return false; // Return null when record does not exist
    }
}*/
function getEnrolledStudents()
{
    global $DB, $USER;

    // Get all courses
    $coursesData = $DB->get_records('course');

    $teacherCourses = array(); // Initialize the teacher courses array

    foreach ($coursesData as $courseData) {
        // Check if the current user has the capability to manage activities in the course
        if (has_capability('moodle/course:manageactivities', context_course::instance($courseData->id), $USER->id)) {
            // Get the course ID and name
            $courseId = $courseData->id;
            $courseName = $courseData->fullname;

            // Get the student IDs enrolled in the course
            $studentIds = array();
            $enrolledStudents = get_enrolled_users(context_course::instance($courseId), 'mod/quiz:attempt', 0, 'u.*',);
            foreach ($enrolledStudents as $student) {
                $checkFile = checkFileExistence($student->id);
                if ($checkFile) {
                    $studentIds[] = array(
                        'id' => $student->id,
                        'name' => $student->firstname . ' ' . $student->lastname
                    );
                }
            }
            // Only add the course data if students are enrolled
            if (!empty($studentIds)) {
                // Add the course data to the teacher courses array
                $teacherCourses[] = array(
                    'courseId' => $courseId,
                    'courseName' => $courseName,
                    'studentIds' => $studentIds
                );
            }
        }
    }
    if($teacherCourses)
    {
        return $teacherCourses;
    }
    else
    {
        return null;
    }

}

function studentIdArray($courseData,$courseId){
    $studentArray = array();

    foreach($courseData as $course)
    {
        if ($course['courseId'] === $courseId) {
            foreach($course['studentIds'] as $student)
            {
                $studentArray[] = $student['id'];
            }
            break;
        }
    }
    return $studentArray;
}

function blockTitleResult($userArray)
{
    require_login();
    global $USER, $DB;
    $parser = new \Smalot\PdfParser\Parser();
    $itemid = 1; //
    $fs = get_file_storage();
    foreach ($userArray as $userId) {
        $context = context_user::instance($userId);
        if ($files = $fs->get_area_files($context->id, 'block_selfevaluation', 'self_evaluation_doc', 1, 'sortorder', false)) {
            foreach ($files as $file) {
                $pdfFileUrl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
                $filename = $file->get_filename();
                $dataFile = $fs->get_file($context->id, 'block_selfevaluation', 'self_evaluation_doc', 1, '/', $filename);
                $pdfText = $dataFile->get_content();
                $pdf = $parser->parseContent($pdfText);
                $text = $pdf->getText();
                $pattern = '/(.*?)skills/';
                preg_match($pattern, $text, $matches);
                if (isset($matches[0])) {
                    $titleText = trim($matches[0]);
                    $lowercaseTitleText = strtolower($titleText);
                    if (strpos($lowercaseTitleText, "check") !== false) {
                        $cPattern = '/Competence area \d+\.\s.+?\nSelf-assessment questions: (\d+)%/';
                        preg_match_all($cPattern, $text, $matches);
                        $cPercentages = $matches[1];
                        $fileInfo[] = [
                            'title' => $titleText,
                            'percentage' => $cPercentages,
                        ];
                    } elseif (strpos($lowercaseTitleText, "quiz") !== false) {
                        $qPattern = '/Competence area \d+\.\s.+?\nKnowledge-based questions: (\d+)%/';
                        preg_match_all($qPattern,$text, $matches);
                        $qPercentages = $matches[1];
                        $fileInfo[] = [
                            'title' => $titleText,
                            'percentage' => $qPercentages,
                        ];
                    }
                };
            }
        }
    }
    return $fileInfo;
}
function calculateStatistics($data) {
    $statistics = array();

    foreach ($data as $id => $item) {
        $title = $item['title'];
        $percentages = $item['percentage'];

        if (!isset($statistics[$title])) {
            $statistics[$title] = array(
                'id' => $id,
                'title' => $title, // Add the 'title' key to each sub-array
                'values' => array(),
                'min' => $percentages,
                'max' => $percentages,
                'sum' => $percentages,
                'count' => 1,
            );
        } else {
            $count = count($percentages);
            for ($i = 0; $i < $count; $i++) {
                $statistics[$title]['min'][$i] = min($statistics[$title]['min'][$i], $percentages[$i]);
                $statistics[$title]['max'][$i] = max($statistics[$title]['max'][$i], $percentages[$i]);
                $statistics[$title]['sum'][$i] += $percentages[$i];
            }
            $statistics[$title]['count']++;
        }
    }
    // Calculate the averages for each index for each title.
    foreach ($statistics as $title => $data) {
        $statistics[$title]['avg'] = array_map(function ($sum) use ($data) {
            return $sum / $data['count'];
        }, $data['sum']);

        // Format the output for each title.
        $formattedData = array();
        $count = count($data['min']);
        for ($i = 0; $i < $count; $i++) {
            $formattedData[] = "{$data['min'][$i]}(min),{$data['max'][$i]}(max),avg({$statistics[$title]['avg'][$i]})";
        }
        $statistics[$title]['values'] = $formattedData;
        unset($statistics[$title]['min'], $statistics[$title]['max'], $statistics[$title]['sum'], $statistics[$title]['count'], $statistics[$title]['avg']);
    }

    return $statistics;
}




function block_selfevaluation_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array())
{
    global $DB;

    require_login();

    if ($filearea != 'self_evaluation_doc') {
        return false;
    }

    $itemid = (int)array_shift($args);

    if ($itemid != 1) {
        return false;
    }

    $fs = get_file_storage();

    $filename = array_pop($args);

    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $file = $fs->get_file($context->id, 'block_selfevaluation', $filearea, $itemid, $filepath, $filename);

    if (!$file) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!
}
