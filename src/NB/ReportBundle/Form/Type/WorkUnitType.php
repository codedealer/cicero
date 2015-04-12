<?php
namespace NB\ReportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WorkUnitType extends AbstractType
{
	protected $securityFacade; 

	public function __construct($securityFacade){
		$this->securityFacade = $securityFacade;
	}

	public function buildForm(FormBuilderInterface $builder, array $options){
		if($this->securityFacade->isGranted('nb_workunit_assign'))
			$builder->add('owner', 'oro_jqueryselect2_hidden', [
				'autocomplete_alias' => 'users'
				]);
		$builder
			->add('subject', 'text', ['required' => true, 'label' => 'Комментарий'])
			->add('startDate', 'oro_datetime', ['required' => true, 'label' => 'Начало'])
			->add('endDate', 'oro_datetime', ['required' => true, 'label' => 'Конец'])
			->add('worktype', 'translatable_entity', [
					'required' => true,
					'class' => '\Extend\Entity\worktype',
					'label' => 'Тип работы'
					])
			->add('client', 'oro_jqueryselect2_hidden', [
					'alias' => 'clients'
				])
			;
		
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'NB\ReportBundle\Entity\WorkUnit',
                'intention' => 'workunit',
                'cascade_validation' => true
            ]
        );
    }

    public function getName(){
    	return 'nb_workunit_form';
    }
}
?>