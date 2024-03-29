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

require_once(t3lib_extMgm::extPath('kickstarter__mvc').'sections/class.tx_kickstarter_section_mvc_base.php');

class tx_kickstarter_section_mvc_template extends tx_kickstarter_section_mvc_base {
	var $sectionID = 'mvctemplate';

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
			$lines = $this->catHeaderLines($lines, $this->sectionID, $this->wizard->options[$this->sectionID], '<strong>Edit Template #'.$action[1].'</strong>', $action[1]);
			$piConf   = $this->wizard->wizArray[$this->sectionID][$action[1]];
			$ffPrefix = '['.$this->sectionID.']['.$action[1].']';

				// Enter title of the plugin
			$subContent='<strong>Enter a title for the template:</strong><br />'.
				$this->renderStringBox($ffPrefix.'[title]',$piConf[title]);
			$lines[]='<tr'.$this->bgCol(3).'><td>'.$this->fw($subContent).'</td></tr>';

            $lines[] = '<tr'.$this->bgCol(2).'><td>'.$this->fw('<strong>This template is for:</strong>').'</td></tr>';
			$subContent = $this->renderSelectBox($ffPrefix.'[inherit]', $piConf[inherit], $this->viewEngines).'<br />';
            $lines[] = '<tr'.$this->bgCol(3).'><td>'.$this->fw($subContent).'</td></tr>';

            $lines[] = '<tr><td><strong>Free name for template</strong></td></tr>';
            $lines[] = '<tr><td>'.$this->renderStringBox($ffPrefix.'[freename]',$piConf[freename]).'</td></tr>';

		}

		$content = '<table border=0 cellpadding=2 cellspacing=2>'.implode("\n",$lines).'</table>';
		return $content;
	}

}


// Include ux_class extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/sections/class.tx_kickstarter_section_mvc_template.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/sections/class.tx_kickstarter_section_mvc_template.php']);
}

?>
