<?php
namespace NB\ReportBundle\Report;

class ReportUtils
{
	public static function formatDate($start, $end, $localeSettings){
		$start->setTimeZone(new \DateTimeZone($localeSettings->getTimeZone()));
		$end->setTimeZone(new \DateTimeZone($localeSettings->getTimeZone()));
		return $start->format('d.m.Y H:i') . ' - ' . $end->format('d.m.Y H:i');
	}

	public static function calculateInterval($start, $end, $localeSettings){
		/*this is unreliable
		$interval = $start->diff($end);
		$formatString = '';
		if(0 != $interval->m)
			$formatString = '%m мес. ';
		if(0 != $interval->d)
			$formatString = '%d д. ';
		$formatString .= '%h ч. ';
		if(0 != $interval->i)
			$formatString .= '%i м.';
			*/
		
		$rawInterval = $end->getTimeStamp() - $start->getTimeStamp();
		$rawInterval /= 60;
		$minutes = $rawInterval % 60;
		$hours = floor($rawInterval / 60);
		$output = '';

		return $minutes ? "$hours ч. $minutes мин." : "$hours ч.";
	}
}