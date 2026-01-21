<?php
/**
 * Services definition for local_alpy_toolkit.
 *
 * @package    local_alpy_toolkit
 * @copyright  2026 SAVIO
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_alpy_toolkit_get_activity_icons' => [
        'classname'   => 'local_alpy_toolkit\external\icons',
        'methodname'  => 'get_activity_icons',
        'description' => 'Retrieves custom icons for a list of course modules if they belong to an Alpy course.',
        'type'        => 'read',
        'ajax'        => true,
    ],
];
