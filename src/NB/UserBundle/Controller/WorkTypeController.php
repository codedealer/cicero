<?php
namespace NB\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use \Extend\Entity\wagerate;
class WorkTypeController extends Controller
{
	protected function checkAccess($permission, $entityName)
    {
    	$securityFacade = $this->get('oro_security.security_facade');
    	$isGranted = $securityFacade->isGranted($permission, 'entity: ' . $entityName);
    	if(!$isGranted)
    		throw new AccessDeniedException('Доступ запрещен.');
    }

    private function getTitleIds($form){
        $tids = [];
        foreach ($form->all() as $key => $field) {
            if(strpos($field->getName(), 'title') !== false)
                $tids[] = (int) ltrim($field->getName(),'title');
        }

        return $tids;
    }

    private function getExistingTitleIds($data){
        if(!$data->getWorkrates()) return [];
        $titles = [];
        $collection = $data->getWorkrates();
        $collectionKeys = $collection->getKeys();
        foreach ($collectionKeys as $workrateKey) {
            $titles[$collection->get($workrateKey)->getTitles()->getId()] = $workrateKey;
        }
        return $titles;
    }

	public function updateAction($request, $entityName, $id){
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
                
                if($record->getIsHourly() === true){
                    $entityClass = '\Extend\Entity\titles';
                    $repo = $em->getRepository($entityClass);
                    $titleIds = $this->getTitleIds($form);
                    $existingTitleIds = $this->getExistingTitleIds($record);
                    $wagerates = [];

                    foreach ($titleIds as $titleId) {
                        $key = 'title' . (string) $titleId;

                        if(array_key_exists($titleId, $existingTitleIds)){
                            $record->getWorkrates()
                                   ->get($existingTitleIds[$titleId])
                                   ->setRate($form->get($key)->getData())
                                   ;
                        }
                        else{
                            $wagerate = new wagerate();
                            $title = $repo->find($titleId);
                            $wagerate->setTitles($title)->setRate(
                                $form->get($key)->getData()
                                );
                            $em->persist($wagerate);

                            $wagerates[] = $wagerate;
                        }
                    }
                    if(count($wagerates)){
                        
                        $em->flush();
                    
                        foreach ($wagerates as $key => $value) {
                            $record->addWorkrates($value);
                        }
                    }
                }
                
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

	public function viewAction($entityName, $id){
		$entityClass = 'Extend\Entity\worktype';
		
		if(!class_exists($entityClass))
			throw $this->createNotFoundException();

		$this->checkAccess('VIEW', $entityClass);

		$record = $this->getDoctrine()->getManager()->getRepository($entityClass)->find($id);
		$entityConfigProvider = $this->get('oro_entity_config.provider.entity');

		if(!$record)
			throw $this->createNotFoundException();

		return $this->render('NBUserBundle:WorkType:view.html.twig', [
			'entity_name' => $entityName,
			'entity' => $record,
			'id' => $id,
			'entity_config' => $entityConfigProvider->getConfig($entityClass),
			'entity_class' => $entityClass
			]);
	}
}
?>