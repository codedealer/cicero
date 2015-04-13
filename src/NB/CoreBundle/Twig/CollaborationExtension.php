<?php
namespace NB\CoreBundle\Twig;

class CollaborationExtension extends \Twig_Extension
{
	public function getName(){
		return 'collaboration_extension';
	}

	public function getFunctions(){
		return [
			new \Twig_SimpleFunction('is_collaborate', [$this, 'isCollaborate'])
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
}
?>