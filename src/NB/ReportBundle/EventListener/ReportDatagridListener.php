<?php
namespace NB\ReportBundle\EventListener;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;


class ReportDatagridListener
{
	public function __construct($logger)
    {
        $this->logger = $logger;
    }

	public function onBuildAfter(BuildAfter $event)
    {
        $datagrid   = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        $parameters = $datagrid->getParameters();

        if ($datasource instanceof OrmDatasource) {
        	$this->logger->notice('its possible');
            /** @var QueryBuilder $query */
            $queryBuilder = $datasource->getQueryBuilder();

            $queryBuilder->setParameter('clientId', $parameters->get('clientId'));

            $queryBuilder->setParameter('contractId', $parameters->get('contract'));
        }
    }
}