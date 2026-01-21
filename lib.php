<?php
/**
 * Main library file for local_alpy_toolkit.
 *
 * @package    local_alpy_toolkit
 * @copyright  2026 SAVIO - Sistema de Aprendizaje Virtual Interactivo (UTB)
 * @author     SAVIO Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extends the course module edit form to include Alpy resource selector.
 *
 * @param mixed $formwrapper The form wrapper
 * @param MoodleQuickForm $mform The Moodle form
 */
function local_alpy_toolkit_coursemodule_standard_elements($formwrapper, $mform) {
    global $COURSE, $CFG;

    // Only apply to courses using the Alpy format
    if ($COURSE->format !== 'alpy') {
        return;
    }

    // Ensure we can access format_alpy definitions
    require_once($CFG->dirroot . '/course/format/alpy/lib.php');
    if (!class_exists('format_alpy')) {
        return;
    }

    // Get resource definitions from the format
    $resources = \format_alpy::get_resource_definitions();
    
    // Prepare options for the select box
    $options = ['' => get_string('no_specific_type', 'local_alpy_toolkit')];
    
    // Helper array to avoid duplicates (since get_resource_definitions returns keys for both English and Spanish)
    $processed_icons = [];

    foreach ($resources as $key => $data) {
        // Use localized string for display
        $stringkey = 'resourcetype_' . $key;
        if (get_string_manager()->string_exists($stringkey, 'local_alpy_toolkit')) {
             $displayname = get_string($stringkey, 'local_alpy_toolkit');
        } else {
             // Fallback to capitalized key if translated string missing
             $displayname = ucfirst($key);
        }
        
        $options[$key] = $displayname;
    }
    
    // Add header
    $mform->addElement('header', 'alpy_header', get_string('pluginname', 'local_alpy_toolkit'));
    
    // Add selector
    $mform->addElement('select', 'local_alpy_type', get_string('alpy_resource_type', 'local_alpy_toolkit'), $options);
    $mform->addHelpButton('local_alpy_type', 'alpy_resource_type', 'local_alpy_toolkit');

    // If we are editing an existing module, try to set the current value based on tags
    $cmid = optional_param('update', 0, PARAM_INT);
    if ($cmid) {
        $tags = \core_tag_tag::get_item_tags('core', 'course_modules', $cmid);
        foreach ($tags as $tag) {
            $tagname = \core_text::strtolower(trim($tag->rawname));
            if (array_key_exists($tagname, $options)) {
                $mform->setDefault('local_alpy_type', $tagname);
                break; // Found the Alpy tag
            }
        }
    }
}

/**
 * Handles the saving of the Alpy resource type.
 *
 * This function triggers after the course module form is submitted.
 * It manages the tagging of the activity based on the selected type.
 *
 * @param object $moduleinfo The module info object
 * @param object $course The course object
 */
function local_alpy_toolkit_coursemodule_edit_post_actions($moduleinfo, $course) {
    global $CFG;

    // Only proceed if it's an Alpy course and our field was present
    if ($course->format !== 'alpy' || !isset($moduleinfo->local_alpy_type)) {
        return;
    }

    require_once($CFG->dirroot . '/course/format/alpy/lib.php');
    $resources = \format_alpy::get_resource_definitions();

    $cmid = $moduleinfo->coursemodule;
    $context = \context_module::instance($cmid);
    
    // 1. Get current tags
    $current_tags = \core_tag_tag::get_item_tags_array('core', 'course_modules', $cmid);
    
    // 2. Identify and remove any EXISTING Alpy tags implies we are changing type
    // We iterate through all known Alpy resource keys
    $filtered_tags = array_filter($current_tags, function($tag) use ($resources) {
        return !array_key_exists(\core_text::strtolower(trim($tag)), $resources);
    });
    
    // 3. Add the NEW Alpy tag if one was selected
    $new_tags = $filtered_tags;
    $selected_type = $moduleinfo->local_alpy_type;
    if (!empty($selected_type) && array_key_exists($selected_type, $resources)) {
        $new_tags[] = $selected_type;
    }
    
    // 4. Update the tags in the database ONLY if they changed
    // This prevents unnecessary cache clearing which can cause 'name' validation errors in course_module_updated event
    $current_tags_values = array_values($current_tags);
    $new_tags_values = array_values($new_tags);
    sort($current_tags_values);
    sort($new_tags_values);

    if ($current_tags_values !== $new_tags_values) {
        \core_tag_tag::set_item_tags('core', 'course_modules', $cmid, $context, $new_tags);
        // Force rebuild of modinfo cache ensuring subsequent event triggers have full data
        get_fast_modinfo($course, 0, true);
    }
    
    return $moduleinfo;
}

/**
 * Extend navigation to inject our dynamic CSS.
 *
 * @param global_navigation $nav
 */
function local_alpy_toolkit_extend_navigation(global_navigation $nav) {
    global $PAGE;
    
    // Inject the Dynamic CSS file
    // This file determines the user's courses and outputs CSS to replace icons
    // specifically in the Timeline and Recently Accessed Items blocks.
    if (isloggedin() && !isguestuser()) {
        // Optimal: Use file modification time.
        // This ensures clients get new CSS only when the code changes, handling cache automatically.
        $stylefile = $CFG->dirroot . '/local/alpy_toolkit/styles.php';
        $version = file_exists($stylefile) ? filemtime($stylefile) : time();
        
        $url = new moodle_url('/local/alpy_toolkit/styles.php', ['v' => $version]);
        $PAGE->requires->css($url);
    }
}
