<?php
namespace NB\CoreBundle\Autocomplete;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\Form\Handler\EntitySelectHandler;

class EntitySelectInterceptor extends EntitySelectHandler
{
	protected $securityContext;

	public function __construct($securityContext){
		$this->securityContext = $securityContext;
		parent::__construct();
	}

	public function search($query, $page, $perPage, $searchById = false){
		list($query, $targetEntity, $targetField) = explode(',', $query);
        $this->initForEntity($targetEntity, $targetField);
        if($targetEntity != 'Extend_Entity_client')
        	return parent::search($query, $page, $perPage, $searchById);
        else
        	return $this->hook($query, $page, $perPage, $searchById);
	}

	public function initForEntity($entityName, $targetField)
    {
        $this->entityName = str_replace('_', '\\', $entityName);
        $this->initDoctrinePropertiesByEntityManager($this->registry->getManagerForClass($this->entityName));

        $this->properties   = array_unique(array_merge($this->defaultPropertySet, [$targetField]));
        $this->currentField = $targetField;
    }

    protected function hook($query, $page, $perPage, $searchById){
    	$csh = new CollaborationSearchHandler('Extend\Entity\client', ['name'], $this->securityContext);
    	$csh->initDoctrinePropertiesByManagerRegistry($this->registry);

    	return $csh->search($query, $page, $perPage, $searchById);
    }
}
?>