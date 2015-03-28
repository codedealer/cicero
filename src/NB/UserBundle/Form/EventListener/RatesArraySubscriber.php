<?php
namespace NB\UserBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Extend\Entity\wagerate;

use Doctrine\ORM\EntityManager;

class RatesArraySubscriber implements EventSubscriberInterface
{
	private $em;

	public function __construct(EntityManager $em){
		$this->em = $em;
	}

	public static function getSubscribedEvents(){
		return [
			FormEvents::PRE_SET_DATA => 'preSetData',
			//FormEvents::PRE_SUBMIT => 'postSubmit'
			 ];
	}

	public function preSetData(FormEvent $event){
		//getData
		$form = $event->getForm();/*
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
		}*/
	}

	public function postSubmit(FormEvent $event){
		$form = $event->getForm();
		$worktype = $event->getData();

		if($form->get('isHourly')->getData() === true){
			$entityClass = '\Extend\Entity\titles';
			$repo = $this->em->getRepository($entityClass);
			$titleIds = $this->getTitleIds($form);
			$worktypeId = $worktype->getId();
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
				$form->remove($key);
			}
			if(count($wagerates)){
				$this->em->flush();
				$workrates = $form->get('workrates')->getData();
				foreach ($wagerates as $key => $value) {
					$workrates->add($value);
				}
			}
		}
		else{
			$titleIds = $this->getTitleIds($form);
			foreach ($titleIds as $titleId) {
				$key = 'title' . (string) $titleId;
				$form->remove($key);
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