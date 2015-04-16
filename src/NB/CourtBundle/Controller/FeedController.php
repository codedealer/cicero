<?php
namespace NB\CourtBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use NB\CourtBundle\Entity\Feed;
use Extend\Entity\Court;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

/**
 * @Route("/court/feed")
 */
class FeedController extends Controller
{
	const TARGET_NAME = 'Extend_Entity_Court';

	/**
     * @Route("/create/{targetId}", name="nb_feed_create", requirements={"targetId"="\d+"})
     * @Acl(
     *      id="nb_feed_create",
     *      type="entity",
     *      class="NBCourtBundle:Feed",
     *      permission="CREATE"
     * )
     * @Template("NBCourtBundle:Feed:create.html.twig")
     */
    public function createAction($targetId)
    {
        $feed = new Feed();

        $request = $this->getRequest();
        $em = $this->getDoctrine()->getManager();

        $refId = $request->get('ref', false);
        if($refId){
        	//prepopulate subject field
        	try{
        		$workunit = $this->getDoctrine()->getRepository('NBReportBundle:WorkUnit')->find($refId);
        	}
        	catch(\Exception $e){
        		$refId = false;
        	}
        	if($refId)
        		$feed->setSubject($workunit->getSubject());
        }


        $form = $this->createForm('nb_feed_form', $feed);
        

        if ($request->getMethod() == 'POST'){
        	$form->submit($request);
            
            if ($form->isValid()) {
            	$routingHelper = $this->get('oro_entity.routing_helper');
            	$court = $routingHelper->getEntity(self::TARGET_NAME, $targetId);
            	

            	$this->get('oro_activity.manager')->addActivityTarget(
            		$feed,
            		$court
            	);

            	$createEvent = $form->get('createEvent')->getData();
            	if($createEvent){
            		$event = new CalendarEvent();
            		$start = $form->get('start')->getData();
            		$end = $form->get('end')->getData();
            		if(!($start instanceof \DateTime))
            			$start = new \DateTime('now', new \DateTimeZone('UTC'));
            		if(!($end instanceof \DateTime) || ($start >= $end)){
            			$end = clone $start;
            			$end->modify('+1 hour');
            		}
            		
            		$event->setStart($start)
            			  ->setEnd($end)
            			  ->setTitle($feed->getSubject())
            			  ;
            		if($feed->getDescription())
            			$event->setDescription($feed->getDescription());

            		$event->setAllDay($form->get('allDay')->getData());

            		$calendar = $court->getCalendar();
            		$event->setSystemCalendar($calendar);
            		$em->persist($event);
            	}

            	$security = $this->get('oro_security.security_facade');
            	//$feed->setOwner($security->getLoggedUser());
            	//$feed->setOrganization($security->getOrganization());

            	$em->persist($feed);
            	$em->flush();

            	$this->get('session')->getFlashBag()->add('success', 'Информация по делу успешно обновлена');

            	return new RedirectResponse($this->get('router')->generate(
            			'oro_entity_view', ['id'=>$targetId, 'entityName' => self::TARGET_NAME])
            	);
            }
        }
        $templateArray = [
        	'entity' => $feed,
        	'targetId' => $targetId,
        	'targetName' => self::TARGET_NAME,
        	'form' => $form->createView(),
        	'formAction' => $this->get('router')
            ->generate('nb_feed_create', ['targetId' => $targetId])
        ];

        if($refId)
        	$templateArray['ref'] = $refId;

        return $templateArray;
    }

    /**
     * This action is used to render the list of tasks associated with the given entity
     * on the view page of this entity
     *
     * @Route(
     *      "/activity/view/{entityClass}/{entityId}",
     *      name="nb_feed_activity_view"
     * )
     *
     * 
     * @Template
     */
    public function activityAction($entityClass, $entityId){
    	return array(
            'entity' => $this->get('oro_entity.routing_helper')->getEntity($entityClass, $entityId)
        );
    }

    /**
     * @Route("/widget/info/{id}", name="nb_feed_widget_info", requirements={"id"="\d+"})
     * @Template
     */
    public function infoAction(Feed $entity)
    {
        return array('entity' => $entity);
    }

    /**
     * @Route(
     *      ".{_format}",
     *      name="nb_feed_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Acl(
     *      id="nb_court_feed_view",
     *      type="entity",
     *      class="NBCourtBundle:Feed",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function indexAction()
    {
    	//Not implemented yet
        return $this->redirect($this->get('router')
        		->generate('oro_entity_index', ['entityName' => 'Extend_Entity_Court']));
    }
}