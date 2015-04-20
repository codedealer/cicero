<?php
namespace NB\ReportBundle\Report;

use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Request;

use NB\ReportBundle\Entity\ReportSummary;
use NB\ReportBundle\Model\ContractContainer;

class HourlyReport extends MonthlyReport
{
	const REPORT_ID = ContractContainer::HOURLY;

	private $cacheWorkTypes = [];
	private $helper;

	public function helperRequired(){
		return true;
	}

	public function setHelper($helper){
		$this->helper = $helper;
	}

	public function getExpressDefinition(){
		return 'NBReportBundle:Report:hourly_definition.html.twig';
	}

	
	public function getExpressExcelObject(Request $request, $phpexcel, $client){
		$this->query = new Query($this->doctrine->getManager());
		$dql = "SELECT workunit.id, workunit.subject, workunit.startDate, workunit.endDate, worktype.name as worktypeName, worktype.id as worktypeId, CONCAT(owner.firstName, CONCAT(' ', owner.lastName)) as ownerName, title.name as titleName FROM NB\ReportBundle\Entity\WorkUnit workunit LEFT JOIN workunit.worktype worktype LEFT JOIN workunit.owner owner LEFT JOIN workunit.client client LEFT JOIN owner.custom_title title WHERE client.id = :clientId AND workunit.contract = :contractId ORDER BY workunit.startDate ASC";
		$this->query->setDql($dql);
		
		$info = ContractContainer::info(self::REPORT_ID);
		$this->query
			  ->setParameter('clientId', $request->get('clientId'))
			  ->setParameter('contractId', $request->get('id'))
			  ;
		$result = $this->query->getArrayResult();

		return $this->buildExcelObject($result, $phpexcel, $client);
	}

	protected function getHead(){
		return ['Время', 'Юрист', 'Должность', 'Тип работы', 'Комментарий', 'Часы', 'Стоимость'];
	}

	public function getReportTable(Request $request){
		$result = $this->getQueryResult($request);
		
		$out = [];
		$totals = [
			'all' => ['hours' => 0, 'price' => 0],
			'uniqueWorkTypes' => [],
		];
		foreach ($result as $record) {
			
			$out[] = [
				ReportUtils::formatDate($record['startDate'], $record['endDate'], $this->localeSettings),
				$record['ownerName'],
				$record['titleName'],
				$record['worktypeName'],
				$record['subject'],
				ReportUtils::calculateInterval($record['startDate'], $record['endDate']),
				'' . $this->calculatePrice(ReportUtils::calculateRawInterval($record['startDate'], $record['endDate']), $record['worktypeId'], $record['titleName']) . ' p.',
			];
			$this->getTotals($record, $totals);
		}

		$this->forceUniqueWorktypes($totals);

		$this->recalculateHours($totals);

		return [
			'head' => $this->getHead(),
			'body' => $out,
			'foot' => $totals
		];
	}

	public function getTotals($record, &$totals){
		$overallTimeInMinutes = 0;
		
			$overallTimeInMinutes = ReportUtils::calculateRawInterval($record['startDate'], $record['endDate']);
			
			$titleName = $record['titleName'] ? $record['titleName'] : 'Призрак';
			$price = $this->calculatePrice($overallTimeInMinutes, $record['worktypeId'], $titleName);

			if(array_key_exists($record['worktypeId'], $totals['uniqueWorkTypes'])){
				$totals['uniqueWorkTypes'][$record['worktypeId']]['hours'] += $overallTimeInMinutes;
				$totals['uniqueWorkTypes'][$record['worktypeId']]['price'] += $price;
				if(array_key_exists($titleName, $totals['uniqueWorkTypes'][$record['worktypeId']]['titles'])){
					$totals['uniqueWorkTypes'][$record['worktypeId']]['titles'][$titleName]['hours'] += $overallTimeInMinutes;
					$totals['uniqueWorkTypes'][$record['worktypeId']]['titles'][$titleName]['price'] += $price;
				}
				else{
					$totals['uniqueWorkTypes'][$record['worktypeId']]['titles'][$titleName] = ['hours' => $overallTimeInMinutes, 'price' => $price];
				}
			}
			else{
				$totals['uniqueWorkTypes'][$record['worktypeId']] = [
				'name' => $record['worktypeName'],
				'hours' => $overallTimeInMinutes,
				'price' => $price,
				'titles' => [
					$titleName => ['hours' => $overallTimeInMinutes, 'price' => $price],
				],
				];
			}
		

		$totals['all']['hours'] += $overallTimeInMinutes;
		$totals['all']['price'] += $price;
	}

	public function calculatePrice($time, $worktypeId, $titleName){
		if(!$this->cacheWorkTypes || !array_key_exists($worktypeId, $this->cacheWorkTypes)){
			$worktype = $this->helper->getEntity('Extend\Entity\worktype', $worktypeId);
			$this->cacheWorkTypes[$worktypeId] = $worktype;
		}
		else
			$worktype = $this->cacheWorkTypes[$worktypeId];

		if(!$worktype->getIsHourly())
			return (float) $worktype->getFlatrate();

		foreach ($worktype->getWorkrates() as $workrate) {
			$titles = $workrate->getTitles();
			if($titles->getName() == $titleName){
				$pricePerMinute = $time * ((float) $workrate->getRate()) / 60;
				return $pricePerMinute;
			}
		}

		return 0;
	}

	protected function buildExcelObject($result, $phpexcel, $client){
		

		$info = ContractContainer::info(self::REPORT_ID);
		$title = $info['report'];
		$e = $phpexcel->createPHPExcelObject();
		$e->getProperties()->setCreator("Nota Bene")
           ->setLastModifiedBy("Nota Bene")
           ->setTitle($title)
           ->setSubject($title)
           ;
        $e->setActiveSheetIndex(0);
        $e->getActiveSheet()->setCellValue('A1', $title)
          ->setCellValue('A2', $info['target_label'] . ': ' . $client->getName())
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
        $sheet->getStyle('A3:G3')->applyFromArray([
        	'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID,
        				'color' => ['rgb' => 'DDDDDD']],
        	'font' => ['bold' => true]
        	]);
        $row = 4;
        $totals = [
			'all' => ['hours' => 0, 'price' => 0],
			'uniqueWorkTypes' => [],
		];
        foreach ($result as $record) {
        	$sheet->setCellValue('A' . $row, ReportUtils::formatDate($record['startDate'], $record['endDate'], $this->localeSettings))
        		->setCellValue('B' . $row, $record['ownerName'])
        		->setCellValue('C' . $row, $record['titleName'])
        		->setCellValue('D' . $row, $record['worktypeName'])
        		->setCellValue('E' . $row, $record['subject'])
        		->setCellValue('F' . $row, ReportUtils::calculateInterval($record['startDate'], $record['endDate']))
        		->setCellValue('G' . $row, $this->calculatePrice(ReportUtils::calculateRawInterval($record['startDate'], $record['endDate']), $record['worktypeId'], $record['titleName']) . ' p.')
        		;
        		$row++;
        		$this->getTotals($record, $totals);
        }

        $this->forceUniqueWorktypes($totals);

		$this->recalculateHours($totals);

        foreach (range('A','G') as $colId) {
        	if('E' !== $colId)
        		$sheet->getColumnDimension($colId)->setAutoSize(true);
        	else{
        		$row--;
        		$sheet->getColumnDimension('E')->setWidth(50);
        		$sheet->getStyle('E4:E'.$row)->getAlignment()->setWrapText(true);
        	}
        }
        $row++;
        $this->drawExcelTotals($sheet, $totals, $row);
         
        return $e;
	}

	protected function drawExcelTotals($sheet, $totals, $row){
		$sheet->setCellValue('A'.$row, 'Итого:')
			  ->setCellValue('F'.$row, $totals['all']['hours'])
			  ->setCellValue('G'.$row, $totals['all']['price'] . ' p.')
			  ->mergeCells('A'.$row.':E'.$row)
			  ->getStyle('A'.$row.':G'.$row)
			  ->applyFromArray([
        	'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID,
        				'color' => ['rgb' => 'DDDDDD']],
        	'font' => ['bold' => true]
        	]);

		$row++;
		$startSubTotals = $row;
		if(count($totals['uniqueWorkTypes']) > 1){
			foreach ($totals['uniqueWorkTypes'] as $wtId => $worktype) {
				if(array_key_exists('titles', $worktype)){
					$first = true;
					$startMerge = $row;
					foreach ($worktype['titles'] as $titleName => $value) {
						if($first){
							$first = false;
							$sheet->setCellValue('A'.$row, $worktype['name'])
								  ->getStyle('A'.$row)
								  ->getAlignment()
								  ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
							$sheet->setCellValue('F'.$row, $worktype['hours'])
								  ->getStyle('F'.$row)
								  ->getAlignment()
								  ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
							$sheet->setCellValue('G'.$row, $worktype['price'] . ' p.')
								  ->getStyle('G'.$row)
								  ->getAlignment()
								  ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
						}
						$sheet->setCellValue('D'.$row, $titleName)
							  ->setCellValue('E'.$row, $value['hours'] . ' (' . $value['price'] . ' p.)');
							  
						$row++;
					}
					$row--;
					$endMerge = $row;
					$sheet->mergeCells('A'.$startMerge.':C'.$endMerge)
						  ->mergeCells('F'.$startMerge.':F'.$endMerge)
						  ->mergeCells('G'.$startMerge.':G'.$endMerge);
				}
				else{
					$sheet->setCellValue('A'.$row, $worktype['name'])
						  ->setCellValue('F'.$row, $worktype['hours'])
						  ->setCellValue('G'.$row, $worktype['price'] . ' p.')
						  ->mergeCells('A'.$row.':E'.$row)
						  ;
				}
				$row++;
			}
			$row--;
			$sheet->getStyle('A'.$startSubTotals.':G'.$row)
				  ->applyFromArray([
        				'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID,
        				'color' => ['rgb' => 'EFEFEF']]])
				  ;
		}
	}
}