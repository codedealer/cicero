<?php
namespace NB\CoreBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class LightSearchHandler extends SearchHandler
{
	public function __construct($entityName, $properties, $securityContext){
		$this->securityContext = $securityContext;
		$this->entityName = $entityName;
        $this->properties = $properties;
	}

	protected function searchEntities($search, $firstResult, $maxResults){
		$search = trim($search);

		$query = $this->entityRepository->createQueryBuilder('q');

		$searchQuery = [];
		$searchParams = [];
		foreach ($this->properties as $key => $value) {
			$searchQuery[] = 'q.' . $value . ' LIKE ?' . $key;
			$searchParams[$key] = '%' . $search . '%';
		}
		$query->where(join('OR ', $searchQuery))
			  ->setParameters($searchParams)
			  ;
		if($this->securityContext->isGranted('ROLE_USER')){
			$id = $this->securityContext->getToken()->getUser()->getId();
			$query->andWhere('q.owner = :userId')
			      ->setParameter('userId', $id)
			      ;
		}

		$query->setFirstResult($firstResult)
			  ->setMaxResults($maxResults)
			  ;

		return $query->getQuery()->getResult();
	}

	protected function checkAllDependenciesInjected(){
		if(!$this->entityRepository)
			throw new \RuntimeException('Search handler is not configured');
	}
}
?>