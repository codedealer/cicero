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
	ContractContainer::HOURLY => 'grid-report-hourly',
	ContractContainer::PROJECT => 'grid-report-project'
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

		$form = $this->createFormBuilder([])
				->add('client', 'oro_jqueryselect2_hidden', [
					'autocomplete_alias' => 'clients',
					'label' => 'Клиент',
					'required' => true
				])
				->getForm()
				;
		$request = $this->getRequest();
		if($request->isMethod('POST')){
			$form->bind($request);

			$client = $form->get('client')->getData();

			return new RedirectResponse($this->get('router')->generate('nb_report_view', 
				['id' => $id, 'client' => $client->getId()]));
		}

		return [
			'report_id' => $id,
			'report_info' => ContractContainer::info($id),
			'formAction' => $this->get('router')->generate('nb_report_index', ['id' => $id]),
            'form' => $form->createView()
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

		$client = $this->get('oro_entity.routing_helper')
				->getEntity('Extend\Entity\client', $this->getRequest()->get('client'));

		return [
			'contract_id' => $id,
			'report_info' => ContractContainer::info($id),
			'grid_name' => $this->grids[$id],
			'client' => $this->getRequest()->get('client'),
			'client_entity' => $client
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

		$client = $this->get('oro_entity.routing_helper')
				->getEntity('Extend\Entity\client', $clientId);

		$report = $this->get('nb_report.report_factory')->getReport($id);

		return [
			'contract_id' => $id,
			'report_info' => ContractContainer::info($id),
			'client' => $client,
			'report' => $report->getReportTable($this->getRequest())
		];
	}
}