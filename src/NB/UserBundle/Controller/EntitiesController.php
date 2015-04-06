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
        $em->registerAction('Extend_Entity_worktype', 'update', 'NBUserBundle:WorkType:update')
           ->registerAction('Extend_Entity_worktype', 'view', 'NBUserBundle:WorkType:view')
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
        $entityClass = $this->get('oro_entity.routing_helper')->decodeClassName($entityName);

        if (!class_exists($entityClass)) {
            throw $this->createNotFoundException();
        }

        $this->checkAccess('VIEW', $entityClass);

        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->get('oro_entity_config.provider.entity');

        if (!$entityConfigProvider->hasConfig($entityClass)) {
            throw $this->createNotFoundException();
        }

        $entityConfig = $entityConfigProvider->getConfig($entityClass);

        if($entityConfig->has('collaboration') && $entityConfig->get('collaboration')){
            return $this->render('NBUserBundle:Entities:index.html.twig', [
                'entity_name'  => $entityName,
                'entity_class' => $entityClass,
                'label'        => $entityConfig->get('label'),
                'plural_label' => $entityConfig->get('plural_label')
                ]);
        }

        return [
            'entity_name'  => $entityName,
            'entity_class' => $entityClass,
            'label'        => $entityConfig->get('label'),
            'plural_label' => $entityConfig->get('plural_label')
        ];
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
    		//return parent::updateAction($request, $entityName, $id);
            $entityClass = $this->get('oro_entity.routing_helper')->decodeClassName($entityName);

            if (!class_exists($entityClass)) {
                throw $this->createNotFoundException();
            }

            $this->checkAccess(!$id ? 'CREATE' : 'EDIT', $entityClass);

            /** @var OroEntityManager $em */
            $em = $this->getDoctrine()->getManager();

            /** @var ConfigProvider $entityConfigProvider */
            $entityConfigProvider = $this->get('oro_entity_config.provider.entity');
            $entityConfig         = $entityConfigProvider->getConfig($entityClass);

            $entityRepository = $em->getRepository($entityClass);

            $record = !$id ? new $entityClass : $entityRepository->find($id);

            $form = $this->createForm(
                'custom_entity_type',
                $record,
                array(
                    'data_class'   => $entityClass,
                    'block_config' => array(
                        'general' => array(
                            'title' => 'General'
                        )
                    ),
                )
            );

            if ($request->getMethod() == 'POST') {
                $form->submit($request);

                if ($form->isValid()) {
                    
                    $em->persist($record);
                    $em->flush();

                    // if($entityConfig->has('collaboration') && $entityConfig->get('collaboration'))
                        //$this->get('nb.object_acl_manager')->updateOperatorPermissions($record);

                    $id = $record->getId();

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->get('translator')->trans('oro.entity.controller.message.saved')
                    );

                    return $this->get('oro_ui.router')->redirectAfterSave(
                        ['route' => 'oro_entity_update', 'parameters' => ['entityName' => $entityName, 'id'=> $id]],
                        ['route' => 'oro_entity_view', 'parameters' => ['entityName' => $entityName, 'id' => $id]]
                    );
                }
            }

            return [
                'entity'        => $record,
                'entity_name'   => $entityName,
                'entity_config' => $entityConfig,
                'entity_class'  => $entityClass,
                'form'          => $form->createView(),
            ];

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
