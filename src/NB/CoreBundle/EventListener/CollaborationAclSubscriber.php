<?php
namespace NB\CoreBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\Security\Core\User\UserInterface;

class CollaborationAclSubscriber implements EventSubscriber
{
	private $serviceContainer;

	public function __construct($serviceContainer){
		$this->serviceContainer = $serviceContainer;
	}

	public function getSubscribedEvents(){
		return [
			'postPersist',
			'preUpdate',
			'preRemove'
		];
	}

	public function postPersist(LifecycleEventArgs $args){
		$entity = $args->getEntity();
		if(!$this->hasCollaborationFeature($entity))
			return;

		$this->serviceContainer->get('nb.object_acl_manager')->setOperatorPermissions($entity);
	}

	public function preRemove(LifecycleEventArgs $args){
		$entity = $args->getEntity();
		if(!$this->hasCollaborationFeature($entity))
			return;

		$this->serviceContainer->get('nb.object_acl_manager')->removeOperatorPermissions($entity);
	}

	public function preUpdate(LifecycleEventArgs $args){
		$entity = $args->getEntity();
		$uow = $args->getEntityManager()->getUnitOfWork();
		if(!$this->hasCollaborationFeature($entity) || !method_exists($entity, 'getUsers'))
			return;
		
		$collectionHash = spl_object_hash($entity->getUsers());
		$collectionUpdates = $uow->getScheduledCollectionUpdates();
		$aclManager = $this->serviceContainer->get('nb.object_acl_manager');
		
		foreach ($collectionUpdates as $hash => $persisntentCollection) {
			if($hash != $collectionHash)
				continue;

			$insertionDiff = $persisntentCollection->getInsertDiff();
			$deletionDiff = $persisntentCollection->getDeleteDiff();
			foreach ($insertionDiff as $key => $user) {
				if($user instanceof UserInterface)
					$aclManager->setOperatorPermissions($entity, $user);
				else
					throw new \RuntimeException('object for collaboration should be of UserInterface');
			}
			foreach ($deletionDiff as $key => $user) {
				if($user instanceof UserInterface)
					$aclManager->removeOperatorPermissions($entity, $user);
				else
					throw new \RuntimeException('object for collaboration should be of UserInterface');
			}
		}
		
	}

	private function hasCollaborationFeature($entity){
		$class = ClassUtils::getClass($entity);
		$entityConfigProvider = $this->serviceContainer->get('oro_entity_config.provider.entity');
		if(!$entityConfigProvider->hasConfig($class))
			return false;

		$config = $entityConfigProvider->getConfig($class);
		if(!$config->has('collaboration') || !$config->get('collaboration'))
			return false;

		return true;
	}
}