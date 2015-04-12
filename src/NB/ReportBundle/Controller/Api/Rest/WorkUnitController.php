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

/**
 * @RouteResource("workunit")
 * @NamePrefix("nb_api_")
 */
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

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->get('nb_workunit.manager.api');
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->get('nb_workunit.form.api');
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        return $this->get('nb_workunit.form.handler.workunit_api');
    }
}
?>