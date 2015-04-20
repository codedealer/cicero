<?php
namespace NB\CourtBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FeedType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options){
		
		$builder
			->add('subject', 'text', ['required' => true, 'label' => 'Тема'])
			->add('result', 'text', ['required' => false, 'label' => 'Результат', 'mapped'=>false])
			->add('description', 'textarea', ['required' => false, 'label' => 'Комментарий'])
			->add('targetId', 'hidden', ['required' => true, 'mapped'=>false])
			->add('createEvent', 'checkbox', ['required' => false, 'label' => 'Привязать к дате', 'mapped' => false])
			->add('start', 'oro_datetime', [
					'required' => false,
					'label' => 'Начало',
					'mapped' => false
				])
			->add('end', 'oro_datetime', [
					'required' => false,
					'label' => 'Конец',
					'mapped' => false
				])
			->add('allDay', 'checkbox', [
					'required' => false, 
					'label' => 'Событие на весь день', 
					'mapped' => false]
				)
			;
		
	}

	public function getName(){
		return 'nb_feed_form';
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'NB\CourtBundle\Entity\Feed',
                'intention' => 'feed',
                'cascade_validation' => true
            ]
        );
    }
}