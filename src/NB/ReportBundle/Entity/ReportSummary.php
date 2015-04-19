<?php
namespace NB\ReportBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="oro_report_summary"
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class ReportSummary
{
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer", name="id")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id = null;
	
	/**
	 * @ORM\Column(type="text", name="dql")
	 */
	protected $dql = '';
	
	/**
	 * @ORM\Column(type="datetime", name="updatedAt", nullable=false)
	 */
	protected $updatedAt = null;
	
	/**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")  
     */
    protected $owner;

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
     * Set dql
     *
     * @param string $dql
     * @return ReportSummary
     */
    public function setDql($dql)
    {
        $this->dql = $dql;

        return $this;
    }

    /**
     * Get dql
     *
     * @return string 
     */
    public function getDql()
    {
        return $this->dql;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return ReportSummary
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set owner
     *
     * @param \Oro\Bundle\UserBundle\Entity\User $owner
     * @return ReportSummary
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

    public function __toString(){
        return 'report summary';
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
