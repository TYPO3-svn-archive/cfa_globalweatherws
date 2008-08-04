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

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'Globale Weather Webservice' for the 'cfa_globalweatherws' extension.
 *
 * @author	Claus Fassing <claus@fassing.eu>
 * @package	TYPO3
 * @subpackage	tx_cfaglobalweatherws
 */
class tx_cfaglobalweatherws_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_cfaglobalweatherws_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_cfaglobalweatherws_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'cfa_globalweatherws';	// The extension key.
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj=1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
	        $this->template = $this->cObj->fileResource($this->conf["templateFile"]);
	        $this->errortemplate = $this->cObj->fileResource($this->conf["errortemplateFile"]);
	  	$this->pi_initPIflexForm(); 
				
                /* configuration from flexforms (higher prio) */
                $country = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'country', 'sDEF');   
                $city = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'city', 'display');   
            
                /* configuration from TS (only if no configuration comes from felxform) */ 
                if(!$city) {
                	$city = $this->conf['city'];
                }
                if(!$country) {
                	$country = $this->conf['country'];
                }
             
                /* Start with the webservice query */
                try {
                	$client = new SoapClient('http://www.webservicex.net/globalweather.asmx?WSDL'); // create a PHP SOAP object
                } catch (SoapFault $fault) {
              		$content = $this->setError();
              		return $this->pi_wrapInBaseClass($content);
 		}
 		
                $param = array(array("CityName"=>$city,"CountryName"=>$country));
                $client->__getFunctions();
                
                try {
                  $content = $client->__call("GetWeather",$param);
                } catch (SoapFault $fault) {
              		$content = $this->setError();
              		return $this->pi_wrapInBaseClass($content);
 		}
              
                $xmlstr = $content->GetWeatherResult;
                
                /* The Response comes with an utf-16 Header, so we do set it to utf-8 */
                $xmlstr = str_replace("utf-16","utf-8",$xmlstr);
                
                $xml = simplexml_load_string($xmlstr);
                
                $status = $xml->Status;
                /* if a bad status return, get back with an error call */
                if($status != "Success") {
                	$content = $this->setError();
              		return $this->pi_wrapInBaseClass($content);
                }

                /*** Get the service items ***/
                $location = substr($xml->Location,0,15);
                $part = explode(",", $location);
                $location = $part[0];
                
                if(strpos($xml->Wind,"MPH") == 0) {
                	$wind  = $xml->Wind;
                } else {	
                	$start = strpos($xml->Wind,"MPH");
                	$wind = substr($xml->Wind,$start+5,4);
                }
                                
                $conditions = $xml->SkyConditions;
                if($conditions == "") {
                    $conditions = $this->getCondition($city);
                } else {
                    $this->setCondition($city,$conditions);
                }
                
                $start = strpos($xml->Temperature,"F");
                $temperature = substr($xml->Temperature,$start+3,4);
                
                $relativeHumidity = $xml->RelativeHumidity;
                
                $start = strpos($xml->Pressure,"Hg");
                $pressure = substr($xml->Pressure,$start+4,8);
                
                
                $subpart=$this->cObj->getSubpart($this->template,'###DETAILVIEW###'); 
                $markerArray['###HEADER###'] = $country;
                $markerArray['###LABEL_LOCATION###'] = $this->pi_getLL('cfa_globalweatherws.pi_frontend.location');
                $markerArray['###LOCATION###'] = $location;
                $markerArray['###LABEL_TEMPERATURE###'] = $this->pi_getLL('cfa_globalweatherws.pi_frontend.temperature');
                $markerArray['###TEMPERATURE###'] = $temperature;
                $markerArray['###LABEL_CONDITIONS###'] = $this->pi_getLL('cfa_globalweatherws.pi_frontend.condition');                
                $markerArray['###CONDITIONS###'] = $conditions;
                $markerArray['###LABEL_WIND###'] = $this->pi_getLL('cfa_globalweatherws.pi_frontend.wind');
                $markerArray['###WIND###'] = $wind;
                $markerArray['###LABEL_HUMIDITY###'] = $this->pi_getLL('cfa_globalweatherws.pi_frontend.humidity');
                $markerArray['###HUMIDITY###'] = $relativeHumidity;
                $markerArray['###LABEL_PRESSURE###'] = $this->pi_getLL('cfa_globalweatherws.pi_frontend.pressure');                
                $markerArray['###PRESSURE###'] = $pressure;
                $content = $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,array(),array());
                 
		return $this->pi_wrapInBaseClass($content);
                
                
	}

        function setCondition($city,$condition) {
          $conditionFile = "condition.txt";
          $search = array('/',' ');
          $city = str_replace($search,'',$city);
          $f = FOpen($city.$conditionFile,'wb');
          if ($f) {
              FWrite($f,$condition);
              FClose($f);
          }    
    
        }

        function getCondition($city) {
          $conditionFile = "condition.txt";
          $search = array('/',' ');
          $city = str_replace($search,'',$city);
          $f = FOpen($city.$conditionFile,'r');
          if ($f) {
            $c = FGets($f);
          } else {
            $c = "n/a";
          }
            return $c;
        }

        function setError() {
          $subpart=$this->cObj->getSubpart($this->errortemplate,'###DETAILVIEW###'); 
          $markerArray['###ERROR###'] = $this->pi_getLL('cfa_globalweatherws.pi_frontend.error');                  
          $content = $this->cObj->substituteMarkerArrayCached($subpart,$markerArray,array(),array());
          return($content);
        }
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfa_globalweatherws/pi1/class.tx_cfaglobalweatherws_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cfa_globalweatherws/pi1/class.tx_cfaglobalweatherws_pi1.php']);
}

?>