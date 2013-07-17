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

	require_once("../../../../../config.php");
	require_once($CFG->dirroot."/blocks/configurable_reports/locallib.php");

	require_login(); 
	
	error_reporting(0);
	ini_set('display_erros',false);
	 
	$id = required_param('id', PARAM_ALPHANUM);
	$reportid = required_param('reportid', PARAM_INT);
	 
	if(! $report = $DB->get_record('block_configurable_reports',array('id' => $reportid)))
		print_error('reportdoesnotexists');

	$courseid = $report->courseid;

	if (! $course = $DB->get_record("course",array( "id" =>  $courseid)) ) {
		print_error("No such course id");
	}

	// Force user login in course (SITE or Course)
	if ($course->id == SITEID)
		require_login();
	else
		require_login($course);


	if ($course->id == SITEID)
		$context = get_context_instance(CONTEXT_SYSTEM);
	else
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
		
	require_once($CFG->dirroot.'/blocks/configurable_reports/report.class.php');
	require_once($CFG->dirroot.'/blocks/configurable_reports/reports/'.$report->type.'/report.class.php');

	$reportclassname = 'report_'.$report->type;	
	$reportclass = new $reportclassname($report);

	if (!$reportclass->check_permissions($USER->id, $context)){
		print_error("No permissions");
	} 
	else{
	
		$components = cr_unserialize($report->components);
		$graphs = $components['plot']['elements'];
		
		if(!empty($graphs)){
			$series = array();
			foreach($graphs as $g){
				require_once($CFG->dirroot.'/blocks/configurable_reports/components/plot/'.$g['pluginname'].'/plugin.class.php');
				if($g['id'] == $id){
					$classname = 'plugin_'.$g['pluginname'];
					$class = new $classname($report);
					$series = $class->get_series($g['formdata']);
					break;
				}
			}

            $graph_data = array();
            foreach($series[0] as $ii => $val) {
                $label = $series[1][$ii];
                $group = $series[2][$ii];
                $graph_data[$group][$label] = $val;
            }

            $first = TRUE;
            $graph_labels = array();
            foreach($graph_data as $group => $_) {
                ksort($graph_data[$group]);
                if($first) {
                    $graph_labels = array_keys($graph_data[$group]);
                    $first = FALSE;
                }
            }

			if($g['id'] == $id){
			
				// Standard inclusions   
				include($CFG->dirroot."/blocks/configurable_reports/lib/pChart/pData.class");
				include($CFG->dirroot."/blocks/configurable_reports/lib/pChart/pChart.class");
			
                // Dataset definition 
                $DataSet = new pData;
                $DataSet->AddPoint($graph_labels,"Label");
                $DataSet->SetAbsciseLabelSerie("Label");

                foreach($graph_data as $group => $values) {
                    $DataSet->AddPoint(array_values($values), $group);
                    $DataSet->AddSerie($group);
                    $DataSet->SetSerieName($group,$group);                    
                }

                // Initialise the graph
                $Test = new pChart(400,400);
				$Test->setFontProperties($CFG->dirroot."/blocks/configurable_reports/lib/Fonts/tahoma.ttf",8);


                $Test->setGraphArea(30,30,370,370);



                // Draw the radar graph
                $Test->drawRadarAxis($DataSet->GetData(),$DataSet->GetDataDescription(),FALSE,
                                     20, //offset
                                     120,120,120, //couleur
                                     230,230,230); //couleur
                $Test->drawFilledRadar($DataSet->GetData(),$DataSet->GetDataDescription(),50,20);

                // Finish the graph
                $Test->drawLegend(15,15,$DataSet->GetDataDescription(),255,255,255);
                //$Test->drawTitle(0,22,"Example 8",50,50,50,400);
				$Test->Stroke();
			}
		}
	}
