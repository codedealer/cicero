<?php
namespace NB\ReportBundle\Report;

use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Request;

use NB\ReportBundle\Entity\ReportSummary;

class MonthlyReport
{
	protected $query, $localeSettings, $reportSummary;

	public function __construct(Query $query, $localeSettings, ReportSummary $reportSummary){
		$this->query = $query;
		$this->localeSettings = $localeSettings;
		$this->reportSummary = $reportSummary;
	}

	protected function getHead(){
		return ['Время', 'Юрист', 'Должность', 'Тип работы', 'Комментарий', 'Часы'];
	}

	public function getReportTable(Request $request){
		$this->query->setParameter('clientId', $request->get('clientId'))
					->setParameter('contractId', $request->get('id'))
					;
		$result = $this->query->getArrayResult();
		
		$out = [];
		foreach ($result as $record) {
			$out[] = [
				ReportUtils::formatDate($record['startDate'], $record['endDate'], $this->localeSettings),
				$record['ownerName'],
				$record['titleName'],
				$record['worktypeName'],
				nl2br($record['subject']),
				ReportUtils::calculateInterval($record['startDate'], $record['endDate'], $this->localeSettings),
			];
		}

		return [
			'head' => $this->getHead(),
			'body' => $out
		];
	}
}