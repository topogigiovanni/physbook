<?php

namespace PJM\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Responsable.
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="PJM\AppBundle\Entity\ResponsableRepository")
 *
 * @UniqueEntity({"user", "responsabilite"})
 */
class Responsable
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     * @Assert\NotBlank()
     * @Assert\Date()
     */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity="Responsabilite", inversedBy="responsables")
     * @Assert\NotBlank()
     **/
    private $responsabilite;

    /**
     * @ORM\ManyToOne(targetEntity="PJM\AppBundle\Entity\User", inversedBy="responsables")
     * @Assert\NotBlank()
     **/
    private $user;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set active.
     *
     * @param bool $active
     *
     * @return Responsable
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Toggle active.
     *
     * @return Responsable
     */
    public function toggleActive()
    {
        $this->active = !$this->active;

        return $this;
    }

    /**
     * Get active.
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return Responsable
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set responsabilite.
     *
     * @param \PJM\AppBundle\Entity\Responsabilite $responsabilite
     *
     * @return Responsable
     */
    public function setResponsabilite(\PJM\AppBundle\Entity\Responsabilite $responsabilite = null)
    {
        $this->responsabilite = $responsabilite;

        return $this;
    }

    /**
     * Get responsabilite.
     *
     * @return \PJM\AppBundle\Entity\Responsabilite
     */
    public function getResponsabilite()
    {
        return $this->responsabilite;
    }

    /**
     * Set user.
     *
     * @param \PJM\AppBundle\Entity\User $user
     *
     * @return Responsable
     */
    public function setUser(\PJM\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \PJM\AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
