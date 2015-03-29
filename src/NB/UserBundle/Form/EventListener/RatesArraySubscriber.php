<?php
namespace NB\UserBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Extend\Entity\wagerate;

use Doctrine\ORM\EntityManager;

class RatesArraySubscriber implements EventSubscriberInterface
{
	private $em, $logger;

	public function __construct(EntityManager $em, $logger){
		$this->em = $em;
		$this->logger = $logger;
	}

	public static function getSubscribedEvents(){
		return [
			FormEvents::PRE_SET_DATA => 'preSetData',
			
			 ];
	}

	public function preSetData(FormEvent $event){
		$data = $event->getData();
		$existingTitles = array();
		if($data instanceof \Extend\Entity\worktype)
			$existingTitles = $this->getTitles($data);
		
		$form = $event->getForm();
		$entityClass = '\Extend\Entity\titles';
		$repo = $this->em->getRepository($entityClass);
		$titles = $repo->findAll();

		foreach ($titles as $key => $title) {
			$form->add('title' . (string) $title->getId(), 'money', [
				'label' => $title->getName(), 
				'currency' => 'RUB', 
				'mapped' => false,
				'required' => false,
				'data' => array_key_exists($title->getId(), $existingTitles)
							? $existingTitles[$title->getId()]
							: 0
				]);
		}
	}

	private function getTitles($data){
		$titles = [];
		foreach ($data->getWorkrates() as $key => $workrate) {
			$titles[$workrate->getTitles()->getId()] = $workrate->getRate();
		}
		return $titles;
	}
}
?>