<?php
$functions = array(
    'blocks_selfevaluation_fetch_file' => array(
        'classname' => 'block_selfevaluation_external',
        'methodname' => 'fetch_files',
        'classpath' => 'blocks/selfevaluation/externallib.php',
        'description' => 'Fetching File Information Related to Student',
        'type' => 'write',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => '',
    ),
);