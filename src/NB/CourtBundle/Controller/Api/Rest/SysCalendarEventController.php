<?php
namespace NB\CourtBundle\Controller\Api\Rest;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\CalendarBundle\Handler\DeleteHandler;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpDateTimeParameterFilter;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;

/**
 * @RouteResource("syscalendarevent")
 * @NamePrefix("nb_api_")
 */
class SysCalendarEventController extends RestController implements ClassResourceInterface
{
	/**
     * Get calendar events.
     *
     * @QueryParam(
     *      name="calendar", requirements="\d+",
     *      nullable=false,
     *      strict=true,
     *      description="Calendar id."
     * )
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. defaults to 10."
     * )
     * @QueryParam(
     *      name="start",
     *      requirements="\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?",
     *      nullable=true,
     *      strict=true,
     *      description="Start date in RFC 3339. For example: 2009-11-05T13:15:30Z."
     * )
     * @QueryParam(
     *      name="end",
     *      requirements="\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?",
     *      nullable=true,
     *      strict=true,
     *      description="End date in RFC 3339. For example: 2009-11-05T13:15:30Z."
     * )
     * @QueryParam(
     *      name="subordinate",
     *      requirements="(true)|(false)",
     *      nullable=true,
     *      strict=true,
     *      default="false",
     *      description="Determines whether events from connected calendars should be included or not."
     * )
     * @QueryParam(
     *      name="iso",
     *      requirements="\d+",
     *      nullable=true
     * )
     * @QueryParam(
     *     name="createdAt",
     *     requirements="\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?",
     *     nullable=true,
     *     description="Date in RFC 3339 format. For example: 2009-11-05T13:15:30Z, 2008-07-01T22:35:17+08:00"
     * )
     * @QueryParam(
     *     name="updatedAt",
     *     requirements="\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?",
     *     nullable=true,
     *     description="Date in RFC 3339 format. For example: 2009-11-05T13:15:30Z, 2008-07-01T22:35:17+08:00"
     * )
     * 
     * 
     *
     * @return Response
     */
    public function cgetAction()
    {
        $calendarId  = (int)$this->getRequest()->get('calendar');
        $subordinate = (true == $this->getRequest()->get('subordinate'));
        $iso = $this->getRequest()->get('iso') ? (int) $this->getRequest()->get('iso') : false;

        $qb = null;
        if ($this->getRequest()->get('start') && $this->getRequest()->get('end')) {
            if($iso){
                $repo = $this->getDoctrine()->getRepository('OroCalendarBundle:CalendarEvent');
                $qb = $repo->getPublicEventListByTimeIntervalQueryBuilder(
                            new \DateTime($this->getRequest()->get('start')),
                            new \DateTime($this->getRequest()->get('end'))
                            )
                            ->andWhere('e.systemCalendar = :iso')
                            ->setParameter('iso', $iso)
                            ;
                $result = $this->get('oro_calendar.calendar_event_normalizer.public')->getCalendarEvents($calendarId, $qb->getQuery());

            }
            else{
                $result = [];
            }
        }
        else {
            throw new BadRequestHttpException(
                'Time interval ("start" and "end") should be specified.'
            );
        }

        return new Response(json_encode($result), Codes::HTTP_OK);
    }

    /**
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->get('oro_calendar.calendar_event.manager.api');
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->get('oro_calendar.calendar_event.form.api');
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        return $this->get('oro_calendar.calendar_event.form.handler.api');
    }

}
?>