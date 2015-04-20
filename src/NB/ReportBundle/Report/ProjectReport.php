<?php
namespace NB\ReportBundle\Report;

use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Request;

use NB\ReportBundle\Entity\ReportSummary;
use NB\ReportBundle\Model\ContractContainer;

class ProjectReport extends MonthlyReport
{
	const REPORT_ID = ContractContainer::PROJECT;

	public function getForm($formBuilder){
		return $formBuilder
				->add('project', 'oro_jqueryselect2_hidden', [
					'autocomplete_alias' => 'projects',
					'label' => 'Проект',
					'required' => true
				])
				->getForm()
				;
	}

	public function process($form){
		return $form->get('project')->getData();
	}

	public function getExpressDefinition(){
		return 'NBReportBundle:Report:project_definition.html.twig';
	}

	protected function getQueryResult(Request $request){
		$info = ContractContainer::info(self::REPORT_ID);
		$this->query->setParameter('clientId', $request->get('clientId'))
					->setParameter('contractId', $request->get('id'))
					->setParameter('relatedClass', $info['target_class'])
					;
		return $this->query->getArrayResult();
	}

	public function getExpressExcelObject(Request $request, $phpexcel, $client){
		$this->query = new Query($this->doctrine->getManager());
		$dql = "SELECT workunit.id, workunit.subject, workunit.startDate, workunit.endDate, worktype.name as worktypeName, worktype.id as worktypeId, CONCAT(owner.firstName, CONCAT(' ', owner.lastName)) as ownerName, title.name as titleName FROM NB\ReportBundle\Entity\WorkUnit workunit LEFT JOIN workunit.worktype worktype LEFT JOIN workunit.owner owner LEFT JOIN owner.custom_title title WHERE workunit.contract = :contractId AND workunit.relatedEntityId = :clientId AND workunit.relatedEntityClass = :relatedClass ORDER BY workunit.startDate ASC";
		$this->query->setDql($dql);
		
		$info = ContractContainer::info(self::REPORT_ID);
		$this->query->setParameter('relatedClass', $info['target_class'])
			  ->setParameter('clientId', $request->get('clientId'))
			  ->setParameter('contractId', $request->get('id'))
			  ;
		$result = $this->query->getArrayResult();

		return $this->buildExcelObject($result, $phpexcel, $client);
	}
}