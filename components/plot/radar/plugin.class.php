<?php

// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/** Configurable Reports
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_radar extends plugin_base{
	
	function init(){
		$this->fullname = get_string('pie','block_configurable_reports');		
		$this->form = true;
		$this->ordering = true;
		$this->reporttypes = array('courses','sql','users','timeline', 'categories');		
	}
	
	function summary($data){
		return get_string('piesummary','block_configurable_reports');
	}
	
	// data -> Plugin configuration data
	function execute($id, $data, $finalreport){
		global $DB, $CFG;

		$series = array();
		if($finalreport){
			foreach($finalreport as $r){

                $series[0][] = $r[$data->areavalue];
                $series[1][] = $r[$data->areaname];
                $series[2][] = $r[$data->areaserie];
                
			}
		}
		
		$serie0 = base64_encode(implode('|',$series[0]));
		$serie1 = base64_encode(implode('|',$series[1]));
		$serie2 = base64_encode(implode('|',$series[2]));
		
		return $CFG->wwwroot.'/blocks/configurable_reports/components/plot/radar/graph.php?reportid='.$this->report->id.'&id='.$id.'&serie0='.$serie0.'&serie1='.$serie1.'&serie2='.$serie2;
	}
	
	function get_series($data){
		$serie0 = required_param('serie0',PARAM_BASE64);
		$serie1 = required_param('serie1',PARAM_BASE64);
		$serie2 = required_param('serie2',PARAM_BASE64);
						
		return array(explode('|',base64_decode($serie0)), 
                     explode('|',base64_decode($serie1)),
                     explode('|',base64_decode($serie2)));
	}
	
}

