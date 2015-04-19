<?php
namespace NB\ReportBundle\Report;

use NB\ReportBundle\Model\ContractContainer;
use NB\ReportBundle\Entity\ReportSummary;

use Doctrine\ORM\Query;

class ReportFactory
{
	protected $doctrine, $localeSettings, $securityContext;

	private $reports = [
		ContractContainer::MONTHLY => 'NB\ReportBundle\Report\MonthlyReport',
		ContractContainer::HOURLY  => 'NB\ReportBundle\Report\HourlyReport'
	];

	public function __construct($doctrine, $securityContext, $localeSettings){
		$this->doctrine = $doctrine;
		$this->localeSettings = $localeSettings;
		$this->securityContext = $securityContext;
	}

	public function getReport($contractId){
		$userId = $this->securityContext->getToken()->getUser()->getId();
		$reportSummary = $this->doctrine->getRepository('NBReportBundle:ReportSummary')
							  ->findOneBy(['owner'=> $userId]);
		if(!($reportSummary instanceof ReportSummary))
			throw new \RuntimeException('Для данного пользователя нет настроек отчета');

		$dql = $reportSummary->getDql();

		$query = new Query($this->doctrine->getManager());
		$query->setDql($dql);

		$class = $this->reports[$contractId];
		$report = new $class($query, $this->localeSettings, $reportSummary);
		if($report->doctrineRequired()){
			$report->setDoctrine($doctrine);
			return $report;
		}
		return $report;
	}
}