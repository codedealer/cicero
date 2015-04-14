<?php
namespace NB\CourtBundle\EventListener;

use Doctrine\ORM\QueryBuilder;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use ORO\Bundle\DataGridBundle\Datasource\ORM\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;

class DatagridListener
{
	protected $router, $sc, $aclVoter;
	const CLASS_NAME = 'Extend\Entity\Court';

	public function __construct(Router $router, $securityContext, $aclVoter){
		$this->router = $router;
		$this->sc = $securityContext;
		$this->aclVoter = $aclVoter;
	}

	public function getLinkProperty($gridName, $keyName, $node)
    {
        if (!isset($node['route'])) {
            return false;
        }

        $router = $this->router;
        $route  = $node['route'];

        return function (ResultRecord $record) use ($router, $route) {
            return $router->generate(
                $route,
                [
                    'entityName' => self::CLASS_NAME,
                    'id' => $record->getValue('id')
                ]
            );
        };
    }

    public function onBuildAfter(BuildAfter $event){
		$datasource = $event->getDatagrid()->getDatasource();

		if($datasource instanceof OrmDatasource){
			//standard acl does not apply on collaboration entities
			//instead user is able to access own objects + ones he manages
			
			//support only local level and system level
			$observer = new OneShotIsGrantedObserver();
			$this->aclVoter->addOneShotIsGrantedObserver($observer);
			$this->sc->isGranted('VIEW', 'entity:' . self::CLASS_NAME);
			$accessLevel = $observer->getAccessLevel();

			if($accessLevel == AccessLevel::BASIC_LEVEL){
				$queryBuilder = $datasource->getQueryBuilder();
				$id = $this->sc->getToken()->getUser()->getId();
				$queryBuilder->andWhere('court.owner = :userId OR :userId MEMBER OF court.users')
							 ->setParameter('userId', $id);
			}
		}
	}
}
?>