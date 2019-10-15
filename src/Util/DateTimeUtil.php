<?php

namespace App\Util;

/**
 * DateTimeUtil
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 */
class DateTimeUtil
{
	/**
	 * Build datetime
	 * 
	 * @param $datetime
	 * @return \DateTime
	 */
	public static function buildDatetime($datetime) {
		$newDateTime = new \DateTime();
		
		if ($datetime){
			if(is_array($datetime['date']) && array_key_exists('year', $datetime['date'])){
				$year = $datetime['date']['year'];
				$month = $datetime['date']['month'];
				$day = $datetime['date']['day'];
					
				$hour = $datetime['time']['hour'];
				$minute = $datetime['time']['minute'];
					
				$newDateTime->setDate($year, $month, $day);
				$newDateTime->setTime($hour, $minute);
			}else{
				$newDateTime = new \DateTime($datetime['date']);
			}
		}
		
		return $newDateTime;
	}
	
	/**
	 * Convert Interval to secondes
	 *
	 * @param int $intervale
	 * @param string $unity
	 * @return number|boolean
	 */
	public static function convertIntervale(int $intervale, string $unity) {
		switch ($unity){
			case "PT1H":
				$intervale = $intervale * 60;
				break;
			case "P1D":
				$intervale = $intervale * 60 * 24;
				break;
			case "P1M":
				$intervale = $intervale * 60 * 24 * 30;
				break;
		}
		
		return $intervale;
	}	
}