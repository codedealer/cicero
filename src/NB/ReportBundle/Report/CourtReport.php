<?php
namespace NB\ReportBundle\Report;

use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Request;

use NB\ReportBundle\Entity\ReportSummary;
use NB\ReportBundle\Model\ContractContainer;

class CourtReport extends ProjectReport
{
	const REPORT_ID = ContractContainer::COURT;

	public function getForm($formBuilder){
		return $formBuilder
				->add('court', 'oro_jqueryselect2_hidden', [
					'autocomplete_alias' => 'courts',
					'label' => 'Судебное дело',
					'required' => true
				])
				->getForm()
				;
	}

	public function process($form){
		return $form->get('court')->getData();
	}

	public function getExpressDefinition(){
		return 'NBReportBundle:Report:court_definition.html.twig';
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
          ->setCellValue('A2', 'Судебное дело: ' . $client->getName() . ' / ' . $client->getNumber())
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
}