<?php
namespace NB\ReportBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="oro_workunit"
 * )
 * 
 * 
 * @Config(
 *      routeName="oro_workunit_index",
 *      routeView="oro_workunit_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list-alt"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL"
 *          },
 *          "dataaudit"={
 *              "auditable"=false
 *          },
 *          "comment"={
 *              "applicable"=false
 *          }
 *      }
 * )
 */
class WorkUnit
{
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer", name="id")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id = null;

	/**
	 * @ORM\Column(type="string", name="subject", length=255, unique=false, nullable=true)
	 */
	protected $subject = null;
	
	/**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    protected $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=false)
     */
    protected $endDate;

    /**
     * @var WorkType
     *
     * @ORM\ManyToOne(targetEntity="Extend\Entity\worktype")
     * @ORM\JoinColumn(name="worktype_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $worktype;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")  
     */
    protected $owner;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="Extend\Entity\client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $client;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @ORM\Column(type="string", name="relatedEntityClass", length=255, unique=false, nullable=true)
     */
    protected $relatedEntityClass = null;

    /**
     * @ORM\Column(type="integer", name="relatedEntityId", unique=false, nullable=true)
     */
    protected $relatedEntityId = null;
    
    
    

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return WorkUnit
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return WorkUnit
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return WorkUnit
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set worktype
     *
     * @param \Extend\Entity\worktype $worktype
     * @return WorkUnit
     */
    public function setWorktype(\Extend\Entity\worktype $worktype = null)
    {
        $this->worktype = $worktype;

        return $this;
    }

    /**
     * Get worktype
     *
     * @return \Extend\Entity\worktype 
     */
    public function getWorktype()
    {
        return $this->worktype;
    }

    /**
     * Set owner
     *
     * @param \Oro\Bundle\UserBundle\Entity\User $owner
     * @return WorkUnit
     */
    public function setOwner(\Oro\Bundle\UserBundle\Entity\User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \Oro\Bundle\UserBundle\Entity\User 
     */
    public function getOwner()
    {
        return $this->owner;
    }

    public function getOwnerId(){
        return $this->getOwner() ? $this->getOwner()->getId() : null;
    }

    /**
     * Set client
     *
     * @param \Extend\Entity\client $client
     * @return WorkUnit
     */
    public function setClient(\Extend\Entity\client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return \Extend\Entity\client 
     */
    public function getClient()
    {
        return $this->client;
    }

    
    /**
     * Set organization
     *
     * @param \Oro\Bundle\OrganizationBundle\Entity\Organization $organization
     * @return WorkUnit
     */
    public function setOrganization(\Oro\Bundle\OrganizationBundle\Entity\Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return \Oro\Bundle\OrganizationBundle\Entity\Organization 
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getSubject();
    }

    /**
     * Set relatedEntityClass
     *
     * @param string $relatedEntityClass
     * @return WorkUnit
     */
    public function setRelatedEntityClass($relatedEntityClass)
    {
        $this->relatedEntityClass = $relatedEntityClass;

        return $this;
    }

    /**
     * Get relatedEntityClass
     *
     * @return string 
     */
    public function getRelatedEntityClass()
    {
        return $this->relatedEntityClass;
    }

    /**
     * Set relatedEntityId
     *
     * @param integer $relatedEntityId
     * @return WorkUnit
     */
    public function setRelatedEntityId($relatedEntityId)
    {
        $this->relatedEntityId = $relatedEntityId;

        return $this;
    }

    /**
     * Get relatedEntityId
     *
     * @return integer 
     */
    public function getRelatedEntityId()
    {
        return $this->relatedEntityId;
    }
}
