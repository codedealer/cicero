<?php
namespace NB\ReportBundle\Datagrid;

class DatagridBuilderHelper
{
	private $settings;

	public function __construct($localeSettings){
		$this->settings = $localeSettings;
	}

	public function getDefaultDateFilterValue(){
		$today = new \DateTime('now', new \DateTimeZone($this->settings->getTimeZone()));

		return $today;
	}
}