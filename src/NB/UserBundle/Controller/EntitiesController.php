<?php

namespace NB\UserBundle\Controller;

//use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\EntityBundle\Controller\EntitiesController as Controller;

use NB\UserBundle\Controller\WorkTypeController;
use NB\UserBundle\Model\InitializableControllerInterface;

class EntitiesController extends Controller implements InitializableControllerInterface
{

    public function init(){
        $em = $this->get('nb.entity_action_mapper');
        $em->registerAction('Extend_Entity_worktype', 'update', 'NBUserBundle:WorkType:update');
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
        $this->get('logger')->notice('bobobo' . $action);
        if(false !== $action)
            return $this->forward($action, [
                'request' => $request,
                'entityName' => $entityName,
                'id' => $id
                ]);
        else
    		return parent::updateAction($request, $entityName, $id);
    }
}
