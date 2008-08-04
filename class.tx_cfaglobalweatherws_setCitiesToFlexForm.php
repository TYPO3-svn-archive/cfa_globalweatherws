<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Claus Fassing <claus@fassing.eu>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class tx_cfaglobalweatherws_setCitiesToFlexForm  {

  function cityResponse ($config) {
    $this->conf=$config;
   
    $optionList = array();

    $flexform = t3lib_div::xml2array($config['row']['pi_flexform']);
    
    $country = (is_array($flexform)) ? $flexform['data']['sDEF']['lDEF']['country']['vDEF'] : '';


    
    $client = new SoapClient('http://www.webservicex.net/globalweather.asmx?WSDL'); // create a PHP SOAP object
        
   
        
     $param = array(array("CountryName"=>$country));
     $client->__getFunctions();
              
     $content = $client->__call("GetCitiesByCountry",$param);
     $wsstr = $content->GetCitiesByCountryResult;

     $wsarray=explode("\n",$wsstr);
     

     for($x = 0; $x < count($wsarray); $x++){
       if(ereg('<City>',$wsarray[$x])) {
  	$result[$x] = $this->between('<City>','</City>',$wsarray[$x]);
       }
     }
     /* do some array clean up from previous loop */
    $result_clean = array_values(array_diff($result, array('')));
      
     for($x = 0; $x < count($result_clean); $x++){
	//print_r($result[$x]);
	$optionList[$x] = array(0 => $result_clean[$x], 1 => $result_clean[$x]);
     }
     
    $config['items'] = array_merge($config['items'],$optionList);
    return $config;
  }
  
  function between($beg, $end, $str) {
      $a = explode($beg, $str, 2);
      $b = explode($end, $a[1]);
      return $b[0];
  }
 }
 
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfa_globalweatherws/class.tx_cfaglobalweatherws_setCitiesToFlexForm.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfa_globalweatherws/class.tx_cfaglobalweatherws_setCitiesToFlexForm.php']);
}
?>
