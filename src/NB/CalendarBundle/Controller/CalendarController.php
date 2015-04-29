<?php

namespace NB\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/table-calendar")
 */
class CalendarController extends Controller
{
    const FIRST_MONTH = 4;
    const FIRST_YEAR = 2015;

    /**
     * @Route("/{year}/{month}",
     * name="nb_table_index",
     * defaults={"year"=0, "month"=0}
     * )
     * @Template()
     */
    public function indexAction($year, $month)
    {
        if(!$year)
        	$year = (int) date("Y");
        if(!$month)
        	$month = (int) date("m");

        return array('year' => $year, 'month' => $month);
    }
}
