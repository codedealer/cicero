<?php

namespace NB\UserBundle\Controller;

//use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\EntityBundle\Controller\EntitiesController as Controller;

use NB\UserBundle\Controller\WorkTypeController;
use NB\UserBundle\Model\InitializableControllerInterface;


class EntitiesController extends Controller implements InitializableControllerInterface
{

    public function init(){
        $em = $this->get('nb.entity_action_mapper');
        $em->registerAction('Extend_Entity_worktype', 'update', 'NBUserBundle:WorkType:update')
           ->registerAction('Extend_Entity_worktype', 'view', 'NBUserBundle:WorkType:view')
           ->registerAction('Extend_Entity_Court', 'index', 'NBCourtBundle:Court:index')
           ->registerAction('Extend_Entity_Court', 'view', 'NBCourtBundle:Court:view')
           ;
    }

    /**
     * Grid of Custom/Extend entity.
     *
     * @param string $entityName
     *
     * @return array
     *
     * @Route(
     *      "/{entityName}",
     *      name="oro_entity_index"
     * )
     * @Template()
     */
    public function indexAction($entityName)
    {
        $action = $this->get('nb.entity_action_mapper')->map($entityName, 'index');
        if(false !== $action)
            return $this->forward($action, [
                'entityName' => $entityName,
                
                ]);
        else
            return parent::indexAction($entityName);
    }

    /**
     * View custom entity instance.
     *
     * @param string $entityName
     * @param string $id
     *
     * @return array
     *
     * @Route(
     *      "/view/{entityName}/item/{id}",
     *      name="oro_entity_view"
     * )
     * @Template()
     */
    public function viewAction($entityName, $id)
    {
        $action = $this->get('nb.entity_action_mapper')->map($entityName, 'view');
        if(false !== $action)
            return $this->forward($action, [
                'entityName' => $entityName,
                'id' => $id
                ]);
        else
            return parent::viewAction($entityName, $id);
    }

    /**
     * @Route(
     *  "update/{entityName}/item/{id}",
     *  name="oro_entity_update",
     *  defaults={"id"=0}
     * )
     * @Template()
     */
    public function updateAction(Request $request, $entityName, $id){

    	$action = $this->get('nb.entity_action_mapper')->map($entityName, 'update');
        
        if(false !== $action)
            return $this->forward($action, [
                'request' => $request,
                'entityName' => $entityName,
                'id' => $id
                ]);
        else{
    		return parent::updateAction($request, $entityName, $id);
        }
    }

    private function checkAccess($permission, $entityName)
    {
        /** @var SecurityFacade $securityFacade */
        $securityFacade = $this->get('oro_security.security_facade');
        $isGranted      = $securityFacade->isGranted($permission, 'entity:' . $entityName);
        if (!$isGranted) {
            throw new AccessDeniedException('Access denied.');
        }
    }

}
