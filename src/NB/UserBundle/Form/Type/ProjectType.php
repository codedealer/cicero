<?php
namespace NB\UserBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\CustomEntityType;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class ProjectType extends CustomEntityType
{
	public function getName(){
		return 'nb_project_type';
	}

	public function finishView(FormView $view, FormInterface $form, array $options){
		// foreach ($view->children as $key => $child) {
		// 	if($key == 'client'){
		// 		$child->vars['configs']['autocomplete_alias'] = 'clients';
		// 	}
		// }
		parent::finishView($view, $form, $options);
	}
}
?>