<?php
namespace NB\CalendarBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;

use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("table")
 * @NamePrefix("nb_api_")
 * @QueryParam(
 *      name="year",
 *      requirements="\d+",
 *      nullable=true,
 *      strict=true
 * )
 * @QueryParam(
 *      name="month",
 *      requirements="\d+",
 *      nullable=true,
 *      strict=true
 * )
 */
class CalendarController extends FOSRestController implements ClassResourceInterface
{
	const FIRST_MONTH = 4;
    const FIRST_YEAR = 2015;

	public function getAction()
    {
        $localeSettings = $this->get('oro_locale.settings');
        if ($this->getRequest()->get('year') && $this->getRequest()->get('month')){
        	$month = $this->getRequest()->get('month');
        	$year = $this->getRequest()->get('year');
        	$start = new \DateTime("$year-$month-01", new \DateTimeZone($localeSettings->getTimeZone()));
        	$end = clone $start;
        	$end->modify('+1 month');
        }
        else{
        	$this->createNotFoundException();
        }
        $repo = $this->getDoctrine()->getRepository('OroUserBundle:User');
        $users = $repo->findBy(['enabled' => true]);
        $workunitProvider = $this->get('nb_workunit.workunit_provider');
        $result = [];
        $out = ['meta' => [
        			'month' => $month, 
        			'year' => $year, 
        			'days' => $start->format('t'),
        			'monthName' => $this->getMonth($month)
        			],
        		'cal' => '',
        		];
        foreach ($users as $user) {
        	$wu = $workunitProvider->getWorkUnits($user, $start, $end);
        	$this->parse($user, $wu, $result, $year, $month, $localeSettings);
        }
        $out['cal'] = $result;
        return new Response(json_encode($out), Codes::HTTP_OK);
    }

    protected function parse($user, $workunits, &$result, $year, $month, $localeSettings){
    	$res = [
    		'user' => ['id' => $user->getId(), 'name' => $user->getFirstname() . ' ' . $user->getLastname()],
    		'units' => []
    	];
    	
    	$dayz = [];
    	$logger = $this->get('logger');
    	foreach ($workunits as $workunit) {
    		$workunit['startDate']->setTimeZone(new \DateTimeZone($localeSettings->getTimeZone()));
    		$workunit['endDate']->setTimeZone(new \DateTimeZone($localeSettings->getTimeZone()));

    		$day = $workunit['startDate']->format('j');

    		$logger->notice("user " . $user->getFirstname() . " day $day");

    		if(!array_key_exists($day, $dayz))
    			$dayz[$day] = 0;
    		//count cummulative minutes per day
			$endDay = $workunit['endDate']->format('j');
			if($day === $endDay){
				$time = $workunit['endDate']->getTimeStamp() - $workunit['startDate']->getTimeStamp();
				$time = $time / 60; //in minutes
				$logger->notice("    minutes $time");
				$dayz[$day] += $time;
			}
			else{
				for($i=$day;$i<=$endDay;$i++){
					$thisDay = new \DateTime("$year-$month-$i");
					if($i === $endDay){
	    				$time = $workunit['endDate']->getTimeStamp() - $thisDay->getTimeStamp();
	    				$time = $time / 60; //in minutes
	    				if(!array_key_exists($i, $dayz))
	    					$dayz[$i] = 0;
	    				$dayz[$i] += $time;
	    			}
	    			elseif($i === $day){
	    				$nextDay = (int) $i;
	    				$nextDay++;
	    				$nite = new \DateTime("$year-$month-$nextDay");
	    				$time = $nite->getTimeStamp() - $workunit['startDate']->getTimeStamp();
	    				$time = $time / 60; //in minutes
	    				if(!array_key_exists($i, $dayz))
	    					$dayz[$i] = 0;
	    				$dayz[$i] += $time;
	    			}
	    			else{
	    				$dayz[$i] = 24 * 60;
	    			}
				}
    		}
    	}
    	
    	$this->recount($dayz);

    	$res['units'] = $dayz;
    	$result[] = $res;
    }

    protected function recount(&$dayz){
    	foreach ($dayz as $key => $day) {
    		$dayz[$key] = floor($day / 60);
    	}
    }

    protected function getMonth($month){
    	switch ($month) {
    		case '1':
    			$month = 'Январь';
    			break;
    		case '2':
    			$month = 'Февраль';
    			break;
    		case '3':
    			$month = 'Март';
    			break;
    		case '4':
    			$month = 'Апрель';
    			break;
    		case '5':
    			$month = 'Май';
    			break;
    		case '6':
    			$month = 'Июнь';
    			break;
    		case '7':
    			$month = 'Июль';
    			break;
    		case '8':
    			$month = 'Август';
    			break;
    		case '9':
    			$month = 'Сентябрь';
    			break;
    		case '10':
    			$month = 'Октябрь';
    			break;
    		case '11':
    			$month = 'Ноябрь';
    			break;
    		case '12':
    			$month = 'Декабрь';
    			break;
    		default:
    			$month = $month;
    			break;
    	}
    return $month;
    }
}