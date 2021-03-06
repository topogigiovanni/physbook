<?php

namespace PJM\AppBundle\Services;

use Doctrine\ORM\EntityManager;

class Group
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Retourne la prom's des 1A actuels.
     *
     * @return int Prom's des 1A actuels
     */
    public function getProms1A()
    {
        $now = new \DateTime();

        $promsAnnee = (int) (substr($now->format('Y'), 0, 1).substr($now->format('Y'), 2, 3));

        if ($now > new \DateTime(date('Y').'-07-01')) {
            return $promsAnnee;
        }

        return $promsAnnee - 1;
    }

    /**
     * Retourne la prom's des P"N" par rapport aux conscrits actuels (ex. P3 des 213 est 211).
     *
     * @return int Prom's des PN
     */
    public function getPromsPN($n)
    {
        return $this->getProms1A() + 1 - $n;
    }

    public function getUsersAuTabagns()
    {
        $promo = $this->getProms1A();

        return $this->em->getRepository('PJMAppBundle:User')->getByProms(array($promo, --$promo, --$promo));
    }
}
