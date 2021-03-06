<?php
namespace NB\CoreBundle\EventListener;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use ORO\Bundle\DataGridBundle\Datasource\ORM\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;

class CollaborationDatagridListener
{
	private $configProvider, $sc, $aclHelper, $aclVoter;

	public function __construct($configProvider, $securityContext, $aclVoter, $aclHelper){
		$this->configProvider = $configProvider;
		$this->sc = $securityContext;
		$this->aclVoter = $aclVoter;
		$this->aclHelper = $aclHelper;
	}

	public function onBuildAfter(BuildAfter $event){
		$dg = $event->getDatagrid();
		$datasource = $dg->getDatasource();
		$className = $dg->getParameters()->get('class_name');

		if($datasource instanceof OrmDatasource && $this->hasCollaborationFeature($className)){
			//standard acl does not apply on collaboration entities
			//instead user is able to access own objects + ones he manages
			
			//support only local level and system level
			$observer = new OneShotIsGrantedObserver();
			$this->aclVoter->addOneShotIsGrantedObserver($observer);
			$this->sc->isGranted('VIEW', 'entity:' . $className);
			$accessLevel = $observer->getAccessLevel();

			if($accessLevel == AccessLevel::BASIC_LEVEL){
				$queryBuilder = $datasource->getQueryBuilder();
				$id = $this->sc->getToken()->getUser()->getId();
				$queryBuilder->andWhere('ce.owner = :userId OR :userId MEMBER OF ce.users')
							 ->setParameter('userId', $id);
			}
		}
	}
	
	public function onResultBefore(OrmResultBefore $event){
		if($this->hasCollaborationFeature($event->getDatagrid()->getParameters()->get('class_name')))
			return;
		//to standard objects apply default acl
		$config = $event->getDatagrid()->getConfig();
		$this->aclHelper->apply($event->getQuery());
	}

	protected function hasCollaborationFeature($className){
		if(!$this->configProvider->hasConfig($className))
			return false;

		$config = $this->configProvider->getConfig($className);
		if(!$config->has('collaboration') || !$config->get('collaboration'))
			return false;

		return true;
	}
}
?>