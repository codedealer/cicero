<?php
namespace NB\ReportBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WorkUnitRelationType extends WorkUnitType
{
	public function buildForm(FormBuilderInterface $builder, array $options){
		parent::buildForm($builder, $options);

		$builder
			->add('relation', 'choice', [
				'required' => false,
				'mapped' => false,
				'label' => 'Связать с сущностью',
				'empty_value' => 'Выбрать сущность...',
				'choices' => [
					'registration' => 'Регистрация',
					'court'		   => 'Судебное дело'
				]
			])
			->add('registration', 'oro_jqueryselect2_hidden', [
			'autocomplete_alias' => 'registrations',
			'mapped' => false,
			'label' => 'Регистрация'
			])
			->add('court', 'oro_jqueryselect2_hidden', [
			'autocomplete_alias' => 'courts',
			'mapped' => false,
			'label' => 'Судебное дело'
			])
			;
	}

	public function getName(){
		return 'nb_workunit_relation_form';
	}
}
?>