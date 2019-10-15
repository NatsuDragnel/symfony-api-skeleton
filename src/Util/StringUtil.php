<?php

namespace App\Util;

/**
 * StringUtil
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 */
class StringUtil
{
	/**
	 * Transform word to camel case word
	 * 
	 * @param string $word
	 */
	public static function transformToCamelCase($word) {
		if (preg_match("#_#", $word)){
			$split = explode("_", $word);
			$word = $split[0];
			
			if (count($split) > 1){
			    for ($i = 1; $i < count($split); $i++){
			        $word.= ucfirst($split[$i]);
			    }
			}
		}
		
		return $word;
	}
	
	/**
	 * Customize strpos function
	 */
	public static function customizeStrpos($string) {
		$matches = [];
		preg_match("#[0-9]#", $string, $matches, PREG_OFFSET_CAPTURE);
		$length = preg_match_all("#[0-9]#", $string);
		$value = null;
	
		foreach ($matches as $match){
			$value = mb_substr($string, $match[1], $length);
		}
	
		return $value;
	}
	
	public static function isValidFromUrl(string $url) {
	    $array = [];
	    $pattern = '#^(.*://)?([\w\-\.]+)\:?([0-9]*)/(.*)$#';
	    
	    if(preg_match($pattern, $url, $array) && !empty($array[2])){
	        return true;
	    }
	    
	    return false;
	}
}