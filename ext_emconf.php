<?php

########################################################################
# Extension Manager/Repository config file for ext: "kickstarter__mvc"
#
# Auto generated 13-07-2007 23:42
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Kickstarter for lib/div',
	'description' => 'This is an addon to the kickstarter and generates code for the lib/div extension development framework of ECT. Please report bugs to http://bugs.typo3.org section kickstarter__mvc.',
	'category' => 'be',
	'shy' => 0,
	'version' => '0.0.5',
	'dependencies' => 'kickstarter',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'alpha',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Christian Welzel',
	'author_email' => 'gawain@camlann.de',
	'author_company' => 'schech.net',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'kickstarter' => '0.3.8-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:16:{s:9:"ChangeLog";s:4:"f415";s:10:"README.txt";s:4:"aa0b";s:12:"ext_icon.gif";s:4:"b4e6";s:17:"ext_localconf.php";s:4:"bdf4";s:21:"template_flexform.xml";s:4:"fa28";s:19:"doc/wizard_form.dat";s:4:"db58";s:20:"doc/wizard_form.html";s:4:"1d08";s:45:"sections/class.tx_kickstarter_section_mvc.php";s:4:"6dc4";s:52:"sections/class.tx_kickstarter_section_mvc_action.php";s:4:"09c7";s:50:"sections/class.tx_kickstarter_section_mvc_base.php";s:4:"15b9";s:51:"sections/class.tx_kickstarter_section_mvc_model.php";s:4:"01c3";s:54:"sections/class.tx_kickstarter_section_mvc_template.php";s:4:"a9d1";s:50:"sections/class.tx_kickstarter_section_mvc_view.php";s:4:"232a";s:57:"renderer/class.tx_kickstarter_classperaction_renderer.php";s:4:"352a";s:58:"renderer/class.tx_kickstarter_methodperaction_renderer.php";s:4:"65b1";s:47:"renderer/class.tx_kickstarter_renderer_base.php";s:4:"4189";}',
	'suggests' => array(
	),
);

?>