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
 * // Fetch the captured results and present them in a graphical form
 * @module     block_selfevaluation/result
 * @copyright  2023 Rohit <rx18007@edu.rta.lv>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    ['jquery', 'core/ajax'],
    // eslint-disable-next-line no-unused-vars
    function($, Ajax,) {
        return {
            init: function(teacherCourses, config, allUserArray) {
                let noAccess;
                if (!teacherCourses ) {
                    $("#context-select").prop('disabled', 'disabled');
                    noAccess = true;
                }
                // Add the "All User" option to the context-select if the setting is enabled
                if (config) {
                    $("<option value='all'>All User</option>").insertAfter($("#context-select option:first"));
                }
                let fileInfo;
                // eslint-disable-next-line no-unused-vars
                let sBasedData = "self-assessment-data-";
                // eslint-disable-next-line no-unused-vars
                let kBasedData = "knowledge-based-data-";
                // Select the check-select and quiz-select elements
                const checkSelect = document.getElementById('self-assessment-select');
                const quizSelect = document.getElementById('knowledge-based-select');
                const checkHeading = document.getElementById('self-assessment-heading');
                const checkHeadingValue = checkHeading.innerHTML;
                const quizHeading = document.getElementById('knowledge-based-heading');
                const quizHeadingValue = quizHeading.innerHTML;
                let userElement = $('#user-select');
                if (noAccess)
                {
                    allUserArray.forEach((user) => {
                        $("#user-select").append(`<option value="${user.userId}">${user.userFullName}</option>`);
                    });
                }// Handle change event of context-select
                $('#context-select').on("change", function() {
                    // Clear the existing options
                    userElement.empty();
                    userElement.html('<option value="">Please select a Student</option>');
                    // Filter the teacherCourses array based on the selected context
                    var selectedContext = $(this).val();
                    if (selectedContext === "all") {
                        allUserArray.forEach(function(user) {
                            // eslint-disable-next-line max-len
                            $("#user-select").append(`<option value="${user.userId}">${user.userFullName}</option>`);
                        });
                    } else {
                        var selectedCourse = teacherCourses.find(function(course) {
                            return course.courseId === selectedContext;
                        });
                        // Populate the user-select with options based on the selected course
                        if (selectedCourse) {
                            selectedCourse.studentIds.forEach(function(student) {
                                $("#user-select").append(`<option value="${student.id}">${student.name}</option>`);
                            });
                        }
                    }
                });
                $("#user-select").on("change", function() {
                    let checkFileCount = 0;
                    let quizFileCount = 0;
                    clearTableCell(sBasedData, kBasedData);
                    let UserId = userElement.val();
                    // Clear any existing options in the select elements, except the initial options
                    checkSelect.innerHTML = '<option value="">Please select a file</option>';
                    quizSelect.innerHTML = '<option value="">Please select a file</option>';
                    if (UserId) {
                        let request = {
                            methodname: 'blocks_selfevaluation_fetch_file',
                            args: {'UserId': UserId}
                        };
                        Ajax.call([request])[0].done(function(data) {
                            fileInfo = data.fileInfo;

                            // Iterate over the fileInfo array
                            fileInfo.forEach(file => {
                                // Check if the title contains the keyword "check"
                                if (file.title.toLowerCase().includes('check')) {
                                    checkFileCount++;
                                    // Create a new option element
                                    const option = document.createElement('option');
                                    option.text = file.title;
                                    option.value = file.fileId;
                                    // Add the option to the check-select element
                                    checkSelect.appendChild(option);
                                }
                                // Check if the title contains the keyword "quiz"
                                if (file.title.toLowerCase().includes('quiz')) {
                                    quizFileCount++;
                                    // Create a new option element
                                    const option = document.createElement('option');
                                    option.text = file.title;
                                    option.value = file.fileId;
                                    // Add the option to the quiz-select element
                                    quizSelect.appendChild(option);
                                }
                                checkHeading.innerHTML = checkHeadingValue + ' ' + checkFileAvailability(checkFileCount);
                                quizHeading.innerHTML = quizHeadingValue + ' ' + checkFileAvailability(quizFileCount);
                            });
                        }).fail(function(data){
                            // eslint-disable-next-line no-console
                            console.log(data);
                        });
                    }
                });
                checkSelect.addEventListener('change',
                    () => {
                        // eslint-disable-next-line no-unused-vars
                        let checkSelectValue = checkSelect.value;
                        for (let i = 0; i < fileInfo.length; i++) {
                            if (checkSelectValue == fileInfo[i].fileId) {
                                for (let j = 0; j < fileInfo[i].percentage.length; j++) {
                                    let tdElement = document.getElementById(sBasedData + j);
                                    tdElement.innerText = '';
                                    let levels = getLevel(fileInfo[i].percentage[j]);
                                    let value = "Percentage " + fileInfo[i].percentage[j] + '%\n Level: ' + levels;
                                    tdElement.innerText = value;
                                }
                            }
                        }
                    }
                );
                quizSelect.addEventListener('change',
                    () => {
                        // eslint-disable-next-line no-unused-vars
                        let quizSelectValue = quizSelect.value;
                        for (let i = 0; i < fileInfo.length; i++) {
                            if (quizSelectValue == fileInfo[i].fileId) {
                                for (let j = 0; j < fileInfo[i].percentage.length; j++) {
                                    let tdElement = document.getElementById(kBasedData + j);
                                    let levels = getLevel(fileInfo[i].percentage[j]);
                                    tdElement.innerText = '';
                                    let value = "Percentage " + fileInfo[i].percentage[j] + '%\n Level: ' + levels;
                                    tdElement.innerText = value;
                                }
                            }
                        }
                    }
                );
                /**
                 * Function to get the level values
                 *@param {int} value to retrive the levels
                 */
                function getLevel(value) {
                    if (value >= 0 && value <= 20) {
                        return "Foundation - Level 1";
                    } else if (value > 20 && value <= 40) {
                        return "Foundation - Level 2";
                    } else if (value > 40 && value <= 60) {
                        return "Intermediate - Level 3";
                    } else if (value > 60 && value <= 80) {
                        return "Intermediate - Level 4";
                    } else if (value > 80 && value <= 100) {
                        return "Advanced - Level 5";
                    } else {
                        return "Invalid value";
                    }
                }

                /**
                 *@param {string} sBasedData to clear the previous values
                 *@param {string} kBasedData to clear the previous Values
                 */
                function clearTableCell(sBasedData, kBasedData) {
                    for (let i = 0; i <= 5; i++) {
                        let tableCell = document.getElementById(sBasedData + i);
                        tableCell.innerText = '';
                        tableCell = document.getElementById(kBasedData + i);
                        tableCell.innerText = '';
                    }
                }

                /**
                 *@param {int} value number of files
                 */
                function checkFileAvailability(value) {
                    if (value === 0) {
                        return 'No Files Available';
                    } else if (value === 1) {
                        return '1 file Available';
                    } else {
                        return value + ' files Available';
                    }
                }
            }
        };
    });
