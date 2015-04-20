<?php
namespace NB\ReportBundle\Report;

class ReportUtils
{
	public static function formatDate($start, $end, $localeSettings){
		$start->setTimeZone(new \DateTimeZone($localeSettings->getTimeZone()));
		$end->setTimeZone(new \DateTimeZone($localeSettings->getTimeZone()));
		return $start->format('d.m.Y H:i') . ' - ' . $end->format('d.m.Y H:i');
	}

	public static function calculateInterval($start, $end){	
		$rawInterval = self::calculateRawInterval($start, $end);
		
		return self::formatInterval($rawInterval);
	}

	public static function calculateRawInterval($start, $end){
		$rawInterval = $end->getTimeStamp() - $start->getTimeStamp();
		$rawInterval /= 60;
		return $rawInterval;
	}

	public static function formatInterval($rawInterval){
		$minutes = $rawInterval % 60;
		$hours = floor($rawInterval / 60);
		$output = '';
		if($hours)
			$output = "$hours ч. ";
		if($minutes)
			$output .= "$minutes мин.";

		return $output;
	}
}