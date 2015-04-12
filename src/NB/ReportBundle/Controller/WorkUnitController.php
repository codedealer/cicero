<?php

namespace NB\ReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use NB\ReportBundle\Entity\WorkUnit;

/**
 * @Route("/work")
 */
class WorkUnitController extends Controller
{
    /**
     * @Route(
     *      
     *      name="nb_workunit_index"
     *      
     * )
     * @Acl(
     *      id="nb_workunit_view",
     *      type="entity",
     *      class="NBReportBundle:WorkUnit",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('nb_workunit.entity.class')
        ];
    }

    /**
     * @Route("/create", name="nb_workunit_create")
     * @Acl(
     *      id="nb_workunit_create",
     *      type="entity",
     *      class="NBReportBundle:WorkUnit",
     *      permission="CREATE"
     * )
     * @Template
     */
    public function createAction()
    {
        $workunit = new WorkUnit();

        $form = $this->createForm('nb_workunit_form', $workunit);

        return [
        	'entity' => $workunit,
        	'form' => $form->createView(),
        	'formAction' => $this->get('oro_entity.routing_helper')
            ->generateUrlByRequest('nb_workunit_create', $this->getRequest())
        ];
    }

    /**
     * @Route("/view/{id}", name="nb_workunit_view", requirements={"id"="\d+"})
     * @AclAncestor("nb_workunit_view")
     * @Template
     */
    public function viewAction(WorkUnit $workunit)
    {
        return array('entity' => $workunit);
    }

    /**
     * @Route("/update/{id}", name="nb_workunit_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="nb_workunit_update",
     *      type="entity",
     *      class="NBReportBundle:WorkUnit",
     *      permission="EDIT"
     * )
     */
    public function updateAction(WorkUnit $workunit)
    {
        return false;
    }
}
