<?php 

namespace AppBundle\Helper;

class DeckReqHelper
{
	
	public function __construct()
	{

	}

	/**
	* Parse deck requirements/restrictions and convert to array
	* @param string $text
	* @return Array
	*/
	public function parseString($test) {
		
		$return_requirements = [];
		// seperate based on commas
		$restrictions = explode(",", $text);
		foreach($restrictions as $restriction) {
			// if we have a value then next split on :
			if (trim($restriction)){
				$matches = [];
				$params = explode(":", $restriction);
				//$text .= print_r($matches,1);	
				if (isset($params[0])){
					$type = trim($params[0]);
					$param1 = false;
					$param2 = false;
					$param3 = false;
					$param4 = false;
					
					if (isset($params[1])){
						$param1 = trim($params[1]);
					}
					if (isset($params[2])){
						$param2 = trim($params[2]);
					}
					if (isset($params[3])){
						$param3 = trim($params[3]);
					}
					if (isset($params[4])){
						$param4 = trim($params[4]);
					}
					
					if (!isset($return_requirements[$type])){
						$return_requirements[$type] = [];
					}
					$parsed = false;
					switch($type){
						case "faction":{
							$return_requirements[$type][$param1] = [
								"min" => $param2,
								"max" => $param3
							]
						}
						default:{
							$return_requirements[$type][] = $param1;
						}
					}
				}
			}
		}
		return $return_requirements;

	}
	
	
}