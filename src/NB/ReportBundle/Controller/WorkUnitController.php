<?php

namespace NB\ReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use NB\ReportBundle\Entity\WorkUnit;

/**
 * @Route("/work")
 */
class WorkUnitController extends Controller
{
    protected $routeMapCreator = [
    	'extendentityregistration' => function($workunit){
            return [
            'route' => 'oro_entity_view',
            'params' => ['entityName' => 'Extend_Entity_Registration', 'id' => $workunit->getRelatedEntityId()]
            ]
        },
        'extendentitycourt' => function($workunit){
            return [
                'route' => 'nb_feed_create',
                'params' => ['targetId' => $workunit->getId()]
            ]
        } ,
    ];

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

        $form = $this->createForm('nb_workunit_relation_form', $workunit);
        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            
            if ($form->isValid()) {
            	$related = false;
            	$rel = $form->get('relation')->getData();
            	if($rel && $form->get($rel)->getData()){
            		$relationData = $this->getRelationData($form->get($rel)->getData());
            		$related = true;
            		$workunit->setRelatedEntityId($relationData['id']);
            		$workunit->setRelatedEntityClass($relationData['class']);
            	}

            	if($workunit->getStartDate() >= $workunit->getEndDate()){
            		$correctEndDate = clone $workunit->getStartDate();
            		$workunit->setEndDate($correctEndDate->modify('+1 hour'));
            	}

            	$em = $this->getDoctrine()->getManager();
            	$em->persist($workunit);
            	$em->flush();

            	$id = $workunit->getId();

            	$this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.entity.controller.message.saved')
                );

            	if(!$related)
	                return $this->get('oro_ui.router')->redirectAfterSave(
	                    ['route' => 'nb_workunit_update', 'parameters' => ['id'=> $id]],
	                    ['route' => 'nb_workunit_view', 'parameters' => ['id' => $id]]
	                );
	            else{
                    $redirect = $this->createRedirect($workunit);
	            	return $this->get('oro_ui.router')->redirectAfterSave(
	                    [
	                    	'route' => $redirect['route'], 
	                    	'parameters' => $redirect['params']
	                    ],
	                    [
	                    	'route' => $redirect['route'], 
	                    	'parameters' => $redirect['params']
	                    ]
	                );
                }
            }
        }

        return [
        	'entity' => $workunit,
        	'form' => $form->createView(),
        	'formAction' => $this->get('oro_entity.routing_helper')
            ->generateUrlByRequest('nb_workunit_create', $this->getRequest())
        ];
    }

    protected function getRelationData($entity){
    	$relationId = $entity->getId();
    	$relationClass = ClassUtils::getClass($entity);
    	//$redirect = $this->redirectMap[str_replace('\\', '', strtolower($relationClass))];

    	return [
    		'id' => $relationId,
    		'class' => $relationClass,
    		];
    }

    protected function createRedirect($workunit){
        $normilizedName = str_replace('\\', '', strtolower($workunit->getRelatedEntityClass()));

        $callable = $this->routeMapCreator[$normilizedName];

        return $callable($workunit);
    }

    /**
     * @Route("/view/{id}", name="nb_workunit_view", requirements={"id"="\d+"})
     * @AclAncestor("nb_workunit_view")
     * @Template
     */
    public function viewAction(WorkUnit $workunit)
    {
        if($workunit->getRelatedEntityId() && $workunit->getRelatedEntityClass()){
        	$entityName = $this->get('oro_entity.routing_helper')
        				->decodeClassName($workunit->getRelatedEntityClass());
        	return [
        	'entity' => $workunit,
        	'relatedPath' => $this->get('router')->generate('oro_entity_view', [
        			'entityName' => $entityName,
        			'id' => $workunit->getRelatedEntityId()
        		])
        	];
        }
        	

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
        $em = $this->getDoctrine()->getManager();

        $id = $workunit->getId();
        
        $form = $this->createForm('nb_workunit_form', $workunit);
        $request = $this->getRequest();

        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            
            if ($form->isValid()) {
            	if($workunit->getStartDate() >= $workunit->getEndDate()){
            		$correctEndDate = clone $workunit->getStartDate();
            		$workunit->setEndDate($correctEndDate->modify('+1 hour'));
            	}
       
            	$em->persist($workunit);
            	$em->flush();

            	$this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.entity.controller.message.saved')
                );

                return $this->get('oro_ui.router')->redirectAfterSave(
                    ['route' => 'nb_workunit_update', 'parameters' => ['id'=> $id]],
                    ['route' => 'nb_workunit_view', 'parameters' => ['id' => $id]]
                );
            }
        }

        return [
        	'entity' => $workunit,
        	'form' => $form->createView(),
        	'formAction' => $this->get('router')->generate('nb_workunit_update', ['id' => $id])
        ];
    }

    /**
     * @Route("/widget/info/{id}", name="nb_workunit_widget_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("nb_workunit_view")
     */
    public function infoAction(WorkUnit $entity)
    {
        return array('entity' => $entity);
    }
}
