<?php
/**
 * Version details for local_alpy_toolkit.
 *
 * @package    local_alpy_toolkit
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2026012000; // YYYYMMDDXX (year, month, day, 2-digit version number).
$plugin->requires = 2022041900; // Moodle 4.0+
$plugin->component = 'local_alpy_toolkit';
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '1.0.0';

$plugin->dependencies = [
    'format_alpy' => 2026012000,
];
