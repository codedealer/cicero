<?php
namespace NB\CourtBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;

class CourtDoctrineSubscriber implements EventSubscriber
{
	public function getSubscribedEvents(){
		return [
			'prePersist'
			
		];
	}

	//set system calendar for court entity
	public function prePersist(LifecycleEventArgs $args){
		$entity = $args->getEntity();
		if('Extend\Entity\Court' !== ClassUtils::getClass($entity))
			return;

		$calendar = new SystemCalendar();
		$calendar->setName('_' . uniqid())
				 ->setPublic(true)
				 ;
		$args->getEntityManager()->persist($calendar);
		$entity->setCalendar($calendar);
		$entity->setCreatedAt(new \DateTime('now'));
	}
}
?>