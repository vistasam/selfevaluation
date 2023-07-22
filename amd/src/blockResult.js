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
            // eslint-disable-next-line no-unused-vars
            init: function (statistics) {
                $('#result-view').on('change', () => {
                    // eslint-disable-next-line no-unused-vars
                    let selectedId = parseInt($('#result-view').val());
                    // Find the selected data based on the ID
                    // eslint-disable-next-line no-unused-vars
                    let selectedData = Object.values(statistics).find(item => item.id === selectedId);
                    // Check if selectedData exists and has the required properties
                    if (selectedData && selectedData.hasOwnProperty('title') && selectedData.hasOwnProperty('values')) {
                        // Clear previous content
                        $('.values-content').empty();
                        // Display the values in the corresponding <p> elements
                        selectedData.values.forEach((value, index) => {
                            let paragraphId = `values-${index}`;
                            $(`#${paragraphId}`).text(formatValue(value));
                        });
                    } else {
                        // Handle case if selectedData is not found or missing properties
                        // eslint-disable-next-line no-console
                        console.error('Invalid data for selected ID:', selectedId);
                    }
                });
                // Helper function to format the value as desired
                /**
                 *format value
                 * @param {text} value result
                 */
                function formatValue(value) {
                    // Split the value into minimum, maximum, and average parts
                    let parts = value.split(',');

                    // Extract the values from each part
                    let min = parts[0].split('(')[0];
                    let max = parts[1].split('(')[0];
                    let avg = parts[2].split('(')[1].split(')')[0];

                    // Return the formatted string
                    return `Min - ${min}, Max - ${max}, Avg - ${avg}`;
                }
            }
        };
    });