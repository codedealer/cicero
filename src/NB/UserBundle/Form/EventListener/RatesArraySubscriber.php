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
			FormEvents::POST_SUBMIT => 'postSubmit'
			 ];
	}

	public function preSetData(FormEvent $event){
		//getData
		
		$form = $event->getForm();
		$entityClass = '\Extend\Entity\titles';
		$repo = $this->em->getRepository($entityClass);
		$titles = $repo->findAll();

		foreach ($titles as $key => $title) {
			$form->add('title' . (string) $title->getId(), 'money', [
				'label' => $title->getName(), 
				'currency' => 'RUB', 
				'mapped' => false,
				'required' => false
				]);
		}
	}

	public function postSubmit(FormEvent $event){
		$form = $event->getForm();
		$worktype = $event->getData();

		if($worktype->getIsHourly() === true){

			//$this->logger->notice('Gotta create workrates n shit');
			//$this->logger->notice('Unmapped field : ' . (string) $form->get('title1')->getName());
			/*
			$entityClass = '\Extend\Entity\titles';
			$repo = $this->em->getRepository($entityClass);
			$titleIds = $this->getTitleIds($form);
			$wagerates = [];

			foreach ($titleIds as $titleId) {
				$wagerate = new wagerate();
				$title = $repo->find($titleId);
				$key = 'title' . (string) $titleId;
				$wagerate->setTitles($title)->setRate(
					$form->get($key)->getData()
					);

				$this->em->persist($wagerate);

				$wagerates[] = $wagerate;
				//$form->remove($key);
			}
			if(count($wagerates)){
				$this->logger->notice('saving wagerates array');
				$this->em->flush();
			
				foreach ($wagerates as $key => $value) {
					$worktype->addWorkrates($value);
				}
			}*/
		}
		else{
			$titleIds = $this->getTitleIds($form);
			foreach ($titleIds as $titleId) {
				$key = 'title' . (string) $titleId;
				//$form->remove($key);
			}
		}
	}

	private function getTitleIds($form){
		$tids = [];
		foreach ($form->all() as $key => $field) {
			if(strpos($field->getName(), 'title') !== false)
				$tids[] = (int) ltrim($field->getName(),'title');
		}

		return $tids;
	}
}
?>