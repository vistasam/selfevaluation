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
 * This Page is handling the file upload.
 *
 * @package   block_selfevaluation
 * @copyright 2023 Rohit <rx18008@edu.rta.lv>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG, $DB, $USER, $OUTPUT, $PAGE;
require_once(dirname(__FILE__) . '/../../config.php'); // Include Moodle configuration file
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->dirroot.'/blocks/selfevaluation/classes/form/upload.php');
require_once($CFG->dirroot.'/blocks/selfevaluation/PDFparser/alt_autoload.php-dist');
use core\notification;

$parser = new \Smalot\PdfParser\Parser();
$PAGE->set_url(new moodle_url('/blocks/selfevaluation/upload.php'));
$PAGE->set_title('Upload');
$context = context_user::instance($USER->id);
$filemanageropts = array('subdirs' => 0, 'maxbytes' => '0', 'maxfiles' => 50, 'context' => $context);
$customdata = array('filemanageropts' => $filemanageropts);
echo $OUTPUT->header();
// Displaying Form
$mform = new upload();
$itemid = 1; //
$validation = false;
$draftitemid = file_get_submitted_draft_itemid('self_evaluation_doc');
file_prepare_draft_area($draftitemid, $context->id, 'block_selfevaluation', 'self_evaluation_doc', $itemid, $filemanageropts);
$entry = new stdClass();
$entry->self_evaluation_doc = $draftitemid;
$mform->set_data($entry);
if($mform->is_cancelled()) {
    \core\notification::error(get_string('cancelmsg','block_selfevaluation'));
    $mform->display();
    echo $OUTPUT->footer();
} elseif ($data = $mform->get_data()) {
    file_save_draft_area_files($draftitemid, $context->id, 'block_selfevaluation', 'self_evaluation_doc', $itemid, $filemanageropts);
    $fs = get_file_storage();
    if ($files = $fs->get_area_files($context->id, 'block_selfevaluation', 'self_evaluation_doc', 1, 'sortorder', false)) {
        $totalNumberOfFiles = count($files);
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $dataFile = $fs->get_file($context->id, 'block_selfevaluation', 'self_evaluation_doc', 1, '/', $filename);
            $pdfText = $dataFile->get_content();
            $pdf = $parser->parseContent($pdfText);
            $text = $pdf->getText();
            $pattern = '/(.*?)RESULTS \(\d{2}\.\d{2}\.\d{4}\)/';
            preg_match($pattern, $text, $matches);
            if (!isset($matches[0])) {
                \core\notification::error(get_string('errorfilemsg','block_selfevaluation'));
                $file->delete();
                redirect($PAGE->url,get_string('redirectmsg','block_selfevaluation'),5);
            }
            else
            {
                $validation = true;
            }
        }
        if($validation)
        {
            \core\notification::success(get_string('success','block_selfevaluation'));
        }
    }
    $mform->display();
    echo $OUTPUT->footer();
}
else {
    $mform->display();
    echo $OUTPUT->footer();
}
