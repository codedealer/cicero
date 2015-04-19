<?php
namespace NB\ReportBundle\EventListener;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;

use NB\ReportBundle\Entity\ReportSummary;

class ReportDatagridListener
{
	protected $doctrine, $securityContext;

    public function __construct($doctrine, $securityContext)
    {
        $this->doctrine = $doctrine;
        $this->securityContext = $securityContext;
    }

	public function onBuildAfter(BuildAfter $event)
    {
        $datagrid   = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        $parameters = $datagrid->getParameters();

        if ($datasource instanceof OrmDatasource) {
        	
            /** @var QueryBuilder $query */
            $queryBuilder = $datasource->getQueryBuilder();

            $queryBuilder->setParameter('clientId', $parameters->get('clientId'));

            $queryBuilder->setParameter('contractId', $parameters->get('contract'));
        }
    }

    public function onResultBefore(OrmResultBefore $event){
        $summary = $this->doctrine->getRepository('NBReportBundle:ReportSummary')
                    ->findOneBy(['owner' => $this->securityContext->getToken()->getUser()->getId()]);
        if(!($summary instanceof ReportSummary)){
            $summary = new ReportSummary();
            $summary->setOwner($this->securityContext->getToken()->getUser());
        }

        $summary->setDql($event->getQuery()->getDql());

        $em = $this->doctrine->getManager();
        $em->persist($summary);
        $em->flush();
    }
}