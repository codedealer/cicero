<?php
namespace NB\CoreBundle\EventListener;

use Doctrine\ORM\QueryBuilder;
use ORO\Bundle\DataGridBundle\Datasource\ORM\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

class CollaborationDatagridListener
{
	private $configProvider;

	public function __construct($configProvider){
		$this->configProvider = $configProvider;
	}

	public function buildAfter(BuildAfter $event){
		
	}
}
?>