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

require_once(t3lib_extMgm::extPath('kickstarter__mvc').'renderer/class.tx_kickstarter_classperaction_renderer.php');
require_once(t3lib_extMgm::extPath('kickstarter__mvc').'renderer/class.tx_kickstarter_methodperaction_renderer.php');

class tx_kickstarter_section_mvc extends tx_kickstarter_sectionbase {
	var $sectionID = 'mvc';
	var $pluginnr = -1;
	var $viewEngines = array('phpTemplateEngine','smartyView');
	var $renderer = array('classperaction', 'methodperaction');
	var $renderer_select = array('0'=>'Single class per Action','1'=>'Method per Action in Controller');
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
			$lines = $this->catHeaderLines($lines, $this->sectionID, $this->wizard->options[$this->sectionID], '<strong>Edit Plugin #'.$action[1].'</strong>', $action[1]);
			$piConf   = $this->wizard->wizArray[$this->sectionID][$action[1]];
			$ffPrefix = '['.$this->sectionID.']['.$action[1].']';

			if(!is_array($piConf['models']))    $piConf['models'] = array();
			if(!is_array($piConf['views']))     $piConf['views'] = array();
			if(!is_array($piConf['templates'])) $piConf['templates'] = array();


				// Enter title of the plugin
			$subContent='<strong>Enter a title for the plugin:</strong><br />'.
				$this->renderStringBox_lang('title',$ffPrefix,$piConf);
			$lines[]='<tr'.$this->bgCol(3).'><td>'.$this->fw($subContent).'</td></tr>';

			$subContent = $this->renderCheckBox($ffPrefix.'[plus_not_staticTemplate]',$piConf['plus_not_staticTemplate']).'Enable this option if you want the TypoScript code to be set by default. Otherwise the code will go into a static template file which must be included in the template record (recommended is to <em>not</em> set this option).<br />';
			$lines[]='<tr'.$this->bgCol(3).'><td>'.$this->fw($subContent).'</td></tr>';


				// Position
			if (is_array($this->wizard->wizArray['fields']))	{
				$optValues = array(
					'0' => '',
				);
				foreach($this->wizard->wizArray['fields'] as $kk => $fC)	{
					if ($fC['which_table']=='tt_content')	{
						$optValues[$kk]=($fC['title']?$fC['title']:'Item '.$kk).' ('.count($fC['fields']).' fields)';
					}
				}
				if (count($optValues)>1)	{
					$subContent='<strong>Apply a set of extended fields</strong><br />
						If you have configured a set of extra fields (Extend existing Tables) for the tt_content table, you can have them assigned to this plugin.
						<br />'.
						$this->renderSelectBox($ffPrefix.'[apply_extended]',$piConf['apply_extended'],$optValues);
					$lines[]='<tr'.$this->bgCol(3).'><td>'.$this->fw($subContent).'</td></tr>';
				}
			}
/*
				// Insert Plugin
			if (is_array($this->wizard->wizArray['tables']))	{
				$optValues = array(
					'0' => '',
				);
				foreach($this->wizard->wizArray['tables'] as $kk => $fC)	{
					$optValues[$kk]=($fC['tablename']||$fC['title']?$fC['title'].' ('.$this->returnName($this->wizard->extKey,'tables').($fC['tablename']?'_'.$fC['tablename']:'').')':'Item '.$kk).' ('.count($fC['fields']).' fields)';
				}
				$subContent='<strong>Example Code Generation</strong><br />'.
						'If you have configured custom tables you can select one of the tables to list by default as an example:<br />'.
						$this->renderSelectBox($ffPrefix.'[list_default]',$piConf['list_default'],$optValues);
				$lines[] = '<tr><td><hr /></td></tr>';
			    $lines[]='<tr'.$this->bgCol(3).'><td>'.$this->fw($subContent).'</td></tr>';
			}
*/

			$subContent='<strong>New Content Element Wizard</strong><br />'.
			    $this->renderCheckBox($ffPrefix.'[plus_wiz]',$piConf['plus_wiz']).
				'Add icon to \'New Content Element\' wizard<br />'.
				'Write a description for the entry (if any):<br />'.
				$this->renderStringBox_lang('plus_wiz_description',$ffPrefix,$piConf)
				;
			$lines[] = '<tr><td><hr /></td></tr>';
			$lines[]='<tr'.$this->bgCol(3).'><td>'.$this->fw($subContent).'</td></tr>';

			$subContent='<strong>Code Layout Selection</strong><br />'.
			    $this->renderSelectBox($ffPrefix.'[code_sel]',$piConf['code_sel'],
				$this->renderer_select);
			$lines[] = '<tr><td><hr /></td></tr>';
			$lines[]='<tr'.$this->bgCol(3).'><td>'.$this->fw($subContent).'</td></tr>';


			$lines[] = '<tr><td><hr /></td></tr>';
			$lines[] = '<tr'.$this->bgCol(3).'><td>'.$this->fw('<strong>Template Declaration</strong>').'</td></tr>';
            $c = array(0);
            if (is_array($piConf['templates']))        {
				$piConf['templates'] = $this->cleanFieldsAndDoCommands($piConf['templates'],$this->sectionID,$action[1],'templates');
                 // Do it for real...
                foreach($piConf['templates'] as $k => $v)  {
                     $c[] = $k;
                     $subContent= '&nbsp;(Remove:&nbsp;'.$this->renderCheckBox($ffPrefix.'[templates]['.$k.'][_DELETE]',0).')';
                     $lines[] = '<tr'.$this->bgCol(2).'><td>'.$this->fw('<strong>Template:</strong> <em>'.$v['title'].'</em>').$this->fw($subContent).'</td></tr>';
                }
            }

            // New template:
            $k = max($c)+1;
            $v = array();
            $lines[] = '<tr'.$this->bgCol(2).'><td>'.$this->fw('<strong>NEW Template:</strong>').'</td></tr>';
            $subContent = $this->renderStringBox($ffPrefix.'[templates]['.$k.'][title]','');
            $lines[] = '<tr'.$this->bgCol(3).'><td>'.$this->fw($subContent).'</td></tr>';



			$lines[] = '<tr><td><hr /></td></tr>';
			$lines[] = '<tr'.$this->bgCol(3).'><td>'.$this->fw('<strong>View Declaration</strong>').'</td></tr>';
            $c = array(0);
            if (is_array($piConf['views']))        {
				$piConf['views'] = $this->cleanFieldsAndDoCommands($piConf['views'],$this->sectionID,$action[1],'views');
                 // Do it for real...
                foreach($piConf['views'] as $k => $v)  {
                     $c[] = $k;
                     $subContent= '&nbsp;(Remove:&nbsp;'.$this->renderCheckBox($ffPrefix.'[views]['.$k.'][_DELETE]',0).')';
					 $subContent .= '<br />Make this view a subclass of: '.$this->renderSelectBox($ffPrefix.'[views]['.$k.'][inherit]',$piConf['views'][$k]['inherit'], $this->viewEngines).'<br />';
                     $lines[] = '<tr'.$this->bgCol(2).'><td>'.$this->fw('<strong>View:</strong> <em>'.$v['title'].'</em>').$this->fw($subContent).'</td></tr>';
                }
            }

            // New view:
            $k = max($c)+1;
            $v = array();
            $lines[] = '<tr'.$this->bgCol(2).'><td>'.$this->fw('<strong>NEW View:</strong>').'</td></tr>';
            $subContent = $this->renderStringBox($ffPrefix.'[views]['.$k.'][title]','');
			$subContent .= ' is a subclass of: '.$this->renderSelectBox($ffPrefix.'[views]['.$k.'][inherit]', 0, $this->viewEngines).'<br />';
            $lines[] = '<tr'.$this->bgCol(3).'><td>'.$this->fw($subContent).'</td></tr>';



			$lines[] = '<tr><td><hr /></td></tr>';
			$lines[] = '<tr'.$this->bgCol(3).'><td>'.$this->fw('<strong>Model Declaration</strong>').'</td></tr>';
            $c = array(0);
            if (is_array($piConf['models']))        {
				$piConf['models'] = $this->cleanFieldsAndDoCommands($piConf['models'],$this->sectionID,$action[1],'models');
                 // Do it for real...
                foreach($piConf['models'] as $k => $v)  {
                     $c[] = $k;
                     $subContent= '&nbsp;(Remove:&nbsp;'.$this->renderCheckBox($ffPrefix.'[models]['.$k.'][_DELETE]',0).')';
                     if(!empty($this->wizard->wizArray['tables'][$v['title']]['tablename']))
                        $lines[] = '<tr'.$this->bgCol(2).'><td>'.$this->fw('<strong>Model:</strong> <em>'.$this->wizard->wizArray['tables'][$v['title']]['tablename'].'</em>').$this->fw($subContent).'</td></tr>';
                     else
                        $lines[] = '<tr'.$this->bgCol(2).'><td>'.$this->fw('<strong>Model:</strong> <em>'.$v['title'].'</em>').$this->fw($subContent).'</td></tr>';
                }
            }

            // New model:
            $k = max($c)+1;
            $v = array();
            $lines[] = '<tr'.$this->bgCol(2).'><td>'.$this->fw('<strong>NEW Model:</strong>').'</td></tr>';
			$lines[] = '<tr><td>There are no tables defined for this extension.</td></tr>';
			if (is_array($this->wizard->wizArray['tables']))	{
				array_pop($lines);
				$optValues = array(
					'' => '',
				);
#				$this->pluginnr = $action[1];
				$tables = /*array_filter(*/$this->wizard->wizArray['tables']/*, array($this, 'filter_callback'))*/;
				foreach($tables as $kk => $fC)	{
					$optValues[$kk]=($fC['tablename']||$fC['title']?$fC['title'].' ('.$this->returnName($this->wizard->extKey,'tables').($fC['tablename']?'_'.$fC['tablename']:'').')':'Item '.$kk).' ('.count($fC['fields']).' fields)';
				}
				$subContent=$this->renderSelectBox($ffPrefix.'[models]['.$k.'][title]',$piConf[models]['.$k.'][title],$optValues);
                $lines[] = '<tr'.$this->bgCol(3).'><td>For table: '.$this->fw($subContent).'</td></tr>';
			}
            $lines[] = '<tr><td>Named model: '.$this->renderStringBox($ffPrefix.'[models]['.$k.'][title]','').'</td></tr>';


			$modelValues = array();
			foreach($piConf['models'] as $vv) { 
                if(!empty($this->wizard->wizArray['tables'][$vv['title']]['tablename']))
                    $modelValues[$vv[title]] = $this->wizard->wizArray['tables'][$vv['title']]['tablename'];
                else
                    $modelValues[$vv[title]] = $vv['title'];
            }
			$viewValues = array();
			foreach($piConf['views'] as $key => $vv) $viewValues[$key] = $vv[title];
			$templValues = array();
			foreach($piConf['templates'] as $key => $vv) $templValues[$key] = $vv[title];

			$lines[] = '<tr><td><hr /></td></tr>';
			$lines[] = '<tr'.$this->bgCol(3).'><td>'.$this->fw('<strong>Action Declaration</strong>').'</td></tr>';
            $c = array(0);
            if (is_array($piConf['actions']))        {
				$piConf['actions'] = $this->cleanFieldsAndDoCommands($piConf['actions'],$this->sectionID,$action[1],'actions');
                 // Do it for real...
                foreach($piConf['actions'] as $k => $v)  {
                     $c[] = $k;
                     $subContent = '&nbsp;(Remove:&nbsp;'.$this->renderCheckBox($ffPrefix.'[actions]['.$k.'][_DELETE]',0).')';
					 $subContent .= '<br />'.$this->renderCheckBox($ffPrefix.'[actions]['.$k.'][plus_user_obj]',$piConf['actions'][$k]['plus_user_obj']).'&nbsp;Actions are cached. Make it a non-cached Action instead<br />';
					 $subContent .= 'Model: '.$this->renderSelectBox($ffPrefix.'[actions]['.$k.'][model]',$v[model],$modelValues).' ';
					 $subContent .= 'View: '.$this->renderSelectBox($ffPrefix.'[actions]['.$k.'][view]',$v[view],$viewValues).' ';
					 $subContent .= 'Template: '.$this->renderSelectBox($ffPrefix.'[actions]['.$k.'][template]',$v[template],$templValues).'<br />';
                     $lines[] = '<tr'.$this->bgCol(2).'><td style="border:1px dotted gray">'.$this->fw('<strong>Action:</strong> <em>'.$v['title'].'</em>').$this->fw($subContent).'</td></tr>';
                }
            }

            // New action:
            $k = max($c)+1;
            $v = array();
            $lines[] = '<tr'.$this->bgCol(2).'><td>'.$this->fw('<strong>NEW Action:</strong>').'</td></tr>';
            $subContent  = $this->renderStringBox($ffPrefix.'[actions]['.$k.'][title]','').' ';
			$subContent .= $this->renderCheckBox($ffPrefix.'[actions]['.$k.'][plus_user_obj]', 0).'&nbsp;Make this a non-cached Action.<br />';
			$subContent .= 'Model: '.$this->renderSelectBox($ffPrefix.'[actions]['.$k.'][model]','',$modelValues).' ';
			$subContent .= 'View: '.$this->renderSelectBox($ffPrefix.'[actions]['.$k.'][view]','',$viewValues).' ';
			$subContent .= 'Template: '.$this->renderSelectBox($ffPrefix.'[actions]['.$k.'][template]','',$templValues).'<br />';
            $lines[] = '<tr'.$this->bgCol(3).'><td style="border:1px dotted black">'.$this->fw($subContent).'</td></tr>';
		}

		/* HOOK: Place a hook here, so additional output can be integrated */
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['kickstarter']['add_cat_mvc'])) {
		  foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['kickstarter']['add_cat_mvc'] as $_funcRef) {
		    $lines = t3lib_div::callUserFunction($_funcRef, $lines, $this->wizard);
		  }
		}

		$content = '<table border=0 cellpadding=2 cellspacing=2>'.implode("\n",$lines).'</table>';
		return $content;
	}

#	function filter_callback($table) {
#		return $table[tablename];
#	}

	/**
	 * Renders the extension PHP code; this was
	 *
	 * @param	string		$k: module name key
	 * @param	array		$config: module configuration
	 * @param	string		$extKey: extension key
	 * @return	void
	 */
	function render_extPart($k,$config,$extKey) {
		$WOP='[mvc]['.$k.']';
		$cN = $this->returnName($extKey,'class','');

		$this->wizard->ext_tables[]=$this->sPS('
			'.$this->WOPcomment('WOP:'.$WOP.'[addType]')."
			t3lib_div::loadTCA('tt_content');
			\$TCA['tt_content']['types']['list']['subtypes_excludelist'][\$_EXTKEY]='layout,select_key,pages,recursive';
			\$TCA['tt_content']['types']['list']['subtypes_addlist'][\$_EXTKEY]='pi_flexform".($config['apply_extended']?$this->wizard->_apply_extended_types[$config['apply_extended']]:"")."';
		");

		if(!$config[plus_not_staticTemplate])
			$this->wizard->ext_tables[] = $this->sPS('t3lib_extMgm::addStaticFile(\''.$extKey.'\', \'./configurations\', \''.$config[title].'\');');

		$this->wizard->ext_tables[]=$this->sPS("t3lib_extMgm::addPiFlexFormValue(\$_EXTKEY, 'FILE:EXT:".$extKey."/configurations/flexform.xml');");

		$this->wizard->ext_tables[]=$this->sPS('
			'.$this->WOPcomment('WOP:'.$WOP.'[addType]')."
			t3lib_extMgm::addPlugin(array('".addslashes($this->getSplitLabels_reference($config,'title','tt_content.'.'list_type_pi'.$k))."', \$_EXTKEY),'list_type');
		");

		$renderer = t3lib_div::makeInstance('tx_kickstarter_'.($this->renderer[$config[code_sel]]).'_renderer');
		$renderer->setParent($this);

		$renderer->generateSetup($extKey, $k, $config[plus_not_staticTemplate]);
		$renderer->generateConfigClass($extKey, $k);
		$renderer->generateActions($extKey, $k);
		$renderer->generateModels($extKey, $k);
		$renderer->generateViews($extKey, $k);
		$renderer->generateTemplates($extKey, $k);

		$this->addFileToFileArray(
			'configurations/flexform.xml',t3lib_div::getUrl(t3lib_extMgm::extPath('kickstarter__mvc').'template_flexform.xml')
		);

			// Add wizard?

		if ($config['plus_wiz'])	{
			$this->addLocalConf($this->wizard->ext_locallang,$config,'title','mvc',$k);
			$this->addLocalConf($this->wizard->ext_locallang,$config,'plus_wiz_description','mvc',$k);

			$indexContent = $this->sPS(
				'class '.$cN.'_wizicon {

					/**
					 * Processing the wizard items array
					 *
					 * @param	array		$wizardItems: The wizard items
					 * @return	Modified array with wizard items
					 */
					function proc($wizardItems)	{
						global $LANG;

						$LL = $this->includeLocalLang();

						$wizardItems[\'plugins_'.$cN.'\'] = array(
							\'icon\'=>t3lib_extMgm::extRelPath(\''.$extKey.'\').\'ce_wiz.gif\',
							\'title\'=>$LANG->getLLL(\'mvc'.$k.'_title\',$LL),
							\'description\'=>$LANG->getLLL(\'mvc'.$k.'_plus_wiz_description\',$LL),
							\'params\'=>\'&defVals[tt_content][CType]=list&defVals[tt_content][list_type]='.$extKey.'\'
						);

						return $wizardItems;
					}

					/**
					 * Reads the [extDir]/locallang.xml and returns the $LOCAL_LANG array found in that file.
					 *
					 * @return	The array with language labels
					 */
					function includeLocalLang()	{
						$llFile = t3lib_extMgm::extPath(\''.$extKey.'\').\'locallang.xml\';
						$LOCAL_LANG = t3lib_div::readLLXMLfile($llFile, $GLOBALS[\'LANG\']->lang);
						
						return $LOCAL_LANG;
					}
				}
			',
			0);
			
			$this->addFileToFileArray(
				'configurations/class.'.$cN.'_wizicon.php',
				$this->PHPclassFile(
					$extKey,
					'configurations/class.'.$cN.'_wizicon.php',
					$indexContent,
					'Class that adds the wizard icon.'
				)
			);

				// Add wizard icon
			$this->addFileToFileArray('ce_wiz.gif',t3lib_div::getUrl(t3lib_extMgm::extPath('kickstarter').'res/wiz.gif'));

			$this->wizard->ext_tables[]=$this->sPS('
				'.$this->WOPcomment('WOP:'.$WOP.'[plus_wiz]:').'
				if (TYPO3_MODE=="BE")	$TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["'.$cN.'_wizicon"] = t3lib_extMgm::extPath($_EXTKEY).\'configurations/class.'.$cN.'_wizicon.php\';
			');
		}
	}

    /**
     * Cleans fields and do commands
     *
     * @param       array           $fConf: current field configuration
     * @param       string          $catID: ID of current category
     * @param       string          $action: the action that should be performed
     * @return      New fieldconfiguration
     */
    function cleanFieldsAndDoCommands($fConf,$catID,$action,$key)        {
        $newFConf=array();
        $downFlag=0;
        foreach($fConf as $k=>$v)       {
            if (trim($v['title']))        {
#           $v['title'] = $this->cleanUpFieldName($v['title']);
                if (!$v['_DELETE'])
                    $newFConf[$k]=$v;
            }
        }

        $this->wizard->wizArray[$catID][$action][$key] = $newFConf;

        return $newFConf;
    }
}


// Include ux_class extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/sections/class.tx_kickstarter_section_mvc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/sections/class.tx_kickstarter_section_mvc.php']);
}

?>
