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
 * Ajax call for the selfevaluation block
 *
 * @copyright 2023 Rohit {rx18008@edu.rta.lv}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   block_emotionanalysis
 */

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->libdir . '/filestorage/file_system.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot.'/blocks/selfevaluation/PDFparser/alt_autoload.php-dist');

class block_selfevaluation_external extends external_api{
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function fetch_files_parameters(){
        return new external_function_parameters([
            'UserId' => new external_value(PARAM_INT,'User id'),
        ]);
    }
    /**
     * Validate Captured data and store into database
     * @throw dml_exception
     * return true if data is inserted successfully
     * @throws dml_exception
     */
    public static function fetch_files($UserId){
        require_login();
        global $USER,$DB;
        $parser = new \Smalot\PdfParser\Parser();
        self::validate_parameters(self::fetch_files_parameters(), array('UserId' => $UserId));
        $context = context_user::instance($UserId);
        $fs = get_file_storage();
        $itemid = 1; //
        $fs = get_file_storage();
        if ($files = $fs->get_area_files($context->id, 'block_selfevaluation', 'self_evaluation_doc', 1, 'sortorder', false)) {
            foreach ($files as $file) {
                $pdfFileUrl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
                $filename = $file->get_filename();
                $dataFile = $fs->get_file($context->id, 'block_selfevaluation', 'self_evaluation_doc', 1, '/', $filename);
                $pdfText = $dataFile->get_content();
                $pdf = $parser->parseContent($pdfText);
                $text = $pdf->getText();

                $pattern = '/(.*?)RESULTS \(\d{2}\.\d{2}\.\d{4}\)/';
                preg_match($pattern, $text, $matches);
                if (isset($matches[0])) {
                    $titleText = trim($matches[0]);
                    $lowercaseTitleText = strtolower($titleText);
                    if (strpos($lowercaseTitleText, "check") !== false) {
                        $cPattern = '/Competence area \d+\.\s.+?\nSelf-assessment questions: (\d+)%/';
                        preg_match_all($cPattern, $text, $matches);
                        $cPercentages = $matches[1];
                        $fileInfo[] = [
                            'fileId' => $file->get_id(),
                            'filename' => $file->get_filename(),
                            'title' => $titleText,
                            'percentage' => $cPercentages,
                        ];
                    } elseif (strpos($lowercaseTitleText, "quiz") !== false) {
                        $qPattern = '/Competence area \d+\.\s.+?\nKnowledge-based questions: (\d+)%/';
                        preg_match_all($qPattern,$text, $matches);
                        $qPercentages = $matches[1];
                        $fileInfo[] = [
                            'fileId' => $file->get_id(),
                            'filename' => $file->get_filename(),
                            'title' => $titleText,
                            'percentage' => $qPercentages,
                        ];
                    }
                };
            }
        }
        return ['fileInfo' => $fileInfo];
    }
    /**
     * Returns description of method
     * @return external_description
     */
    public static function fetch_files_returns(){
        return new external_single_structure([
            'fileInfo' => new external_multiple_structure(
                new external_single_structure([
                    'fileId' => new external_value(PARAM_INT, 'count of each emotion state'),
                    'filename' => new external_value(PARAM_TEXT, 'Emotion State'),
                    'title' => new external_value(PARAM_TEXT,'Check Title of the File'),
                    'percentage' => new external_multiple_structure(
                        new external_value(PARAM_INT, 'percentage value results')
                    ),
                ])
            ),
        ]);
    }
}