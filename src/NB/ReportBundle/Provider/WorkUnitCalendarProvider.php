<?php
namespace NB\ReportBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CalendarBundle\Provider\CalendarProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class WorkUnitCalendarProvider implements CalendarProviderInterface
{
	const ALIAS = 'work';
    const MY_WORK_CALENDAR_ID = 1;

    protected $calendarLabels = [
        self::MY_WORK_CALENDAR_ID => 'nb.report.workunit.calendar_label'
    ];

    protected $translator;
    protected $doctrineHelper;
    protected $aclHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AclHelper $aclHelper,
        TranslatorInterface $translator
    ) {
        $this->doctrineHelper         = $doctrineHelper;
        $this->aclHelper              = $aclHelper;
        $this->translator             = $translator;
    }

    public function getCalendarDefaultValues($organizationId, $userId, $calendarId, array $calendarIds)
    {
        $result = [];
        
        $result[self::MY_WORK_CALENDAR_ID] = [
            'calendarName'    => $this->translator->trans($this->calendarLabels[self::MY_WORK_CALENDAR_ID]),
            'removable'       => false,
            'position'        => 1,
            'backgroundColor' => '#F83A22',
            'options'         => [
                'widgetRoute'   => 'nb_workunit_widget_info',
                'widgetOptions' => [
                    'title'         => 'Отчет о работе',
                    'dialogOptions' => [
                        'width' => 600
                    ]
                ]
            ]
        ];

        return $result;
    }

    public function getCalendarEvents(
        $organizationId,
        $userId,
        $calendarId,
        $start,
        $end,
        $connections,
        $extraFields = []
    ) {
        
        if ($this->isCalendarVisible($connections, self::MY_WORK_CALENDAR_ID)) {
            
            $repo  = $this->doctrineHelper->getEntityRepository('NBReportBundle:WorkUnit');
            $qb    = $repo->createQueryBuilder('w')
            			  ->select('w.id, w.subject, c.name as clientName, w.startDate, w.endDate, wt.name as worktypeName')
            			  ->leftJoin('w.worktype', 'wt')
            			  ->leftJoin('w.client', 'c')
            			  ->where('w.owner = :assignedTo AND w.startDate >= :start AND w.endDate <= :end')
            			  ->setParameter('assignedTo', $userId)
            			  ->setParameter('start', $start)
            			  ->setParameter('end', $end)
            			  ;
            if ($extraFields) {
            	foreach ($extraFields as $field) {
            	    $qb->addSelect('w.' . $field);
            	}
        	}
            $query = $this->aclHelper->apply($qb);

            return $this->normilize($query);
        }

        return [];
    }

    /**
     * @param array $connections
     * @param int   $calendarId
     * @param bool  $default
     *
     * @return bool
     */
    protected function isCalendarVisible($connections, $calendarId, $default = true)
    {
        return isset($connections[$calendarId])
            ? $connections[$calendarId]
            : $default;
    }

    protected function normilize($query){
    	$result = [];

        $items  = $query->getArrayResult();
        foreach ($items as $item) {
            /** @var \DateTime $start */
            $start = $item['startDate'];
            $end = $item['endDate'];
            
            $result[] = [
                'calendar'    => self::MY_WORK_CALENDAR_ID,
                'id'          => $item['id'],
                'title'       => $item['worktypeName'],
                'description' => $item['subject'] . "\r\nКлиент: " . $item['clientName'],
                'start'       => $start->format('c'),
                'end'         => $end->format('c'),
                'allDay'      => false,
                'createdAt'   => $start->format('c'),
                'updatedAt'   => $start->format('c'),
                'editable'    => false,
                'removable'   => false
            ];
        }

        return $result;
    }
}
?>