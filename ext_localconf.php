<?php

//pi
$TYPO3_CONF_VARS['EXTCONF']['kickstarter']['sections']['mvc'] = array(
	'classname'   => 'tx_kickstarter_section_mvc',
	'filepath'    => 'EXT:kickstarter__mvc/sections/class.tx_kickstarter_section_mvc.php',
	'title'       => 'Frontend Plugins (MVC)',
	'description' => 'Create frontend plugins. Plugins are web applications running on the website itself (not in the backend of TYPO3). The default guestbook, message board, shop, rating feature etc. are examples of plugins.',
	'singleItem'  => 'mvc',
);


?>