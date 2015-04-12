<?php
namespace NB\ReportBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Routing\ClassResourceInterface;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

class WorkUnitController extends RestController implements ClassResourceInterface
{
	/**
     * REST DELETE
     *
     * @param int $id
     *
     * 
     * @Acl(
     *      id="nb_workunit_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="NBReportBundle:WorkUnit"
     * )
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }
}
?>