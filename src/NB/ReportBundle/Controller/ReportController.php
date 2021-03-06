<?php

namespace NB\ReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use NB\ReportBundle\Model\ContractContainer;

/**
 * @Route("/report")
 */
class ReportController extends Controller
{
	protected $grids = [
	ContractContainer::MONTHLY => 'grid-report-monthly',
	ContractContainer::HOURLY => 'grid-report-monthly',
	ContractContainer::PROJECT => 'grid-report-project',
	ContractContainer::COURT => 'grid-report-project',
	];

	/**
     * @Route(
     * "/{id}",
     * name="nb_report_index"
     *      
     * )
     * @Acl(
     *      id="oro_report_view",
     *      type="entity",
     *      class="OroReportBundle:Report",
     *      permission="VIEW"
     * )
     * @Template
     */
	public function indexAction($id){
		if(!ContractContainer::has($id))
			throw $this->createNotFoundException();

		$report = $this->get('nb_report.report_factory')->getReport($id);

		$formBuilder = $this->createFormBuilder([]);
		$form = $report->getForm($formBuilder);

		$request = $this->getRequest();
		if($request->isMethod('POST')){
			$form->bind($request);

			$client = $report->process($form);

			if(!$client){
				$this->get('session')->getFlashBag()->add(
                    'danger',
                    'Укажите обязательные параметры отчета'
                );
                return $this->redirect($this->get('router')->generate('nb_report_index', ['id' => $id]));
			}

			return $this->get('oro_ui.router')->redirectAfterSave(
				['route' => 'nb_report_default', 'parameters' => ['id' => $id, 'clientId' => $client->getId()]],
				['route' => 'nb_report_view', 'parameters' => ['id' => $id, 'client' => $client->getId()]]
				);
		}

		return [
			'report_id' => $id,
			'report_info' => ContractContainer::info($id),
			'formAction' => $this->get('router')->generate('nb_report_index', ['id' => $id]),
            'form' => $form->createView(),
            'express_definition' => $report->getExpressDefinition(),
		];
	}

	/**
     * @Route(
     * "/{id}/create",
     * name="nb_report_view"
     *      
     * )
     * @Acl(
     *      id="oro_report_view",
     *      type="entity",
     *      class="OroReportBundle:Report",
     *      permission="VIEW"
     * )
     * @Template
     */
	public function viewAction($id){
		if(!ContractContainer::has($id))
			throw $this->createNotFoundException();

		$report_info = ContractContainer::info($id);

		$client = $this->get('oro_entity.routing_helper')
				->getEntity($report_info['target_class'], $this->getRequest()->get('client'));

		return [
			'contract_id' => $id,
			'report_info' => $report_info,
			'grid_name' => $this->grids[$id],
			'client' => $this->getRequest()->get('client'),
			'client_entity' => $client,
		];
	}

	/**
     * @Route(
     * "/{id}/preview/{clientId}",
     * name="nb_report_preview"
     *      
     * )
     * @Acl(
     *      id="oro_report_create",
     *      type="entity",
     *      class="OroReportBundle:Report",
     *      permission="CREATE"
     * )
     * @Template
     */
	public function previewAction($id, $clientId){
		if(!ContractContainer::has($id))
			throw $this->createNotFoundException();

		$report_info = ContractContainer::info($id);
		$client = $this->get('oro_entity.routing_helper')
				->getEntity($report_info['target_class'], $clientId);

		$report = $this->get('nb_report.report_factory')->getReport($id);

		return [
			'contract_id' => $id,
			'report_info' => $report_info,
			'client' => $client,
			'report' => $report->getReportTable($this->getRequest())
		];
	}

	/**
     * @Route(
     * "/{id}/download/{clientId}",
     * name="nb_report_download"
     *      
     * )
     * @Acl(
     *      id="oro_report_create",
     *      type="entity",
     *      class="OroReportBundle:Report",
     *      permission="CREATE"
     * )
     */
	public function downloadAction($id, $clientId){
		if(!ContractContainer::has($id))
			throw $this->createNotFoundException();

		$report_info = ContractContainer::info($id);
		$client = $this->get('oro_entity.routing_helper')
				->getEntity($report_info['target_class'], $clientId);

		$report = $this->get('nb_report.report_factory')->getReport($id);

		$phpexcel = $this->get('phpexcel');
		$e = $report->getExcelObject($this->getRequest(), $phpexcel, $client);

		$writer = $this->get('phpexcel')->createWriter($e, 'Excel5');
		$filename = uniqid();
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $filename . '.xls');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;
	}

	/**
     * @Route(
     * "/{id}/default/{clientId}",
     * name="nb_report_default"
     *      
     * )
     * @Acl(
     *      id="oro_report_create",
     *      type="entity",
     *      class="OroReportBundle:Report",
     *      permission="CREATE"
     * )
     * @Template
     */
	public function defaultAction($id, $clientId){
		if(!ContractContainer::has($id))
			throw $this->createNotFoundException();

		$report_info = ContractContainer::info($id);
		$client = $this->get('oro_entity.routing_helper')
				->getEntity($report_info['target_class'], $clientId);

		$report = $this->get('nb_report.report_factory')->getReport($id);

		$phpexcel = $this->get('phpexcel');
		$e = $report->getExpressExcelObject($this->getRequest(), $phpexcel, $client);

		$writer = $this->get('phpexcel')->createWriter($e, 'Excel5');
		$filename = uniqid();
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $filename . '.xls');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;
	}
}