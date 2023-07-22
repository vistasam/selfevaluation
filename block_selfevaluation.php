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
 * Block self_evaluation
 * @package   block_selfevaluation
 * @copyright 2023 Rohit <rx18008@edu.rta.lv>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_selfevaluation extends block_base {

    function init() {
        $this->title = "Self Evaluation Avg Values";/*get_string('pluginname', 'block_emotionanalysis')*/

    }
    function has_config()
    {
        return true;
    }
    /**
     * Core function, specifies where the block can be used.
     * @return array
     */
    public function applicable_formats() {
        return array('course-view' => true, 'mod' => true);
    }

    function get_content() {
        global $COURSE;
        if ($this->content !== NULL) {
            return $this->content;
        }
        $courseId = $COURSE->id;
        require_once('lib.php');
        $userArray = getEnrolledStudents();
        $studentArray = studentIdArray($userArray,$courseId);
        $fileTitles = blockTitleResult($studentArray);
        $statistics = calculateStatistics($fileTitles);
        $this->page->requires->js_call_amd('block_selfevaluation/blockResult', 'init',array($statistics));
        if(!$fileTitles)
        {
            $html = "No data available yet";
        }
        else{
        $html = html_writer::start_tag('select',[
            'id' => 'result-view',
            'width' => '20',
            ]);
        $html .= html_writer::tag('option', 'Please select a value', ['value' => '']);
        foreach($statistics as $title => $statistic)
        {
            $html .= '<option value="' . $statistic['id'] . '">' . $title . '</option>';
        }
        // Add custom CSS to control the width of the select element.
        $html .= html_writer::end_tag('select');
        $html .= "<br>";
        $html .= "<br>FOUNDATIONS AND ACCESS";
        $html .= "<p class='value-content' id='values-0'></p>";
        $html .= "INFORMATION AND DATA LITERACY";
        $html .= "<p class='value-content' id='values-1'></p>";
        $html .= "COMMUNICATION AND COLLABORATION";
        $html .= "<p class='value-content' id='values-2'></p>";
        $html .= "DIGITAL CONTENT CREATION";
        $html .= "<p class='value-content' id='values-3'></p>";
        $html .= "SAFETY";
        $html .= "<p class='value-content' id='values-4'></p>";
        $html .= "PROBLEM SOLVING AND CONTINUING LEARNING";
        $html .= "<p class='value-content' id='values-5'></p>";
        }
        $this->content = new stdClass;
        $this->content->text = $html;
        $custom_css = '
        <style>
        #result-view {
            width: 250px; /* Adjust the width to your desired size. */
        }
        </style>
        ';
        echo $custom_css;

        return $this->content;
    }
}



