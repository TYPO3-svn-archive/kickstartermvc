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

class tx_kickstarter_section_mvc extends tx_kickstarter_sectionbase {
	var $sectionID = 'mvc';
	var $pluginnr = -1;
	var $viewEngines = array('phpTemplateEngine','smartyView');

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
                     $lines[] = '<tr'.$this->bgCol(2).'><td>'.$this->fw('<strong>Model:</strong> <em>'.$this->wizard->wizArray['tables'][$v['title']]['tablename'].'</em>').$this->fw($subContent).'</td></tr>';
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


			$modelValues = array();
			foreach($piConf['models'] as $vv) $modelValues[$vv[title]] = $this->wizard->wizArray['tables'][$vv['title']]['tablename'];
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

		$this->generateSetup($extKey, $k, $config[plus_not_staticTemplate]);
		$this->generateConfigClass($extKey, $k);
		$this->generateActions($extKey, $k);
		$this->generateModels($extKey, $k);
		$this->generateViews($extKey, $k);
		$this->generateTemplates($extKey, $k);

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

    /**
     * Generates the setup.txt
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
	 * @param       bool             $static: include template as static template
     */
	function generateSetup($extKey, $k, $static) {
		$lines = array();
		$incls = array();
		$acts  = array();

		$cN = $this->returnName($extKey,'class','');
        $actions = $this->wizard->wizArray[$this->sectionID][$k]['actions'];

		$lines[] = '
# Common configuration
plugin.'.$cN.'.configurations {
  templatePath = EXT:'.$extKey.'/templates/
}

includeLibs.tx_div = EXT:div/class.tx_div.php
includeLibs.tx_lib_switch = EXT:lib/class.tx_lib_switch.php';

		foreach($actions as $action) {
			if(!trim($action['title'])) continue;
			$acts[] = '    '.$action['title'].' = '.($action[plus_user_obj]?'USER_INT':'USER').'
    '.$action['title'].' {
       userFunc = '.$cN.'_controller_'.$action[title].'->main
       setupPath = plugin.'.$cN.'.configurations.
    }';
			$incls[] = 'includeLibs.'.$cN.'_controller_'.$action[title].' = '.
				'EXT:'.$extKey.'/controllers/class.'.$cN.'_controller_'.$action[title].'.php';
		}
		$lines = array_merge($lines, $incls);

		$lines[] = '
# The controller switch
plugin.'.$cN.'.controllerSwitch = USER
plugin.'.$cN.'.controllerSwitch {
    userFunc = tx_lib_switch->main
';

		$lines = array_merge($lines, $acts);
		$lines[] = '}
tt_content.list.20.tx_'.$extKey.' =< plugin.'.$cN.'.controllerSwitch';

		if(!$static)
			$this->addFileToFileArray('configurations/setup.txt', implode("\n", $lines));
		else
			$this->addFileToFileArray('ext_typoscript_setup.txt', implode("\n", $lines));
	}

    /**
     * Generates the class.tx_*_configuration.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateConfigClass($extKey, $k) {

		$cN = $this->returnName($extKey,'class','');

		$indexContent = '
tx_div::load(\'tx_lib_configurations\');

class '.$cN.'_configurations extends tx_lib_configurations {
        var $setupPath = \'plugin.'.$cN.'.configurations.\';
}';

		$this->addFileToFileArray('configurations/class.'.$cN.'_configuration.php', 
			$this->PHPclassFile(
					$extKey,
					'configurations/class.'.$cN.'_configuration.php',
					$indexContent,
					'Class that handles TypoScript configuration.'
			)
		);
	}

    /**
     * Generates the class.tx_*_controller_*.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateActions($extKey, $k) {

		$cN = $this->returnName($extKey,'class','');

        $actions = $this->wizard->wizArray[$this->sectionID][$k]['actions'];
		foreach($actions as $action) {
			if(!trim($action[title])) continue;

			$tablename = $this->wizard->wizArray['tables'][$action['model']]['tablename'];
			$model = $cN.'_model_'.$tablename;
			$views = $this->wizard->wizArray[$this->sectionID][$k]['views'];
			$view  = $cN.'_views_'.$views[$action[view]][title];
			$templates = $this->wizard->wizArray[$this->sectionID][$k]['templates'];
			$template  = $templates[$action[template]][title].'.php';

			$indexContent = '
tx_div::load(\'tx_lib_controller\');

class '.$cN.'_controller_'.$action[title].' extends tx_lib_controller {

		function main() {
                $model = tx_div::makeInstance(\''.$model.'\');
                $model->setConfigurations($this->configurations);
                $model->load($this->parameters);
                $resultList = $model->get(\'resultList\');
                $view = tx_div::makeInstance(\''.$view.'\');
                $view->set(\'entryList\', $resultList);
                $view->setController($this);
                $view->setTemplatePath($this->configurations->get(\'templatePath\'));
                return $view->render($this->configurations->get(\''.$template.'\'));
		}
}';

			$this->addFileToFileArray('controllers/class.'.$cN.'_controller_'.$action['title'].'.php', 
				$this->PHPclassFile(
					$extKey,
					'controllers/class.'.$cN.'_controller_'.$action['title'].'.php',
					$indexContent,
					'Class that implements the controller for action '.$action['title'].'.'
				)
			);
		}
	}

    /**
     * Generates the class.tx_*_model_*.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateModels($extKey, $k) {

		$cN = $this->returnName($extKey,'class','');

        $models = $this->wizard->wizArray[$this->sectionID][$k]['models'];
		if(!is_array($models)) return;

		foreach($models as $model) {
			$tablename = $this->wizard->wizArray['tables'][$model['title']]['tablename'];
			if(!trim($tablename)) continue;
			$real_tableName = $this->returnName($extKey,'tables',$tablename);

			$indexContent = '
class '.$cN.'_model_'.$tablename.' extends tx_lib_object {
        var $configurations;

        function load($parameters = null) {

                // fix settings
                $fields = \'*\';
                $tables = \''.$real_tableName.'s\';
                $groupBy = null;
                $orderBy = \'sorting\';
                $where = \'hidden = 0 AND deleted = 0 \';

                // variable settings
                if($parameters) {
					// do query modifications according to incoming parameters here.
                }

                // query
                $query = $GLOBALS[\'TYPO3_DB\']->SELECTquery($fields, $tables, $where, $groupBy, $orderBy);
                $result = $GLOBALS[\'TYPO3_DB\']->sql_query($query);
                $list = tx_div::makeInstance(\'tx_lib_object\');
                if($result) {
                        while($row = $GLOBALS[\'TYPO3_DB\']->sql_fetch_assoc($result)) {
                                $entry = new tx_lib_object($row);
                                $list->append($entry);
                        }
                }
                $this->set(\'resultList\', $list);
        }

        function setConfigurations($configurations) {
                $this->configurations = $configurations;
        }
}
';
			$this->addFileToFileArray('models/class.'.$cN.'_model_'.$tablename.'.php', 
				$this->PHPclassFile(
					$extKey,
					'models/class.'.$cN.'_model_'.$tablename.'.php',
					$indexContent,
					'Class that implements the model for table '.$real_tableName.'.'
				)
			);
		}
	}

    /**
     * Generates the class.tx_*_view_*.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateViews($extKey, $k) {

		$cN = $this->returnName($extKey,'class','');

        $views = $this->wizard->wizArray[$this->sectionID][$k]['views'];
		foreach($views as $view) {
			if(!trim($view[title])) continue;

			$indexContent = '
tx_div::load(\'tx_lib_'.$this->viewEngines[$view['inherit']].'\');

class '.$cN.'_views_'.$view[title].' extends tx_lib_'.$this->viewEngines[$view['inherit']].' {
}';

			$this->addFileToFileArray('views/class.'.$cN.'_view_'.$view['title'].'.php', 
				$this->PHPclassFile(
					$extKey,
					'views/class.'.$cN.'_view_'.$view['title'].'.php',
					$indexContent,
					'Class that implements the view for '.$view['title'].'.'
				)
			);
		}
	}

    /**
     * Generates the class.tx_*_template_*.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateTemplates($extKey, $k) {

		$cN = $this->returnName($extKey,'class','');

        $templates = $this->wizard->wizArray[$this->sectionID][$k]['templates'];
		foreach($templates as $template) {
			if(!trim($template[title])) continue;

			$indexContent = '
<?php $entryList = $this->get(\'entryList\'); ?>
<?php if($entryList->isNotEmpty()): ?>
        <ol>
<?php endif; ?>
<?php for($entryList->rewind(); $entryList->valid(); $entryList->next()): $entry = $entryList->current();       ?>
        <li>
			<h3>Insert HTML/Code to display elements here</h3>
        </li>
<?php endfor; ?>
<?php if($entryList->isNotEmpty()): ?>
        </ol>
<?php endif; ?>
';

			$this->addFileToFileArray('templates/'.$template['title'].'.php', $indexContent);
		}
	}

}


// Include ux_class extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/sections/class.tx_kickstarter_section_mvc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/sections/class.tx_kickstarter_section_mvc.php']);
}

?>
