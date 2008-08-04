<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:cfa_globalweatherws/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');

t3lib_extMgm::addStaticFile($_EXTKEY,"pi1/static/css/","default CSS-styles");
t3lib_extMgm::addStaticFile($_EXTKEY,"pi1/static/","Globale Weather Webservice");

include_once(t3lib_extMgm::extPath($_EXTKEY).'class.tx_cfaglobalweatherws_setCitiesToFlexForm.php');
/*
 * Flexform integration
 */
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1'] ='pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:'.$_EXTKEY . '/flexform.xml');
?>
