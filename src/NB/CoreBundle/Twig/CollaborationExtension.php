<?php
namespace NB\CoreBundle\Twig;

use NB\ReportBundle\Model\ContractContainer;

class CollaborationExtension extends \Twig_Extension
{
	public function getName(){
		return 'collaboration_extension';
	}

	public function getFunctions(){
		return [
			new \Twig_SimpleFunction('is_collaborate', [$this, 'isCollaborate']),
			new \Twig_SimpleFunction('contract_transform', [$this, 'contractTransform'])
		];
	}

	public function isCollaborate($entity, $user){
		if(!is_object($entity) 
			|| !method_exists($entity, 'getUsers') 
			|| !method_exists($entity, 'getOwner')
			)
			return false;
		return $entity->getOwner() == $user || $entity->getUsers()->contains($user);
	}

	public function contractTransform($value){
		return ContractContainer::label($value);
	}
}
?>