<?php
/**
 * Generates dynamic CSS to replace activity icons in Timeline/Dashboard blocks.
 *
 * @package    local_alpy_toolkit
 * @copyright  2026 SAVIO
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', false); // We need the user session to know their courses

require_once('../../config.php');
require_once($CFG->dirroot . '/course/format/alpy/lib.php');

// Cache handling to avoid generating this on every page load
// The ETag effectively caches it until the content changes, but for simplicity
// in this dev phase, we send headers. Ideally this should be cached better.
$lifetime = 60 * 60 * 24; // 1 day cache
header('Content-Type: text/css');
header('Cache-Control: public, max-age=' . $lifetime . ', immutable');
header('Pragma: public');

// Validate login provided NO_MOODLE_COOKIES is false
// require_login(); // Optional: if we want to valid login. Better to be safe.
if (!isloggedin() || isguestuser()) {
    exit;
}

// PERFORMANCE: Close session writing immediately. 
// This script only reads user data, it implies no changes to session.
// This allows the browser to download this CSS *in parallel* with other Moodle requests
// instead of waiting for the session lock to be released.
\core\session\manager::write_close();

global $DB, $USER, $CFG;

// 1. Get user's enrolled courses to limit the scope (Performance + Security)
// We explicitely request fields in the FIRST parameter.
$my_courses = enrol_get_my_courses('id, format');
if (empty($my_courses)) {
    exit;
}

// Filter only ALPY courses to respect architectural design
$course_ids = [];
foreach ($my_courses as $c) {
    if ($c->format === 'alpy') {
        $course_ids[] = $c->id;
    }
}

// If no Alpy courses found, stop here
if (empty($course_ids)) {
    exit;
}

// 2. Find all Alpy-tagged modules within these courses
// We need: cm.id, tag.rawname
list($insql, $inparams) = $DB->get_in_or_equal($course_ids);

$sql = "SELECT cm.id, t.rawname
          FROM {course_modules} cm
          JOIN {context} ctx ON (ctx.instanceid = cm.id AND ctx.contextlevel = " . CONTEXT_MODULE . ")
          JOIN {tag_instance} ti ON (ti.component = 'core' AND ti.itemtype = 'course_modules' AND ti.itemid = cm.id)
          JOIN {tag} t ON ti.tagid = t.id
         WHERE cm.course $insql";

$records = $DB->get_records_sql($sql, $inparams);

// 3. Generate CSS
echo "/** Alpy Toolkit Dynamic Icon Replacement **/\n";

$resources = \format_alpy::get_resource_definitions();
$iconCache = []; // Memoization for file system checks
$baseDir = $CFG->dirroot . '/course/format/alpy/pix/';
$baseUrl = $CFG->wwwroot . '/course/format/alpy/pix/';

foreach ($records as $rec) {
    $tagname = \core_text::strtolower(trim($rec->rawname));
    
    // Check if we already resolved this tag to avoid duplicated I/O calls
    if (array_key_exists($tagname, $iconCache)) {
        $iconUrl = $iconCache[$tagname];
    } else {
        // Resolve standard resource key
        $canonical = \format_alpy::resolve_resource_key($tagname);
        $iconUrl = false;
        
        if ($canonical) {
             // Check extensions priority: SVG -> PNG
             if (file_exists($baseDir . $canonical . '.svg')) {
                 $iconUrl = $baseUrl . $canonical . '.svg';
             } elseif (file_exists($baseDir . $canonical . '.png')) {
                 $iconUrl = $baseUrl . $canonical . '.png';
             }
        }
        // Cache the result (url string or false)
        $iconCache[$tagname] = $iconUrl;
    }

    if ($iconUrl) {
        // CSS Rule:
        // Target 1: Anchor with specific ID href (Timeline standard)
        // Target 2: Data attribute (some modern themes)
        
        $cmid = $rec->id;
        
        // Target specifically the Timeline and Recently Accessed blocks
        
        // CSS Image Replacement Technique (Cross-browser safe)
        // 1. We set width/height to the desired icon size.
        // 2. We use padding-left equal to width to push the original <img> content (src) out of the visible box.
        // 3. We set the new icon as the background image of the <img> element itself.
        // 4. We use box-sizing: border-box to include padding in the total width.
        
        $cssProperties = "
            /* Padding 'Mask' Technique (Restored as requested) */
            box-sizing: border-box !important;
            width: 24px !important;
            height: 24px !important;
            padding-left: 24px !important;
            padding-right: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            
            background-image: url('{$iconUrl}') !important;
            background-size: contain !important;
            background-repeat: no-repeat !important;
            background-position: center center !important;
        ";

        // 1. TIMELINE BLOCK
        // Use :has() to find the list item containing the specific activity link
        echo ".block_timeline .list-group-item:has(a[href*='id={$cmid}']) .activityiconcontainer img {\n";
        echo $cssProperties;
        echo "}\n";

        // 2. RECENTLY ACCESSED ITEMS BLOCK
        // The anchor itself wraps the content
        echo ".block_recentlyaccesseditems a[href*='id={$cmid}'] .activityiconcontainer img {\n";
        echo $cssProperties;
        echo "}\n";

        // 3. UPCOMING EVENTS BLOCK
        // Covers the side block and the expanded view
        echo ".block_upcoming .event:has(a[href*='id={$cmid}']) .activityiconcontainer img {\n";
        echo $cssProperties;
        echo "}\n";

        // 4. SINGLE ACTIVITY HEADER
        // When viewing the activity page itself, Moodle adds a 'cmid-ID' class to the body.
        echo "body.cmid-{$cmid} .page-header .activityiconcontainer img, \n";
        echo "body.cmid-{$cmid} .page-header img.icon, \n"; // Some themes use generic .icon class
        echo "body.cmid-{$cmid} .page-context-header .page-header-image img, \n"; // Common in non-Boost themes
        echo "body.cmid-{$cmid} .page-context-header .activityiconcontainer img, \n";
        echo "body.cmid-{$cmid} .page-context-header img.icon, \n";
        echo "body.cmid-{$cmid} .activity-header .activityiconcontainer img {\n";
        echo $cssProperties;
        echo "}\n\n";
    }
}
