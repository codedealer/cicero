<?php
namespace NB\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use NB\UserBundle\Form\EventListener\RatesArraySubscriber;

class WorkTypeFormType extends AbstractType
{
	private $em, $logger;

	public function getName(){
		return 'nb_work_type';
	}

	public function __construct($em, $logger){
		$this->em = $em;
		$this->logger = $logger;
	}

	public function buildForm(FormBuilderInterface $builder, array $options){
		$builder
			->add('name', 'text', ['required' => true])
			->add('isHourly', 'checkbox', ['required' => false])
			->add('flatrate', 'money', ['required' => false, 'currency' => 'RUB'])
			//->add('workrates', 'collection', ['allow_add' => true, ])//'data_class' => 'Extend\Entity\wagerate'])
			;
		$builder->addEventSubscriber(new RatesArraySubscriber($this->em, $this->logger));
	}
}
?>