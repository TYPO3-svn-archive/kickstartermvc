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

class tx_kickstarter_section_mvc_diagram extends tx_kickstarter_section_mvc_base {

	/* -------------------------------------------------------------------------------------- */
	function widen_model_fields($model) { if (!$model) return '';
		$content = '';
		$table = $this->retreiveTableInfos($model['table']);

		if ($table) {
			/* assemble a list of fields this model covers */
			$fnames = array();
			$fdepes = array();
			$farrys = array();
			if (count($table['fields']))
				foreach ($table['fields'] as $key => $field) {
					$fnames[] = $field['fieldname'];
					if (($field['type'] == 'rel') &&
					    ($field['conf_rel_type'] == 'select_cur') &&
					    ($field['conf_relations'] == 1) &&
					    ($field['conf_relations_selsize'] == 1))
					$fdepes[] = $field['fieldname'] . '&mdash;&gt;' . ($field['conf_rel_table'] != '_CUSTOM' ? $field['conf_rel_table'] : $field['conf_custom_table_name']);
					if (($field['type'] == 'rel') &&
					    ($field['conf_rel_type'] == 'select_storage') &&
					    ($field['conf_relations'] > 1) &&
					    ($field['conf_relations_selsize'] > 1))
					$farrys[] = $field['fieldname'] . '&mdash;&gt;' . ($field['conf_rel_table'] != '_CUSTOM' ? $field['conf_rel_table'] : $field['conf_custom_table_name']);
				}

			$content .= '
	<dl style="margin-bottom: 0;">
		<dt style="font-weight: bold; white-space: nowrap;">Mapped fields</dt>
			<dd>'.implode(', ', $fnames).'</dd>
'    .(count($fdepes) ? '
		<dt style="font-weight: bold; white-space: nowrap;">Mapped dependencies (1:1)</dt>
			<dd>'.implode(', ', $fdepes).'</dd>
':'').(count($farrys) ? '
		<dt style="font-weight: bold; white-space: nowrap;">Mapped relations (1:N)</dt>
			<dd>'.implode(', ', $farrys).'</dd>
':'');
			/* presets are specific collections of fields bundled under a topic */
			if ($model['plus_seg'] && ($model['segmentnum'] > 0)) {
				$content .= '
		<dt style="font-weight: bold; white-space: nowrap;">Segments</dt>
			<dd><dl>
';
				$presets = array();
				for ($sp = 0; $sp < $model['segmentnum']; $sp++) {
					$content .= '
				<dt style="font-weight: bold; white-space: nowrap;">'.$model['segmentnames'][$sp].'</dt>
					<dd>'.implode(', ', $model['segment'][$sp]).'</dd>
';
				}
				$content .= '
			</dl></dd>
';
			}

			$content .= '
	</dl>
';
		}

		return $content;
	}

	function widen_action_follow_ups($action, $template) { if (!$action || !$template) return '';
		$content = '';
		$ctrls = $this->wizard->wizArray['mvccontroller'];
		$actns = $this->wizard->wizArray['mvcaction'];
		$tmpls = $this->wizard->wizArray['mvctemplate'];
		$actna = array();
		$tmpla = array();

		$cid = $action[controller];
		foreach ($actns as $key => $struct) {
			if ($struct[controller] == $cid) {
				$actna[       $struct           [freename]] =        $struct           ;
				$tmpla[$tmpls[$struct[template]][freename]] = $tmpls[$struct[template]];
			}
		}

		switch ($template[fill_type]) {
			case 'show':
				$content .= '<span style="'.(!$actna['render'   ]                        ? 'text-decoration: line-through; color: red;' : ''             ).'">return to list</span><br />';
				$content .= '<span style="color: green;">remember entry</span>';
				break;
			case 'render':
				$content .= '<span style="'.(!$actna['render'   ]                        ? 'text-decoration: line-through; color: red;' : ''             ).'">reload list</span><br />';
				$content .= '<span style="'.(!$actna['show'     ]                        ? 'text-decoration: line-through; color: red;' : ''             ).'">show entry</span><br />';
				$content .= '<span style="color: green;">forget entry</span>';
				break;
			case 'tree':
				$content .= '<span style="'.(!$actna['tree'     ]                        ? 'text-decoration: line-through; color: red;' : ''             ).'">reload tree</span><br />';
				$content .= '<span style="'.(!$actna['show'     ]                        ? 'text-decoration: line-through; color: red;' : ''             ).'">show entry</span><br />';
				$content .= '<span style="color: green;">forget entry</span>';
				break;
			case 'renderedshow':
				$content .= '<span style="color: blue;">dead end</span>';
				break;
			case 'form':
				$content .= '<span style="'.(!$actna['list'     ]                        ? 'text-decoration: line-through; color: red;' : ''             ).'">return to list</span><br />';
				$content .= '<span style="'.(!$actna['list'     ] || !$actna['maintain'] ? 'text-decoration: line-through; color: red;' : 'color: green;').'">create/edit entry</span><br />';
				$content .= '<span style="color: green;">remember entry</span>';
				break;
			case 'list':
				$content .= '<span style="'.(!$actna['list'     ]                        ? 'text-decoration: line-through; color: red;' : ''             ).'">return to list</span><br />';
				$content .= '<span style="'.(!$actna['maintain' ]                        ? 'text-decoration: line-through; color: red;' : ''             ).'">maintain entry</span><br />';
				$content .= '<span style="'.(!$actna['list'     ]                        ? 'text-decoration: line-through; color: red;' : 'color: green;').'">apply changes (hide/remove/delete)</span><br />';
				$content .= '<span style="color: green;">forget entry</span>';
				break;
			case 'hierarchy':
				$content .= '<span style="'.(!$actna['hierarchy']                        ? 'text-decoration: line-through; color: red;' : ''             ).'">return to tree</span><br />';
				$content .= '<span style="'.(!$actna['maintain' ]                        ? 'text-decoration: line-through; color: red;' : ''             ).'">maintain entry</span><br />';
				$content .= '<span style="'.(!$actna['list'     ]                        ? 'text-decoration: line-through; color: red;' : 'color: green;').'">apply changes (hide/remove/delete)</span><br />';
				$content .= '<span style="color: green;">forget entry</span>';
				break;
			case 'listedform':
				$content .= '<span style="'.(!$actna['maintainlisted']                  ? 'text-decoration: line-through; color: red;' : ''             ).'">sync changes (create/edit/hide/remove/delete/undo)</span><br />';
				break;
			default:
				$content .= '<span style="color: blue;">dead end</span>';
				break;
		}

		return $content;
	}

	function widen_plugin_pids($pid, $plugin) { if (!$plugin) return '';
		$content = '';
		$ctrls = $this->wizard->wizArray['mvccontroller'];
		$actns = $this->wizard->wizArray['mvcaction'];
		$tmpls = $this->wizard->wizArray['mvctemplate'];
		$PIDs = array();

		foreach($ctrls as $cid => $ctrl) {
			if($ctrl['plugin'] != $pid)
				continue;
		foreach($actns as $aid => $actn) {
			if($actn['controller'] != $cid)
				continue;

			if (in_array($actn[freename], array('list','tree','hierarchy','render','maintain','show')))
				$PIDs[] = $actn[freename] . 'PID';
		}
		}

		$content .= '
	<dl style="margin-bottom: 0;">
		<dt style="font-weight: bold; white-space: nowrap;">PIDs</dt>
			<dd>'.implode(', ', $PIDs).'</dd>
	</dl>
';
		return $content;
	}

	/* -------------------------------------------------------------------------------------- */
	function widen_template($tid, $template) { if (!$template) return '';
		$content = '';
		$modls = $this->wizard->wizArray['mvcmodel'];
		$tabls = $this->wizard->wizArray['tables'];

                $modelValues = array(
 		        ''		=> '-',			// no preset (empty)
                );
                $typeValues = array(
 		        ''		=> '-',			// no preset (empty)

                	'cloud'   	=> 'Cloud',		// -> cloud, render

                	'show'		=> 'SingleView',	// -> render
                	'render'   	=> 'ListView',		// -> render, show
                	'tree'   	=> 'TreeView',		// -> tree, show
                	'renderedshow'  => 'InlineListView',

                	'form'		=> 'SingleEdit',	// -> list, [create, edit]
                	'list'   	=> 'ListEdit',		// -> list, maintain, [hide, remove]
                	'hierarchy'   	=> 'TreeEdit',		// -> tree, maintain, [hide, remove]
                	'listedform'	=> 'InlineListEdit',	// -> [sync]

                	'select'	=> 'SelectChoice',
                	'radio'		=> 'RadioChoice',
                	'checkbox'	=> 'CheckboxChoice',
                );

                if (is_array($modls))
                    foreach ($modls as $key => $vv) {
                        $modelValues[$vv[freename]] = $vv[title] . ' (' . $vv[freename] . ')';
                    }

		$content .=
			str_replace('<select ', '<select style="width: 10em;" ', $this->renderSelectBox('[mvctemplate]['.$tid.'][fill_type]',$template[fill_type],$typeValues)).
			str_replace('<select ', '<select style="width: 10em;" ', $this->renderSelectBox('[mvctemplate]['.$tid.'][fill_model]',$template[fill_model],$modelValues));

		return $content;
	}

	function widen_view($vid, $view) { if (!$view) return '';
		$content = '';

		return $content;
	}

	function widen_model($mid, $model) { if (!$model) return '';
		$content = '';

		return $content;
	}

	/* -------------------------------------------------------------------------------------- */
	function widen_action($aid, $action) { if (!$action) return '';
		$content = '';
		$modls = $this->wizard->wizArray['mvcmodel'];
		$views = $this->wizard->wizArray['mvcview'];
		$tmpls = $this->wizard->wizArray['mvctemplate'];

                $presetValues = array(
 		    ''			=> '-',				// no preset (empty)
		    'render'		=> 'List', 			// list + show controls
 		    'list'		=> 'List (with controls)',	// list + create/edit/remove controls
		    'maintainlisted'	=> 'List (with bulk-controls)', // bulk   create/edit controls
		    'tree'		=> 'Tree', 			// tree + show controls
 		    'hierarchy'		=> 'Tree (with controls)',	// tree + create/edit/remove controls
		    'search'		=> 'Results (with highlights)', // list + show controls + hints
                    'cloud' 	  	=> 'Cloud',			// cloud
		    'maintain'		=> 'Edit', 			//        create/edit controls
		    'show'		=> 'Show', 			//        show controls
		    'trigger'		=> 'Trigger model',		// trigger the model
		    'display'		=> 'Render template',		// pass-through template, nothing else, no model
                );
                $templValues = array(
                    ''			=> '- (has no visual reflection)'
                );
                $viewValues = array(
                    ''			=> '- (has no visual reflection)'
                );
                $modelValues = array(
                    ''			=> '- (not DB-driven/state-change free)'
                );

                if (is_array($tmpls))
                    foreach($tmpls as $key => $vv) $templValues[$key] = $vv[title];
                if (is_array($views))
                    foreach($views as $key => $vv) $viewValues[$key] = $vv[title];
                if (is_array($modls))
                    foreach($modls as $key => $vv) $modelValues[$key] = $vv[title];

		$header = $action[title] . '<br /><span style="font-size: 0.8em;">(' . $action[freename] . ')</span>';
		$content .= '
<div style="padding: 5px; border: 1px solid black; background: #F7F7F7;">
	<h1 style="margin-top: 0;">'.$header.'</h1>
';
		$content .= '
	<dl style="margin-bottom: 0;">
		<dt style="font-weight: bold; white-space: nowrap;">Function-Body Preset</dt>
			<dd>'.str_replace('<select ', '<select style="width: 20em;" ', $this->renderSelectBox('[mvcaction]['.$aid.'][preset]',$action[preset],$presetValues)).'</dd>
		<dt style="font-weight: bold; white-space: nowrap;">Model</dt>
			<dd>'.str_replace('<select ', '<select style="width: 20em;" ', $this->renderSelectBox('[mvcaction]['.$aid.'][model]',$action[model],$modelValues)) . '<br />' .
			      $this->widen_model($action[model], $modls[$action[model]]).'</dd>
'.($action[preset]!='trigger'?'
		<dt style="font-weight: bold; white-space: nowrap;">View</dt>
			<dd>'.str_replace('<select ', '<select style="width: 20em;" ', $this->renderSelectBox('[mvcaction]['.$aid.'][view]',$action[view],$viewValues)) . '<br />' .
			      $this->widen_view($action[view], $views[$action[view]]).'</dd>
'.($action[view]!=''?'
		<dt style="font-weight: bold; white-space: nowrap;">Template</dt>
			<dd>'.str_replace('<select ', '<select style="width: 20em;" ', $this->renderSelectBox('[mvcaction]['.$aid.'][template]',$action[template],$templValues)) . '<br />' .
			      $this->widen_template($action[template], $tmpls[$action[template]]).'</dd>
'.($action[template]!=''?'
		<dt style="font-weight: bold; white-space: nowrap;">Follow-Ups</dt>
			<dd>'.$this->widen_action_follow_ups($action, $tmpls[$action[template]]).'</dd>
':'').'
':'').'
':'').'
	</dl>
</div>
		';

		return $content;
	}

	function widen_controller_grouped_by_model($cid, $controller) { if (!$controller) return '';
		$content = '';
		$actns = $this->wizard->wizArray['mvcaction'];
		$modls = $this->wizard->wizArray['mvcmodel'];
		$modln = array();
		foreach ($actns as $key => $struct)
			if ($struct['controller'] == $cid)
				$modln[$struct['model']]++;

		$header = $controller[title] . '<br /><span style="font-size: 0.8em;">(' . $controller[freename] . ')</span>';
		$content .= '
<table cellpadding="0" cellspacing="0" border="0">
<tr><td style="padding: 5px 15px 5px 5px; background: center right url(/typo3/ext/kickstarter__mvc_ex/gfx/connector-enter.png) repeat-y;" rowspan="'.(count($modln)+1).'">
<div style="padding: 5px; border: 1px solid black; background: #F7F7F7;">
	<h1 style="margin-top: 0;">'.$header.'</h1>
	Controller
</div>
</td><td></td></tr>
';
		if (count($modln) > 0) {
			$modlm = 0;
			foreach ($modln as $mid => $actnn)
				if (1) {
					$modlm++;
					$content .= '
<tr><td style="padding: 5px 5px 5px 15px; background: center left url(/typo3/ext/kickstarter__mvc_ex/gfx/connector'.(count($modln)==1?'-enter':($modlm==1?'-first':($modlm==count($modln)?'-last':''))).'.png) repeat-y;">
					';
		/* ---------------------------------- */
		$header = $mid ? $modls[$mid][title] . '<br /><span style="font-size: 0.8em;">(' . $modls[$mid][freename] . ')</span>' : 'Static';
		$content .= '
<table cellpadding="0" cellspacing="0" border="0">
<tr><td style="padding: 5px 15px 5px 5px; background: center right url(/typo3/ext/kickstarter__mvc_ex/gfx/connector-enter.png) repeat-y;" rowspan="'.($actnn+1).'">
<div style="padding: 5px; border: 1px solid black; background: #F7F7F7;">
	<h1 style="margin-top: 0;">'.$header.'</h1>
'.($header!='Static'?$this->widen_model_fields($modls[$mid]):'').'
</div>
</td><td></td></tr>
';
		if ($actnn > 0) {
			$actnm = 0;
			foreach ($actns as $key => $struct)
				if ($struct['controller'] == $cid)
				if ($struct['model'] == $mid) {
					$actnm++;
					$content .= '
<tr><td style="padding: 5px 5px 5px 15px; background: center left url(/typo3/ext/kickstarter__mvc_ex/gfx/connector'.($actnn==1?'-enter':($actnm==1?'-first':($actnm==$actnn?'-last':''))).'.png) repeat-y;">'.$this->widen_action($key, $struct).'</td></tr>
					';
				}
		}

		$content .= '
</table>
';
		/* ---------------------------------- */
					$content .= '
</td></tr>
					';
				}
		}

		$content .= '
</table>
';
		return $content;
	}

	function widen_controller($cid, $controller) { if (!$controller) return '';
		$content = '';
		$actns = $this->wizard->wizArray['mvcaction'];
		$actnn = 0;
		foreach ($actns as $key => $struct)
			if ($struct['controller'] == $cid)
				$actnn++;

		$header = $controller[title] . '<br /><span style="font-size: 0.8em;">(' . $controller[freename] . ')</span>';
		$content .= '
<table cellpadding="0" cellspacing="0" border="0">
<tr><td style="padding: 5px 15px 5px 5px; background: center right url(/typo3/ext/kickstarter__mvc_ex/gfx/connector-enter.png) repeat-y;" rowspan="'.($actnn+1).'">
<div style="padding: 5px; border: 1px solid black; background: #F7F7F7;">
	<h1 style="margin-top: 0;">'.$header.'</h1>
	Controller
</div>
</td><td></td></tr>
';
		if ($actnn > 0) {
			$actnm = 0;
			foreach ($actns as $key => $struct)
				if ($struct['controller'] == $cid) {
					$actnm++;
					$content .= '
<tr><td style="padding: 5px 5px 5px 15px; background: center left url(/typo3/ext/kickstarter__mvc_ex/gfx/connector'.($actnn==1?'-enter':($actnm==1?'-first':($actnm==$actnn?'-last':''))).'.png) repeat-y;">'.$this->widen_action($key, $struct).'</td></tr>
					';
				}
		}

		$content .= '
</table>
';
		return $content;
	}

	function widen_plugin($pid, $plugin) { if (!$plugin) return '';
		$content = '';
		$ctrls = $this->wizard->wizArray['mvccontroller'];
		$ctrln = 0;
		foreach ($ctrls as $key => $struct)
			if ($struct['plugin'] == $pid)
				$ctrln++;

		$header = $plugin[title] . '<br /><span style="font-size: 0.8em;">(mvc' . $pid . ')</span>';
		$content .= '
<table cellpadding="0" cellspacing="0" border="0">
<tr><td style="padding: 5px 15px 5px 5px; background: center right url(/typo3/ext/kickstarter__mvc_ex/gfx/connector-enter.png) repeat-y;" rowspan="'.($ctrln+1).'">
<div style="padding: 5px; border: 1px solid black; background: #F7F7F7;">
	<h1 style="margin-top: 0;">'.$header.'</h1>
	'.$this->widen_plugin_pids($pid, $plugin).'
</div>
</td><td></td></tr>
';
		if ($ctrln > 0) {
			$ctrlm = 0;
			foreach ($ctrls as $key => $struct)
				if ($struct['plugin'] == $pid) {
					$ctrlm++;
					$content .= '
<tr><td style="padding: 5px 5px 5px 15px; background: center left url(/typo3/ext/kickstarter__mvc_ex/gfx/connector'.($ctrln==1?'-enter':($ctrlm==1?'-first':($ctrlm==$ctrln?'-last':''))).'.png) repeat-y;">'.$this->widen_controller_grouped_by_model($key, $struct).'</td></tr>
					';
				}
		}

		$content .= '
</table>
';
		return $content;
	}

	/**
	 * Renders the form in the kickstarter; this was add_cat_pi()
	 *
	 * @return	HTML
	 */
	function render_wizard() {
		$content = '';
		$plugs = $this->wizard->wizArray['mvc'];
		$plugn = count($plugs);

		$content .= '
<table cellpadding="0" cellspacing="0" border="0">
<tr><td style="padding: 5px 15px 5px 5px; background: center right url(/typo3/ext/kickstarter__mvc_ex/gfx/connector-enter.png) repeat-y;" rowspan="'.($plugn+1).'">
<div style="padding: 5px; border: 1px solid black; background: #F7F7F7;">
	<h1 style="margin-top: 0;">'.'Typo3'.'</h1>
</div>
</td><td></td></tr>
';
		if ($plugn > 0) {
			$plugm = 0;
			foreach ($plugs as $key => $struct)
				if (1) {
					$plugm++;
					$content .= '
<tr><td style="padding: 5px 5px 5px 15px; background: center left url(/typo3/ext/kickstarter__mvc_ex/gfx/connector'.($plugn==1?'-enter':($plugm==1?'-first':($plugm==$plugn?'-last':''))).'.png) repeat-y;">'.$this->widen_plugin($key, $struct).'</td></tr>
					';
				}
		}

		$content .= '
</table>
';
		return $content;
	}
}


// Include ux_class extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc_ex/sections/class.tx_kickstarter_section_mvc_diagram.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc_ex/sections/class.tx_kickstarter_section_mvc_diagram.php']);
}

?>
