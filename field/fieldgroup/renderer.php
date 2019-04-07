<?php
// This file is part of mod_datalynx for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package datalynxfield
 * @subpackage fieldgroup
 * @copyright 2018 michael pollak <moodle@michaelpollak.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once(dirname(__FILE__) . "/../renderer.php");

/**
 * Class datalynxfield_fieldgroup_renderer Renderer for fieldgroup field type
 */
class datalynxfield_fieldgroup_renderer extends datalynxfield_renderer {

    /**
     * Fields that are included in the fieldgroup. Fieldid as key.
     * @var array
     */
    protected $subfields = array();

    /**
     *
     * {@inheritDoc}
     * @see datalynxfield_renderer::render_display_mode()
     */
    public function render_display_mode(stdClass $entry, array $params) {
        global $OUTPUT; // Needed for mustache implementation.

        // We want to display these fields.
        $fieldgroupfields = $this->get_subfields();

        // Loop through maxlines.
        $maxlines = $this->_field->field->param2;

        // Add key so the other renderers know they deal with fieldgroup.
        $params['fieldgroup'] = true;

        // In case we don't have anything to show there should be an error.
        $linedispl = $completedispl = array();

        // Show all lines with content, get rid of all after that.
        $lastlinewithcontent = -1;

        for ($line = 0; $line < $maxlines; $line++) {
            foreach ($fieldgroupfields as $fieldid => $subfield) {
                $lastlinewithcontent = $this->renderer_split_content($entry, $fieldid, $line, $lastlinewithcontent);
                $subfielddefinition['name'] = $subfield->field->name;
                $subfielddefinition['content'] = $subfield->renderer()->render_display_mode($entry, $params);

                $linedispl['subfield'][] = $subfielddefinition; // Build this multidimensional array for mustache context.
            }
            $completedispl['line'][] = $linedispl;
            $linedispl = array(); // Reset.

        }

        // We need this construct to make sure intermittent empty lines are shown.
        for ($line = $lastlinewithcontent + 1; $line <= $maxlines; $line++) {
            unset($completedispl['line'][$line]);
        }

        return $OUTPUT->render_from_template('mod_datalynx/fieldgroup', $completedispl);
    }

    /**
     *
     * {@inheritDoc}
     * @see datalynxfield_renderer::render_edit_mode()
     */
    public function render_edit_mode(MoodleQuickForm &$mform, stdClass $entry, array $options) {
        // We want to display these fields.
        $fieldgroupfields = $this->get_subfields();

        // Number of lines to show and generate.
        $defaultlines = isset($this->_field->field->param3) ? $this->_field->field->param3 : 3;
        $maxlines = isset($this->_field->field->param2) ? $this->_field->field->param2 : 3;
        $requiredlines = isset($this->_field->field->param4) ? $this->_field->field->param4 : 0;

        // Add a fieldgroup marker to the entry data.
        $mform->addElement('hidden', 'fieldgroup', $this->_field->field->id);
        $mform->setType('fieldgroup', PARAM_INT);

        $mform->addElement('hidden', 'visiblelines', $defaultlines);
        $mform->setType('visiblelines', PARAM_INT);

        // Set every field in this line required.
        $options['required'] = true;

        // Show all lines with content, get rid of all after that.
        $lastlinewithcontent = -1;

        // Loop through all lines.
        for ($line = 0; $line < $maxlines; $line++) {

            // Instead of collapsing header we use simple divs.
            $mform->addElement('html', '<div class="lines" data-line="' . s($line + 1) . '">');

            // After this line none is required.
            if ($line == $requiredlines) {
                unset($options['required']);
            }

            foreach ($fieldgroupfields as $fieldid => $subfield) {
                $lastlinewithcontent = $this->renderer_split_content($entry, $fieldid, $line, $lastlinewithcontent);

                // Add a static label.
                $mform->addElement('static', '', $subfield->field->name . ': ');
                $tempentryid = $entry->id;
                $entry->id = $entry->id . "_" . $line; // Add iterator to fieldname.

                $subfield->renderer()->render_edit_mode($mform, $entry, $options);

                // Restore entryid to prior state.
                $entry->id = $tempentryid;
            }

            $mform->addElement('button', "removeline_$line", get_string('delete'), 'data-removeline="' . s($line + 1) . '"');

            // Instead of collapsing header we use simple divs.
            $mform->addElement('html', '</div>');
        }

        // In case there are extra lines, change default lines to show.
        if ($lastlinewithcontent > $defaultlines) {
            $defaultlines = $lastlinewithcontent + 1;
        }

        // Hide unused lines.
        global $PAGE;
        $PAGE->requires->js_call_amd('mod_datalynx/fieldgroups', 'init', array($this->_field->field->name, $defaultlines, $maxlines));

        // Show a button to add one more line.
        $mform->addElement('button', 'addline', get_string('addline', 'datalynx'));
    }

    /**
     * Get fields of the fielgroup as an array of fieldobjects where key = fieldid.
     *
     * @return array
     */
    public function get_subfields() {
        if (empty($this->subfields)) {
            // We want to display these fields.
            $fieldids = $this->_field->fieldids;
            foreach ($fieldids as $fieldid) {
                $field = $this->_field->df->get_field_from_id($fieldid);
                if ($field->for_use_in_fieldgroup()) {
                    $this->subfields[$fieldid] = $field;
                }
            }
        }
        return $this->subfields;
    }

    /**
     *
     * {@inheritDoc}
     * @see datalynxfield_renderer::render_search_mode()
     */
    public function render_search_mode(MoodleQuickForm &$mform, $i = 0, $value = '') {
        return false; // Remove from search.
    }

    /**
     *  We call validation of subfields
     *
     * {@inheritDoc}
     * @see datalynxfield_renderer::validate()
     */
    public function validate($entryid, $tags, $formdata) {
        return array();
    }

    /**
     * Fieldgroups should be shown in own group.
     *
     * {@inheritDoc}
     * @see datalynxfield_renderer::patterns()
     */
    protected function patterns() {
        $cat = get_string('fieldgroups', 'datalynx');
        $fieldname = $this->_field->name();

        $patterns = array();
        $patterns["[[$fieldname]]"] = array(true, $cat);

        return $patterns;
    }

    /**
     * We split the multiple contents for every line and pass only one content at a time to the subfields renderer.
     *
     * @param object $entry
     * @param number $subfieldid
     * @param number $line defines what line we want to pass here.
     * @param number $lastlinewithcontent stores where we still see content entries.
     * @return number $lastlinewithcontent the last line we want to show.
     */
    public static function renderer_split_content($entry, $subfieldid, $line, $lastlinewithcontent) {
        // Retrieve only relevant part of content and hand it over.
        // Loop through all possible contents. content, content1, ...
        for ($i = 0; $i <= 4; $i++) {
            if ($i == 0) {
                $contentid = ''; // Content1 is actually content.
            } else {
                $contentid = $i;
            }

            // If we render a fieldgroup we assume there is fieldgroup content in the $entry.
            if ( isset ( $entry->{"c{$subfieldid}_content{$contentid}_fieldgroup"}) ) {
                $tempcontent = $entry->{"c{$subfieldid}_content{$contentid}_fieldgroup"};
                $tempid = $entry->{"c{$subfieldid}_id_fieldgroup"};
            } else {
                // If we have exactly one content, show this and leave the rest blank.
                if (isset( $entry->{"c{$subfieldid}_content{$contentid}"} )) {
                    $tempcontent = array( $entry->{"c{$subfieldid}_content{$contentid}"} );
                    $tempid = array( $entry->{"c{$subfieldid}_id"} );
                } else {
                    $tempcontent = array();
                }
            }

            // Don't touch content if it is not a fieldgroup.
            if (isset($tempcontent[$line])) {
                $entry->{"c{$subfieldid}_content{$contentid}"} = $tempcontent[$line];

                // Remember that this line has some usercontent.
                if ($line > $lastlinewithcontent) {
                    $lastlinewithcontent = $line;
                }

            } else {
                $entry->{"c{$subfieldid}_content{$contentid}"} = null;
            }
            // We need to pass content ids too.
            if (isset($tempid[$line])) {
                $entry->{"c{$subfieldid}_id"} = $tempid[$line];
            } else {
                $entry->{"c{$subfieldid}_id"} = null;
            }
        }
        return $lastlinewithcontent;
    }

    /**
     * Add all subfields to tag patterns, even if not in view.
     *
     * @param array $patterns Current set of patterns as collected from the view.
     * @return array Appended field patterns with all fieldgroup patterns.
     */
    public function get_fieldgroup_patterns($patterns) {
        foreach ($this->get_subfields() as $fieldid => $subfield) {
            $fieldname = $subfield->field->name;
            $patterns[$fieldid][0] = "[[$fieldname]]";
        }
        return ($patterns);
    }
}
