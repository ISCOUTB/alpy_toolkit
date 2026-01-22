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
// We need: cm.id, cm.instance, m.name (modulename), tag.rawname
list($insql, $inparams) = $DB->get_in_or_equal($course_ids);

$sql = "SELECT cm.id, cm.instance, m.name as modulename, t.rawname
          FROM {course_modules} cm
          JOIN {modules} m ON m.id = cm.module
          JOIN {context} ctx ON (ctx.instanceid = cm.id AND ctx.contextlevel = " . CONTEXT_MODULE . ")
          JOIN {tag_instance} ti ON (ti.component = 'core' AND ti.itemtype = 'course_modules' AND ti.itemid = cm.id)
          JOIN {tag} t ON ti.tagid = t.id
         WHERE cm.course $insql";

$records = $DB->get_records_sql($sql, $inparams);

// Pre-fetch calendar events for these courses to optimize Upcoming Events targeting
// We need to map (modulename, instanceid) -> [eventid1, eventid2...]
$events_by_instance = [];
$events_sql = "SELECT id, modulename, instance 
                 FROM {event} 
                WHERE courseid $insql 
                  AND modulename IS NOT NULL 
                  AND instance IS NOT NULL";
$events = $DB->get_recordset_sql($events_sql, $inparams);
foreach ($events as $event) {
    if (!isset($events_by_instance[$event->modulename])) {
        $events_by_instance[$event->modulename] = [];
    }
    if (!isset($events_by_instance[$event->modulename][$event->instance])) {
        $events_by_instance[$event->modulename][$event->instance] = [];
    }
    $events_by_instance[$event->modulename][$event->instance][] = $event->id;
}
$events->close();

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
        
        $containerCssProperties = "
            background-color: #e5f2fe !important;
            border: 1px solid rgba(40, 130, 211, 0.1) !important;
        ";

        $imgCssProperties = "
            /* Padding 'Mask' Technique */
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
            
            filter: none !important;
            border-radius: 0 !important;
        ";

        // 1. TIMELINE BLOCK
        // Container
        echo ".block_timeline .list-group-item:has(a[href*='id={$cmid}']) .activityiconcontainer {\n";
        echo $containerCssProperties;
        echo "}\n";
        // Image - targeting specific classes to override Moodle's !important filters
        echo ".block_timeline .list-group-item:has(a[href*='id={$cmid}']) .activityiconcontainer img.activityicon,\n";
        echo ".block_timeline .list-group-item:has(a[href*='id={$cmid}']) .activityiconcontainer img.icon {\n";
        echo $imgCssProperties;
        echo "}\n";

        // 2. RECENTLY ACCESSED ITEMS BLOCK
        // Container
        echo ".block_recentlyaccesseditems a[href*='id={$cmid}'] .activityiconcontainer {\n";
        echo $containerCssProperties;
        echo "}\n";
        // Image - Increased specificity to beat .activityiconcontainer.assessment .activityicon
        echo ".block_recentlyaccesseditems a[href*='id={$cmid}'] .activityiconcontainer img.activityicon,\n";
        echo ".block_recentlyaccesseditems a[href*='id={$cmid}'] .activityiconcontainer img.icon {\n";
        echo $imgCssProperties;
        echo "}\n";

        // 3. UPCOMING EVENTS BLOCK AND CALENDAR EVENTS
        if (isset($events_by_instance[$rec->modulename]) && isset($events_by_instance[$rec->modulename][$rec->instance])) {
            foreach ($events_by_instance[$rec->modulename][$rec->instance] as $eventid) {
                // 3.A UPCOMING EVENTS BLOCK
                // Container
                echo ".block_calendar_upcoming .event:has(a[data-event-id='{$eventid}']) .activityiconcontainer,\n";
                echo ".block_upcoming .event:has(a[data-event-id='{$eventid}']) .activityiconcontainer {\n";
                echo $containerCssProperties;
                echo "}\n";
                // Image
                echo ".block_calendar_upcoming .event:has(a[data-event-id='{$eventid}']) .activityiconcontainer img.activityicon,\n";
                echo ".block_upcoming .event:has(a[data-event-id='{$eventid}']) .activityiconcontainer img.activityicon,\n";
                echo ".block_calendar_upcoming .event:has(a[data-event-id='{$eventid}']) .activityiconcontainer img.icon,\n";
                echo ".block_upcoming .event:has(a[data-event-id='{$eventid}']) .activityiconcontainer img.icon {\n";
                echo $imgCssProperties;
                echo "}\n";
                
                // 3.B CALENDAR VIEW
                $calendarImgCss = "
                    box-sizing: border-box !important;
                    width: 16px !important; 
                    height: 16px !important;
                    padding-left: 16px !important;
                    padding-right: 0 !important;
                    padding-top: 0 !important;
                    padding-bottom: 0 !important;
                    
                    background-image: url('{$iconUrl}') !important;
                    background-size: contain !important;
                    background-repeat: no-repeat !important;
                    background-position: center center !important;
                    
                    filter: none !important; 
                    border-radius: 0 !important;
                ";
                
                // Target the icon inside the event card header in calendar view
                echo ".calendarwrapper .event[data-event-id='{$eventid}'] .box img.icon,\n";
                echo ".calendarwrapper .event[data-event-id='{$eventid}'] .card-header img.icon {\n";
                echo $calendarImgCss;
                echo "}\n";
            }
        }

        // 4. SINGLE ACTIVITY HEADER
        // Container
        echo "body.cmid-{$cmid} .page-header .activityiconcontainer, \n";
        echo "body.cmid-{$cmid} .page-context-header .activityiconcontainer, \n";
        echo "body.cmid-{$cmid} .activity-header .activityiconcontainer {\n";
        echo $containerCssProperties;
        echo "}\n";
        
        // Image
        echo "body.cmid-{$cmid} .page-header .activityiconcontainer img, \n";
        echo "body.cmid-{$cmid} .page-context-header .activityiconcontainer img, \n";
        echo "body.cmid-{$cmid} .activity-header .activityiconcontainer img {\n";
        echo $imgCssProperties;
        echo "}\n\n";
    }
}
