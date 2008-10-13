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

require_once(t3lib_extMgm::extPath('kickstarter').'class.tx_kickstarter_sectionbase.php');
require_once(t3lib_extMgm::extPath('kickstarter__mvc_ex').'dbinfos.php');

class tx_kickstarter_section_mvc_base extends tx_kickstarter_sectionbase {
	var $pluginnr = -1;
	var $viewEngines = array('phpTemplateEngine','smartyView');
	var $renderer = array('simple', 'switched');
	var $renderer_select = array('0'=>'Simple Controllers', '1'=>'Switched Controllers');
	var $ajaxActions = array();

	function retreiveTableInfos($tableid) {
		if (is_numeric($tableid))
			if (is_array($this->wizard->wizArray['tables']))
				return $this->wizard->wizArray['tables'][$tableid];
			else
				return null;

		/* reverse-engineer the table if it's in the database */
		return $GLOBALS['KSRE'][$tableid];
	}

	/**
	 * renders a multi-select box
	 *
	 * @param	string		field prefix
	 * @param	string		the value of the preselected option
	 * @param	array		array of string values for the options
	 * @return	string		the complete select box
	 */
	function renderMultiSelectBox($prefix,$values,$optValues,$size=8)	{
		$onCP = $this->getOnChangeParts($prefix);
		$opt=array();
		foreach($optValues as $k=>$v)	{
			if (is_array($values))
				$sel = (in_array($k,$values)?' selected="selected"':'');
			$opt[]='<option value="'.htmlspecialchars($k).'"'.$sel.'>'.htmlspecialchars($v).'</option>';
		}
		return $this->wopText($prefix).$onCP[0].'<select multiple="multiple" size="'.$size.'" name="'.$this->piFieldName("wizArray_upd").$prefix.'" onchange="'.$onCP[1].'"'.$this->wop($prefix).'>'.implode('',$opt).'</select>';
	}

}

?>
