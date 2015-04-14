<?php
namespace NB\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;

class ProjectController extends BaseController
{
	
    public function updateAction(Request $request, $entityName, $id){

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
            'nb_project_type',
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

       
        return $this->render('OroEntityBundle:Entities:update.html.twig', [
            'entity'        => $record,
            'entity_name'   => $entityName,
            'entity_config' => $entityConfig,
            'entity_class'  => $entityClass,
            'form'          => $form->createView(),
        ]);
    }

	protected function checkAccess($permission, $entityName)
    {
        $securityFacade = $this->get('oro_security.security_facade');
    	$isGranted = $securityFacade->isGranted($permission, 'entity: ' . $entityName);
    	if(!$isGranted)
    		throw new AccessDeniedException('Доступ запрещен.');
    }
}
?>