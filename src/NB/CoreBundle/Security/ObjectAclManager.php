<?php
namespace NB\CoreBundle\Security;

use Oro\Bundle\SecurityBundle\Acl\Persistance\AclManager;

class ObjectAclManager
{
	protected $manager, $logger;

	public function __construct($manager, $logger){
		$this->manager = $manager;
		$this->logger = $logger;
	}

	public function setOperatorPermissions($object, $user = null){
		if(!method_exists($object, 'getUsers'))
			throw new \RuntimeException('Object with collaboration feature is expected to have field "users"');
		
		$this->logger->notice('Setting permissions');

		$oid = $this->manager->getOid($object);
		$builder = $this->manager->getMaskBuilder($oid);
		$mask = $builder->add('VIEW_LOCAL')->get();
		if($user === null){
			foreach ($object->getUsers() as $user) {
				$sid = $this->manager->getSid($user);
				$this->manager->setPermission($sid, $oid, $mask);
			}
		}
		else{
			$sid = $this->manager->getSid($user);
			$this->manager->setPermission($sid, $oid, $mask);
		}

		$this->manager->flush();
	}

	public function removeOperatorPermissions($object, $user = null){
		if(!method_exists($object, 'getUsers'))
			throw new \RuntimeException('Object with collaboration feature is expected to have field "users"');
		
		$this->logger->notice('Deleting permissions');
		
		$oid = $this->manager->getOid($object);
		if($user === null){
			foreach ($object->getUsers() as $user) {
				$sid = $this->manager->getSid($user);
				$this->manager->deleteAllPermissions($sid, $oid);
			}
		}
		else{
			$sid = $this->manager->getSid($user);
			$this->manager->deleteAllPermissions($sid, $oid);
		}
		
		$this->manager->flush();
	}
}
?>