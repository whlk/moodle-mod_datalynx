<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package datalynxfield
 * @subpackage tag
 * @copyright 2016 David Bogner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once (dirname(__FILE__) . "/../renderer.php");


/**
 * Class datalynxfield_tag_renderer Renderer for tag field type
 */
class datalynxfield_tag_renderer extends datalynxfield_renderer {

    /**
     * 
     * {@inheritDoc}
     * @see datalynxfield_renderer::render_edit_mode()
     */
    public function render_edit_mode(MoodleQuickForm &$mform, stdClass $entry, array $options) {
        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;
        
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        $content = core_tag_tag::get_item_tags_array('mod_datalynx', 'datalynx_contents', $contentid, core_tag_tag::BOTH_STANDARD_AND_NOT);
        
        $fieldname = "field_{$fieldid}_{$entryid}";
        $mform->addElement('tags', $fieldname, get_string('tags'), array('itemtype' => 'datalynx_contents', 'component' => 'mod_datalynx'));
        $mform->setDefault($fieldname, $content);
        $required = !empty($options['required']);
        if ($required) {
            $mform->addRule($fieldname, null, 'required', null, 'client');
        }
        
    }

    /**
     * 
     * {@inheritDoc}
     * @see datalynxfield_renderer::render_display_mode()
     */
    public function render_display_mode(stdClass $entry, array $params) {
        global $OUTPUT;
        $str = '';
        $field = $this->_field;
        $fieldid = $field->id();
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        $items = core_tag_tag::get_item_tags('mod_datalynx', 'datalynx_contents', $contentid);
        $str = $OUTPUT->tag_list($items , null, 'datalynx-tags');
        if (isset($params['nolink'])) {
            $str = preg_replace("/<b>.+<\/b>/i", '', $str);
            $str = preg_replace("/<a[^>]*(href=\"[^\"]+?\")([^>]*?)(\/?)>([^<]+)(<\/a>)/i",'<span$2>$4</span>', $str);
        }
        return $str;
    }
    
    /**
     * Array of patterns this field supports
     */
    protected function patterns() {
        $fieldname = $this->_field->name();
        $patterns = parent::patterns();
        $patterns["[[$fieldname]]"] = array(true);
        // tag without link
        $patterns["[[$fieldname:nolink]]"] = array(true);
        return $patterns;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see datalynxfield_renderer::render_search_mode()
     */
    public function render_search_mode(MoodleQuickForm &$mform, $i = 0, $value = '') {
        global $CFG;
    
        $field = $this->_field;
        $fieldid = $field->id();
    
        $selected = $value;
    
        $options = $field->options_menu();
    
        $fieldname = "f_{$i}_$fieldid";
        $select = &$mform->createElement('tags', $fieldname, get_string('tags'), array('itemtype' => 'datalynx_contents', 'component' => 'mod_datalynx'));
        $select->setValue($selected);
    
        $mform->disabledIf($fieldname, "searchoperator$i", 'eq', '');
    
        return array(array($select), null);
    }

    /**
     * 
     * {@inheritDoc}
     * @see datalynxfield_renderer::validate()
     */
    public function validate($entryid, $tags, $formdata) {
        $fieldid = $this->_field->id();
        
        $formfieldname = "field_{$fieldid}_{$entryid}";
        
        $errors = array();
        foreach ($tags as $tag) {
            list(, $behavior, ) = $this->process_tag($tag);
            /* @var $behavior datalynx_field_behavior */
            if ($behavior->is_required() and isset($formdata->$formfieldname)) {
                if (!clean_param($formdata->$formfieldname, PARAM_NOTAGS)) {
                    $errors[$formfieldname] = get_string('fieldrequired', 'datalynx');
                }
            }
        }
        
        return $errors;
    }
}
