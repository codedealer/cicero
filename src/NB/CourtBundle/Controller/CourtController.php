<?php

namespace NB\CourtBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;

class CourtController extends Controller
{
	protected function checkAccess($permission, $entityName)
    {
        $securityFacade = $this->get('oro_security.security_facade');
    	$isGranted = $securityFacade->isGranted($permission, 'entity: ' . $entityName);
    	if(!$isGranted)
    		throw new AccessDeniedException('Доступ запрещен.');
    }

	public function indexAction($entityName){
		$entityClass = $this->get('oro_entity.routing_helper')->decodeClassName($entityName);

        if (!class_exists($entityClass)) {
            throw $this->createNotFoundException();
        }

        $this->checkAccess('VIEW', $entityClass);

        return $this->render('NBCourtBundle:Court:index.html.twig');
	}

	public function viewAction($entityName, $id){
		$entityClass = $this->get('oro_entity.routing_helper')->decodeClassName($entityName);

        if (!class_exists($entityClass)) {
            throw $this->createNotFoundException();
        }

        $this->checkAccess('VIEW', $entityClass);

        $em = $this->getDoctrine()->getManager();
        $entityConfigProvider = $this->get('oro_entity_config.provider.entity');
        $record = $em->getRepository($entityClass)->find($id);

        if (!$record) {
            throw $this->createNotFoundException();
        }

        $calendarConfigProvider = $this->get('oro_calendar.provider.calendar_config');
        $dateRange = $calendarConfigProvider->getDateRange();

        return $this->render('NBCourtBundle:Court:view.html.twig', [
        	'entity_name'   => $entityName,
            'entity'        => $record,
            'id'            => $id,
            'entity_config' => $entityConfigProvider->getConfig($entityClass),
            'entity_class'  => $entityClass,
            'startDate' 	=> $dateRange['startDate'],
            'endDate'		=> $dateRange['endDate']
        	]);
	}
}
?>