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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package datalynxfield
 * @subpackage datalynxview
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/datalynx/field/field_class.php");

class datalynxfield_datalynxview extends datalynxfield_no_content {
    public $type = 'datalynxview';
    
    public $refdatalynx = null;
    public $refview = null;
    public $reffilterid = null;
    public $localview = null;
    public $css = null;
    
    public function __construct($df = 0, $field = 0) {
        global $DB;
        
        parent::__construct($df, $field);

        // Get the datalynx
        if (empty($this->field->param1) or !$data = $DB->get_record('datalynx', array('id' => $this->field->param1))) {
            return;
        }

        $datalynx = new datalynx($data, null);
        // TODO Add capability check on view entries
        
        // Get the view
        if (empty($this->field->param2) or !$view = $datalynx->get_view_from_id($this->field->param2)) {
            return;
        }
        $this->refdatalynx = $datalynx;
        $this->refview = $view;
        $this->localview = $this->df->get_current_view();
        $this->reffilterid = $this->field->param3 ? $this->field->param3 : 0;
    }

}

