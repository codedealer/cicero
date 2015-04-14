<?php

namespace NB\CourtBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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
}
?>