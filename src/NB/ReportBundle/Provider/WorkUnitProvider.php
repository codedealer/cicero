<?php
namespace NB\ReportBundle\Provider;
use Doctrine\ORM\QueryBuilder;

class WorkUnitProvider
{
	public function __construct($doctrine){
		$this->doctrine = $doctrine;
	}

	public function getWorkUnits($user, $start, $end){
	
	$repo  = $this->doctrine->getRepository('NBReportBundle:WorkUnit');
        $qb    = $repo->createQueryBuilder('w')
        	 ->where('w.startDate >= :start')
        	 ->andWhere('w.endDate < :end')
        	 ->andWhere('w.owner = :owner')
        	 ->setParameter('start',$start)
        	 ->setParameter('end',$end)
        	 ->setParameter('owner',$user)
                 ->getQuery()
        	 ->getArrayResult();
        return $qb;
	}
}