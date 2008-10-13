<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2007 Christian Welzel (gawain@camlann.de)  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * @author  Christian Welzel <gawain@camlann.de>
 */

require_once(t3lib_extMgm::extPath('kickstarter__mvc_ex').'sections/class.tx_kickstarter_section_mvc_base.php');

class tx_kickstarter_section_mvc_action extends tx_kickstarter_section_mvc_base {
	var $sectionID = 'mvcaction';

	/**
	 * Renders the form in the kickstarter; this was add_cat_pi()
	 *
	 * @return	HTML
	 */
	function render_wizard() {
		$lines=array();

		$action = explode(':',$this->wizard->modData['wizAction']);
		if ($action[0]=='edit')	{
			$this->regNewEntry($this->sectionID, $action[1]);
			$lines = $this->catHeaderLines($lines, $this->sectionID, $this->wizard->options[$this->sectionID], '<strong>Edit Action #'.$action[1].'</strong>', $action[1]);
			$piConf   = $this->wizard->wizArray[$this->sectionID][$action[1]];
			$ffPrefix = '['.$this->sectionID.']['.$action[1].']';

				// Enter title of the action
			$subContent='<strong>Enter a title for the action:</strong><br />'.
				$this->renderStringBox($ffPrefix.'[title]',$piConf['title']);
			$lines[]='<tr'.$this->bgCol(3).'><td>'.$this->fw($subContent).'</td></tr>';

            $subContent='<strong>Enter a short description for the action:</strong><br />'.
                $this->renderTextareaBox($ffPrefix.'[description]',$piConf['description']);
            $lines[]='<tr'.$this->bgCol(3).'><td>'.$this->fw($subContent).'</td></tr>';

            $controllerValues = array();
            $modelValues = array(
            	''			=> '- (not DB-driven/state-change free)'
            );
            $viewValues = array(
            	''			=> '- (has no visual reflection)'
            );
            $templValues = array(
            	''			=> '- (has no visual reflection)'
            );
            $presetValues = array(
 		''			=> '-',				// no preset (empty)
		'render'		=> 'List', 			// list + show controls
 		'list'			=> 'List (with controls)',	// list + create/edit/remove controls
		'maintainlisted'	=> 'List (with bulk-controls)', // bulk   create/edit controls
		'tree'			=> 'Tree', 			// tree + show controls
 		'hierarchy'		=> 'Tree (with controls)',	// tree + create/edit/remove controls
		'search'		=> 'Results (with highlights)', // list + show controls + hints
                'cloud' 	  	=> 'Cloud',			// cloud
		'maintain'		=> 'Edit', 			//        create/edit controls
		'show'			=> 'Show', 			//        show controls
		'trigger'		=> 'Trigger model',		// trigger the model
		'display'		=> 'Render template',		// pass-through template, nothing else, no model
            );

            if(is_array($this->wizard->wizArray['mvccontroller']))
                foreach($this->wizard->wizArray['mvccontroller'] as $key => $vv) $controllerValues[$key] = $vv[title];
            if(is_array($this->wizard->wizArray['mvcmodel'     ]))
                foreach($this->wizard->wizArray['mvcmodel'     ] as $key => $vv) $modelValues[$key] = $vv[title];
            if(is_array($this->wizard->wizArray['mvcview'      ]))
                foreach($this->wizard->wizArray['mvcview'      ] as $key => $vv) $viewValues[$key] = $vv[title];
            if(is_array($this->wizard->wizArray['mvctemplate'  ]))
                foreach($this->wizard->wizArray['mvctemplate'  ] as $key => $vv) $templValues[$key] = $vv[title];

            $lines[] = '<tr><td><strong>This action belongs to contoller</strong></td></tr>';
			$lines[] = '<tr><td>'.$this->renderSelectBox($ffPrefix.'[controller]',$piConf[controller],$controllerValues).'</td></tr>';

            $lines[] = '<tr><td><strong>This is the default action for the above controller on</strong></td></tr>';
			$lines[] = '<tr><td>'.$this->renderCheckBox($ffPrefix.'[defaction]',$piConf[defaction]).'</td></tr>';

            $lines[] = '<tr><td><strong>Make this action callable through AJAX.</strong></td></tr>';
			$lines[] = '<tr><td>'.$this->renderCheckBox($ffPrefix.'[plus_ajax]', $piConf[plus_ajax]).'</td></tr>';

            $lines[] = '<tr><td><strong>Model for this action to operate on</strong></td></tr>';
			$lines[] = '<tr><td>'.$this->renderSelectBox($ffPrefix.'[model]',$piConf[model],$modelValues).'</td></tr>';

            $lines[] = '<tr><td><strong>View for this action</strong></td></tr>';
			$lines[] = '<tr><td>'.$this->renderSelectBox($ffPrefix.'[view]',$piConf[view],$viewValues).'</td></tr>';

            $lines[] = '<tr><td><strong>Template for this action</strong></td></tr>';
			$lines[] = '<tr><td>'.$this->renderSelectBox($ffPrefix.'[template]',$piConf[template],$templValues).'</td></tr>';

            $lines[] = '<tr><td><strong>Free name for action class</strong></td></tr>';
			$lines[] = '<tr><td>'.$this->renderStringBox($ffPrefix.'[freename]',$piConf[freename]).'</td></tr>';

            $lines[] = '<tr><td><strong>Preset for this action\'s function-body</strong></td></tr>';
			$lines[] = '<tr><td>'.$this->renderSelectBox($ffPrefix.'[preset]',$piConf[preset],$presetValues).'</td></tr>';
		}

		$content = '<table border=0 cellpadding=2 cellspacing=2>'.implode("\n",$lines).'</table>';
		return $content;
	}

}


// Include ux_class extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc_ex/sections/class.tx_kickstarter_section_mvc_action.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc_ex/sections/class.tx_kickstarter_section_mvc_action.php']);
}

?>
