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
		$originalQuery = $query;
		list($query, $targetEntity, $targetField) = explode(',', $query);
		if($targetEntity != 'Extend_Entity_client')
			return parent::search($originalQuery, $page, $perPage, $searchById);
        
        $this->initForEntity($targetEntity, $targetField);
       
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