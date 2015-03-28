<?php
namespace NB\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use NB\UserBundle\Form\EventListener\RatesArraySubscriber;

class WorkTypeFormType extends AbstractType
{
	private $em;

	public function getName(){
		return 'nb_work_type';
	}

	public function __construct($em){
		$this->em = $em;
	}

	public function buildForm(FormBuilderInterface $builder, array $options){
		$builder
			->add('name', 'text', ['required' => true])
			->add('isHourly', 'checkbox', ['required' => false])
			->add('flatrate', 'money', ['required' => false, 'currency' => 'RUB'])
			->add('workrates', 'collection', ['allow_add' => true])
			;
		$builder->addEventSubscriber(new RatesArraySubscriber($this->em));
		
		$entityClass = '\Extend\Entity\titles';
		$repo = $this->em->getRepository($entityClass);
		$titles = $repo->findAll();

		foreach ($titles as $key => $title) {
			$builder->add('title' . (string) $title->getId(), 'money', [
				'label' => $title->getName(), 
				'currency' => 'RUB', 
				'mapped' => false,
				'required' => false
				]);
		}
	}
}
?>