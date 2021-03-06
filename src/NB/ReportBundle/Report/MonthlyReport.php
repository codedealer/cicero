<?php
namespace NB\ReportBundle\Report;

use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Request;

use NB\ReportBundle\Entity\ReportSummary;
use NB\ReportBundle\Model\ContractContainer;

class MonthlyReport
{
	protected $query, $localeSettings, $reportSummary, $doctrine;

	const REPORT_ID = ContractContainer::MONTHLY;

	public function __construct(Query $query, $localeSettings, ReportSummary $reportSummary){
		$this->query = $query;
		$this->localeSettings = $localeSettings;
		$this->reportSummary = $reportSummary;
	}

	public function doctrineRequired(){
		return true;
	}

	public function setDoctrine($doctrine){
		$this->doctrine = $doctrine;
	}

	public function helperRequired(){
		return false;
	}

	public function setHelper($doctrine){
		return false;
	}

	protected function getHead(){
		return ['Время', 'Юрист', 'Должность', 'Тип работы', 'Комментарий', 'Часы'];
	}

	public function getForm($formBuilder){
		return $formBuilder
				->add('client', 'oro_jqueryselect2_hidden', [
					'autocomplete_alias' => 'clients',
					'label' => 'Клиент',
					'required' => true
				])
				->getForm()
				;
	}

	public function process($form){
		return $form->get('client')->getData();
	}

	public function getReportTable(Request $request){
		$result = $this->getQueryResult($request);
		
		$out = [];
		$totals = [
			'all' => ['hours' => 0],
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

	public function getExpressDefinition(){
		return 'NBReportBundle:Report:month_definition.html.twig';
	}

	public function getTotals($record, &$totals){
		$overallTimeInMinutes = 0;
		//foreach ($records as $record) {
			$overallTimeInMinutes = ReportUtils::calculateRawInterval($record['startDate'], $record['endDate']);
			
			$titleName = $record['titleName'] ? $record['titleName'] : 'Призрак';

			if(array_key_exists($record['worktypeId'], $totals['uniqueWorkTypes'])){
				$totals['uniqueWorkTypes'][$record['worktypeId']]['hours'] += $overallTimeInMinutes;
				if(array_key_exists($titleName, $totals['uniqueWorkTypes'][$record['worktypeId']]['titles'])){
					$totals['uniqueWorkTypes'][$record['worktypeId']]['titles'][$titleName]['hours'] += $overallTimeInMinutes;
				}
				else{
					$totals['uniqueWorkTypes'][$record['worktypeId']]['titles'][$titleName] = ['hours' => $overallTimeInMinutes];
				}
			}
			else{
				$totals['uniqueWorkTypes'][$record['worktypeId']] = [
				'name' => $record['worktypeName'],
				'hours' => $overallTimeInMinutes,
				'titles' => [
					$titleName => ['hours' => $overallTimeInMinutes],
				],
				];
			}
		//}

		$totals['all']['hours'] += $overallTimeInMinutes;
	}

	public function getExpressExcelObject(Request $request, $phpexcel, $client){
		$this->query = new Query($this->doctrine->getManager());
		$dql = "SELECT workunit.id, workunit.subject, workunit.startDate, workunit.endDate, worktype.name as worktypeName, worktype.id as worktypeId, CONCAT(owner.firstName, CONCAT(' ', owner.lastName)) as ownerName, title.name as titleName FROM NB\ReportBundle\Entity\WorkUnit workunit LEFT JOIN workunit.worktype worktype LEFT JOIN workunit.owner owner LEFT JOIN workunit.client client LEFT JOIN owner.custom_title title WHERE client.id = :clientId AND workunit.contract = :contractId AND workunit.startDate >= :startDate AND workunit.endDate <= :endDate ORDER BY workunit.startDate ASC";
		$this->query->setDql($dql);
		$startDate = new \DateTime('first day of this month');
		$endDate = new \DateTime('last day of this mont');
		
		$this->query->setParameter('startDate', $startDate)
			  ->setParameter('endDate', $endDate)
			  ->setParameter('clientId', $request->get('clientId'))
			  ->setParameter('contractId', $request->get('id'))
			  ;
		$result = $this->query->getArrayResult();

		return $this->buildExcelObject($result, $phpexcel, $client);
	}

	public function getExcelObject(Request $request, $phpexcel, $client){
		$result = $this->getQueryResult($request);
		return $this->buildExcelObject($result, $phpexcel, $client);
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
        $sheet->getStyle('A3:F3')->applyFromArray([
        	'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID,
        				'color' => ['rgb' => 'DDDDDD']],
        	'font' => ['bold' => true]
        	]);
        $row = 4;
        $totals = [
			'all' => ['hours' => 0],
			'uniqueWorkTypes' => [],
		];
        foreach ($result as $record) {
        	$sheet->setCellValue('A' . $row, ReportUtils::formatDate($record['startDate'], $record['endDate'], $this->localeSettings))
        		->setCellValue('B' . $row, $record['ownerName'])
        		->setCellValue('C' . $row, $record['titleName'])
        		->setCellValue('D' . $row, $record['worktypeName'])
        		->setCellValue('E' . $row, $record['subject'])
        		->setCellValue('F' . $row, ReportUtils::calculateInterval($record['startDate'], $record['endDate']))
        		;
        		$row++;
        		$this->getTotals($record, $totals);
        }

        $this->forceUniqueWorktypes($totals);

		$this->recalculateHours($totals);

        foreach (range('A','F') as $colId) {
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
			  ->mergeCells('A'.$row.':E'.$row)
			  ->getStyle('A'.$row.':F'.$row)
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
						}
						$sheet->setCellValue('D'.$row, $titleName)
							  ->setCellValue('E'.$row, $value['hours']);
						$row++;
					}
					$row--;
					$endMerge = $row;
					$sheet->mergeCells('A'.$startMerge.':C'.$endMerge)
						  ->mergeCells('F'.$startMerge.':F'.$endMerge);
				}
				else{
					$sheet->setCellValue('A'.$row, $worktype['name'])
						  ->setCellValue('F'.$row, $worktype['hours'])
						  ->mergeCells('A'.$row.':E'.$row)
						  ;
				}
				$row++;
			}
			$row--;
			$sheet->getStyle('A'.$startSubTotals.':F'.$row)
				  ->applyFromArray([
        				'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID,
        				'color' => ['rgb' => 'EFEFEF']]])
				  ;
		}
	}

	protected function getQueryResult(Request $request){
		$this->query->setParameter('clientId', $request->get('clientId'))
					->setParameter('contractId', $request->get('id'))
					;
		return $this->query->getArrayResult();
	}

	/* So basically totals has hours data for all worktypes classified 
	*  by titleNames. But in fact it only matters for law type of work
	*  I have no idea what the client meant :o
	*  Hence implementation is hardcoded
	*/
	protected function forceUniqueWorktypes(&$totals){
		//techical work is not classified by titleName
		if(array_key_exists(1, $totals['uniqueWorkTypes']))
			unset($totals['uniqueWorkTypes'][1]['titles']);
	}

	//convert int minutes to hours/minutes representation
	protected function recalculateHours(&$totals){
		$totals['all']['hours'] = ReportUtils::formatInterval($totals['all']['hours']);
		foreach ($totals['uniqueWorkTypes'] as $id => $wt) {
			$totals['uniqueWorkTypes'][$id]['hours'] = ReportUtils::formatInterval($wt['hours']);
			if(array_key_exists('titles', $wt)){
				foreach ($wt['titles'] as $titleName => $title) {
					$totals['uniqueWorkTypes'][$id]['titles'][$titleName]['hours'] = ReportUtils::formatInterval($title['hours']);
				}
			}
		}
	}
}