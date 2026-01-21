<?php
/**
 * External API for local_alpy_toolkit.
 *
 * @package    local_alpy_toolkit
 * @copyright  2026 SAVIO
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_alpy_toolkit\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_multiple_structure;
use external_single_structure;

class icons extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function get_activity_icons_parameters() {
        return new external_function_parameters([
            'cmids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Course Module ID')
            )
        ]);
    }

    /**
     * Get custom icons for a list of course modules.
     *
     * @param array $cmids List of Course Module IDs
     * @return array
     */
    public static function get_activity_icons($cmids) {
        global $CFG, $DB;

        // Validation
        $params = self::validate_parameters(self::get_activity_icons_parameters(), ['cmids' => $cmids]);
        $cmids = $params['cmids'];
        $result = [];

        if (empty($cmids)) {
            return $result;
        }

        // Must have format_alpy installed
        if (!file_exists($CFG->dirroot . '/course/format/alpy/lib.php')) {
            return $result;
        }
        require_once($CFG->dirroot . '/course/format/alpy/lib.php');

        // Retrieve info about these modules: id, course, and check if course format is alpy
        list($insql, $inparams) = $DB->get_in_or_equal($cmids);
        
        // We select cm.id and c.format to filter only ALPY courses
        $sql = "SELECT cm.id, c.format 
                  FROM {course_modules} cm
                  JOIN {course} c ON cm.course = c.id
                 WHERE cm.id $insql";
                 
        $modules = $DB->get_records_sql($sql, $inparams);
        $alpy_cmids = [];

        foreach ($modules as $mod) {
            if ($mod->format === 'alpy') {
                $alpy_cmids[] = $mod->id;
            }
        }

        if (empty($alpy_cmids)) {
            return $result;
        }

        // Get resource definitions from the format
        // $resources = \format_alpy::get_resource_definitions(); // Not strictly needed if we use resolve_resource_key
        
        // Batch get tags for relevant modules
        // get_items_tags is efficient
        $tagsbyitem = \core_tag_tag::get_items_tags('core', 'course_modules', $alpy_cmids);

        foreach ($alpy_cmids as $cmid) {
            if (isset($tagsbyitem[$cmid])) {
                foreach ($tagsbyitem[$cmid] as $tag) {
                    $tagname = \core_text::strtolower(trim($tag->rawname));
                    
                    // Resolve alias (lectura -> reading)
                    $canonical = \format_alpy::resolve_resource_key($tagname);

                    if ($canonical) {
                        // Use canonical name for file lookup
                        $tagname = $canonical;
                        
                        // Check file existence (SVG/PNG)
                        $url = '';
                        $svgpath = $CFG->dirroot . '/course/format/alpy/pix/' . $tagname . '.svg';
                        $pngpath = $CFG->dirroot . '/course/format/alpy/pix/' . $tagname . '.png';
                        
                        // We use a clean moodle_url structure
                        if (file_exists($svgpath)) {
                            // Let's manually construct the URL to the pix folder of the plugin
                            $url = $CFG->wwwroot . '/course/format/alpy/pix/' . $tagname . '.svg';

                        } elseif (file_exists($pngpath)) {
                            $url = $CFG->wwwroot . '/course/format/alpy/pix/' . $tagname . '.png';
                        }

                        if ($url) {
                            $result[] = [
                                'cmid' => $cmid,
                                'iconurl' => $url,
                                'alt' => $tagname
                            ];
                            // Only one icon per activity
                            break; 
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_multiple_structure
     */
    public static function get_activity_icons_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'cmid' => new external_value(PARAM_INT, 'Course Module ID'),
                'iconurl' => new external_value(PARAM_URL, 'URL of the custom icon'),
                'alt' => new external_value(PARAM_TEXT, 'Alt text for the icon')
            ])
        );
    }
}
