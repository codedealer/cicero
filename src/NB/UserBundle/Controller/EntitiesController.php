<?php

namespace NB\UserBundle\Controller;

//use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\EntityBundle\Controller\EntitiesController as Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class EntitiesController extends Controller
{
    protected function checkAccess($permission, $entityName)
    {
    	$securityFacade = $this->get('oro_security.security_facade');
    	$isGranted = $securityFacade->isGranted($permission, 'entity: ' . $entityName);
    	if(!$isGranted)
    		throw new AccessDeniedException('Доступ запрещен.');
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

    	if($entityName !== 'Extend_Entity_worktype')
    		return parent::updateAction($request, $entityName, $id);

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
            'nb_work_type',
            $record
        );

        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            
            if ($form->isValid()) {
                $em->persist($record);
                $em->flush();

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

        return $this->render('NBUserBundle:WorkType:update.html.twig', [
            'entity'        => $record,
            'entity_name'   => $entityName,
            'entity_config' => $entityConfig,
            'entity_class'  => $entityClass,
            'form'          => $form->createView(),
        ]);
    }
}
