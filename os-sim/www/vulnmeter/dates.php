<?php
class dates {
	public static $daysMap = array (
			'Sunday',
			'Monday',
			'Tuesday',
			'Wednesday',
			'Thursday',
			'Friday',
			'Saturday'
	);

	public static $nweekday = array(
			'1' =>  'First',
			'2' =>  'Second',
			'3' =>  'Third',
			'4' =>  'Fourth',
			'5' =>  'Fifth'
	);

	private static function getTimeArray($max,$step = 1,$min = 0) {
		$arr = range($min,$max,$step);
		return array_combine($arr,$arr);
	}

	public static function getHours() {
		return self::getTimeArray(23);
	}

	public static function getMinutes() {
		return self::getTimeArray(60,15);
	}

	public static function getYears($ystart) {
		$current_year = date("Y");
		return self::getTimeArray($current_year+1,1,min($ystart,$current_year));
	}

	public static function getMonths() {
		return self::getTimeArray(12,1,1);
	}

	public static function getDays() {
		return self::getTimeArray(31,1,1);
	}

	public static function getFrequencies() {
		return self::getTimeArray(30,1,1);
	}
}