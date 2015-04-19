<?php
namespace NB\ReportBundle\Report;

use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Request;

use NB\ReportBundle\Entity\ReportSummary;
use NB\ReportBundle\Model\ContractContainer;

class MonthlyReport
{
	protected $query, $localeSettings, $reportSummary;

	public function __construct(Query $query, $localeSettings, ReportSummary $reportSummary){
		$this->query = $query;
		$this->localeSettings = $localeSettings;
		$this->reportSummary = $reportSummary;
	}

	public function doctrineRequired(){
		return false;
	}

	protected function getHead(){
		return ['Время', 'Юрист', 'Должность', 'Тип работы', 'Комментарий', 'Часы'];
	}

	public function getReportTable(Request $request){
		$result = $this->getQueryResult($request);
		
		$out = [];
		foreach ($result as $record) {
			$out[] = [
				ReportUtils::formatDate($record['startDate'], $record['endDate'], $this->localeSettings),
				$record['ownerName'],
				$record['titleName'],
				$record['worktypeName'],
				$record['subject'],
				ReportUtils::calculateInterval($record['startDate'], $record['endDate']),
			];
		}

		return [
			'head' => $this->getHead(),
			'body' => $out
		];
	}

	public function getExcelObject(Request $request, $phpexcel, $client){
		$result = $this->getQueryResult($request);

		$info = ContractContainer::info(ContractContainer::MONTHLY);
		$title = $info['report'];
		$e = $phpexcel->createPHPExcelObject();
		$e->getProperties()->setCreator("Nota Bene")
           ->setLastModifiedBy("Nota Bene")
           ->setTitle($title)
           ->setSubject($title)
           ;
        $e->setActiveSheetIndex(0);
        $e->getActiveSheet()->setCellValue('A1', $title)
          ->setCellValue('A2', 'Клиент: ' . $client->getName())
          ->setCellValue('B2', 
          				(new \DateTime('now', new \DateTimeZone($this->localeSettings->getTimeZone())))
          					->format('d.m.Y H:i:s'));
        $sheet = $e->getActiveSheet();
        $sheet->getStyle('A1')->getFont()->setSize(16);

        $row = 3;
        $column = 'A';
        foreach ($this->getHead() as $h) {
        	$sheet->setCellValue($column . $row, $h);
        	$column++;
        }
        $sheet->getStyle('A3:F3')->applyFromArray([
        	'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID,
        				'color' => ['rgb' => 'DDDDDD']],
        	'font' => ['bold' => true]
        	]);
        $row = 4;
        foreach ($result as $record) {
        	$sheet->setCellValue('A' . $row, ReportUtils::formatDate($record['startDate'], $record['endDate'], $this->localeSettings))
        		->setCellValue('B' . $row, $record['ownerName'])
        		->setCellValue('C' . $row, $record['titleName'])
        		->setCellValue('D' . $row, $record['worktypeName'])
        		->setCellValue('E' . $row, $record['subject'])
        		->setCellValue('F' . $row, ReportUtils::calculateInterval($record['startDate'], $record['endDate']))
        		;
        		$row++;
        }

        foreach (range('A','F') as $colId) {
        	if('E' !== $colId)
        		$sheet->getColumnDimension($colId)->setAutoSize(true);
        	else{
        		$row--;
        		$sheet->getColumnDimension('E')->setWidth(50);
        		$sheet->getStyle('E4:E'.$row)->getAlignment()->setWrapText(true);
        	}
        }
         
        return $e;
	}

	protected function getQueryResult(Request $request){
		$this->query->setParameter('clientId', $request->get('clientId'))
					->setParameter('contractId', $request->get('id'))
					;
		return $this->query->getArrayResult();
	}
}