<?php

namespace PJM\AppBundle\Services;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use PJM\AppBundle\Entity\Boquette;
use PJM\AppBundle\Entity\Historique;
use PJM\AppBundle\Entity\Compte;
use PJM\UserBundle\Entity\User;

class Utils
{
    protected $em;
    protected $mailer;

    public function __construct(EntityManager $em, Mailer $mailer)
    {
        $this->em = $em;
        $this->mailer = $mailer;
    }

    public function getBoquette($boquetteSlug)
    {
        $boquette = $this->em
            ->getRepository('PJMAppBundle:Boquette')
            ->findOneBySlug($boquetteSlug);

        return $boquette;
    }

    public function getCompte(User $user, $boquetteSlug)
    {
        // Le C'vis est dans le compte du Pian's
        if ($boquetteSlug == "cvis") {
            $boquetteSlug = "pians";
        }

        $repository = $this->em->getRepository('PJMAppBundle:Compte');
        $compte = $repository->findOneByUserAndBoquetteSlug($user, $boquetteSlug);

        if ($compte === null) {
            $compte = new Compte($user, $this->getBoquette($boquetteSlug));
            $this->em->persist($compte);
            $this->em->flush();
        }

        return $compte;
    }

    public function getSolde(User $user, $boquetteSlug)
    {
        return $this->getCompte($user, $boquetteSlug)->getSolde();
    }

    public function bucquage($boquetteSlug, $itemSlug)
    {
        $boquette = $this->getBoquette($boquetteSlug);
        $repositoryHistorique = $this->em->getRepository('PJMAppBundle:Historique');
        $repositoryCommande = $this->em->getRepository('PJMAppBundle:Commande');
        $repositoryCompte = $this->em->getRepository('PJMAppBundle:Compte');
        $repositoryVacances = $this->em->getRepository('PJMAppBundle:Vacances');
        $listeUsers = [];

        // on regarde quand a été fait le dernier bucquage
        $lastBucquage = $repositoryHistorique->findLastValidByItemSlug($itemSlug);
        $now = new \DateTime("now");
        $now->setTime(0, 0, 0);
        // s'il y a déjà eu un bucquage
        if (isset($lastBucquage)) {
            // si ce bucquage a été aujourd'hui, on arrête
            if ($lastBucquage->getDate()->setTime(0, 0, 0) == $now) {
                return 'Un bucquage a deja ete fait aujourd\'hui.';
            }

            // sinon on compte le nombre de jours à bucquer
            $startDate = $lastBucquage->getDate()->setTime(0, 0, 0)->add(new \DateInterval('P1D'));
            $nbJours = $startDate->diff($now, true)->days+1;
        } else {
            // sinon on bucque le premier jour
            $startDate = $now;
            $nbJours = 1;
        }
        $endDate = clone $startDate;
        $endDate->add(new \DateInterval('P'.$nbJours.'D'));

        // on obtient tous les jours à bucquer (contient encore les WE)
        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            $endDate
        );

        // on va chercher les vacances
        $listeVacances = $repositoryVacances->findByFait(false);

        // pour tous les jours jusqu'à aujourd'hui, on débite
        foreach ($period as $date) {
            // si le jour n'est pas un samedi/dimanche
            if ($date->format("D") != "Sat" && $date->format("D") != "Sun") {
                $jourDeVacs = false; // par défaut

                // pour chaque vacances pas encore finies
                foreach ($listeVacances as $vacances) {
                    // si le dernier jour de ces vacances est passé
                    if ($vacances->getDateFin() < $date) {
                        // on indique les vacances comme finies
                        $vacances->setFait(true);
                        $this->em->persist($vacances);
                    }

                    $endDateFinVacs = clone $vacances->getDateFin();
                    $endDateFinVacs->add(new \DateInterval('P1D'));
                    $periodVacs = new \DatePeriod(
                        $vacances->getDateDebut(),
                        new \DateInterval('P1D'),
                        $endDateFinVacs
                    );

                    // pour chaque jour de vacances
                    foreach ($periodVacs as $dateVacs) {
                        // si le jour en train d'être bucqué est un jour de vacances
                        if ($date == $dateVacs) {
                            // on l'indique
                            $jourDeVacs = true;
                            // on a un jour de vacances donc on a notre info, on arrête le bouclage
                            break 2;
                        }
                    }
                }

                // si le jour n'est pas un jour de vacances
                if (!$jourDeVacs) {
                    // on regarde les commandes actives et résiliées à cette date
                    $commandes = array_merge(
                        $repositoryCommande->findByItemSlugAndValidAndAtDate($itemSlug, true, $date),
                        $repositoryCommande->findByItemSlugAndValidAndAtDate($itemSlug, false, $date)
                    );

                    foreach ($commandes as $commande) {
                        // calculer prix (en cents, et le nombre est enregistré en déciunité)
                        $prix = $commande->getItem()->getPrix()*$commande->getNombre()/10;

                        // bucquer dans l'historique
                        $historique = new Historique();
                        $historique->setCommande($commande);
                        $historique->setValid(true);
                        $this->em->persist($historique);

                        // réduire le solde
                        $compte = $repositoryCompte->findOneByUserAndBoquette($commande->getUser(), $boquette);
                        if (!isset($compte)) {
                            return 'Erreur : le compte de '.$commande->getUser()->getUsername().' n\'existe pas.';
                        }

                        $compte->debiter($prix);
                        $this->em->persist($compte);

                        // on enregistre l'utilisateur comme "à regarder" pour le negat'ss
                        if (!in_array($compte->getUser(), $listeUsers)) {
                            $listeUsers[] = $compte->getUser();
                        }
                    }
                } else {
                    $nbJours--;
                }
            } else {
                // si c'est un samedi ou un dimanche on compte un jour en moins
                $nbJours--;
            }
        }

        // propagation en BDD des débits
        $this->em->flush();

        // pour tous ceux qui ont été débité,
        // on check les comptes en negat'ss et envoit un mail
        foreach ($listeUsers as $user) {
            $compte = $repositoryCompte->findOneByUserAndBoquette($user, $boquette);
            if ($compte->getSolde() < 0) {
                $this->mailer->sendAlerteSolde($compte);
            }
        }

        return $nbJours.' jours bucques a partir du '.$startDate->format('d/m/y').'.';
    }
}