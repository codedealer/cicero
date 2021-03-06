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
    /*TODO: refactor that
    */

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

        $request = $this->getRequest();
        $em = $this->getDoctrine()->getManager();

        $refId = $request->get('ref', false);
        if($refId){
            //prepopulate
            try{
                $_workunit = $this->getDoctrine()->getRepository('NBReportBundle:WorkUnit')->find($refId);
            }
            catch(\Exception $e){
                $refId = false;
            }
            if($refId){
                $workunit->setStartDate($_workunit->getEndDate());
                $workunit->setClient($_workunit->getClient());
                $workunit->setWorktype($_workunit->getWorktype());
                $workunit->setContract($_workunit->getContract());
            }
        }

        $form = $this->createForm('nb_workunit_relation_form', $workunit);
        
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

            	if(!($workunit->getEndDate() instanceof \DateTime)
                   || ($workunit->getStartDate() >= $workunit->getEndDate())){
            		$correctEndDate = clone $workunit->getStartDate();
            		$workunit->setEndDate($correctEndDate->modify('+1 hour'));
            	}

            	
            	$em->persist($workunit);
            	$em->flush();

            	$id = $workunit->getId();

            	$this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.entity.controller.message.saved')
                );

            	if(!$related)
	                return $this->get('oro_ui.router')->redirectAfterSave(
	                    ['route' => 'nb_workunit_create', 'parameters' => ['ref'=> $id]],
	                    ['route' => 'nb_workunit_view', 'parameters' => ['id' => $id]]
	                );
	            else{
                    $redirect = $this->createRedirect($workunit);

	            	return $this->get('oro_ui.router')->redirectAfterSave(
	                    [
	                    	'route' => $redirect[0]['route'], 
	                    	'parameters' => $redirect[0]['params']
	                    ],
	                    [
	                    	'route' => $redirect[1]['route'], 
	                    	'parameters' => $redirect[1]['params']
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

    	return [
    		'id' => $relationId,
    		'class' => $relationClass,
    		];
    }

    protected function createRedirect($workunit){
        $normilizedName = str_replace('\\', '', strtolower($workunit->getRelatedEntityClass()));
        $redirect = [];
        switch ($normilizedName) {
            case 'extendentityregistration':
                $redirect = [0=>[
                    'route' => 'oro_entity_view',
                    'params' => ['
                        entityName' => 'Extend_Entity_Registration', 
                        'id' => $workunit->getRelatedEntityId()
                        ]
                        ],
                        1=>[
                    'route' => 'oro_entity_view',
                    'params' => ['
                        entityName' => 'Extend_Entity_Registration', 
                        'id' => $workunit->getRelatedEntityId()
                        ]
                        ]
                    ];
                break;
            case 'extendentitycourt':
                $redirect = [0=>[
                    'route' => 'nb_feed_create',
                    'params' => ['targetId' => $workunit->getRelatedEntityId(), 'ref' => $workunit->getId()]
                    ],
                    1=>[
                    'route' => 'nb_feed_create',
                    'params' => ['targetId' => $workunit->getRelatedEntityId()]
                    ],
                ];
                break;
            case 'extendentityproject':
                $redirect = [0=>[
                    'route' => 'nb_workunit_create',
                    'params' => ['ref' => $workunit->getId()]
                    ],
                    1=>[
                    'route' => 'nb_workunit_index',
                    'params' => []
                    ],
                ];
                break;
            default:
                throw new \RuntimeException('WorkUnit hook for '. $normilizedName . ' not implemented');
                break;
        }

        return $redirect;
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
            	if(!($workunit->getEndDate() instanceof \DateTime)
                   || ($workunit->getStartDate() >= $workunit->getEndDate())){
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
