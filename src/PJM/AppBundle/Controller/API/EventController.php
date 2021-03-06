<?php

namespace PJM\AppBundle\Controller\API;

use PJM\AppBundle\Entity\Boquette;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/event")
 * @Security("has_role('ROLE_USER')")
 */
class EventController extends Controller
{
    /**
     * @param Request $request
     * @param Boquette $boquette
     *
     * @return JsonResponse
     * @Route("/calendrier/{slug}", options={"expose"=true}, defaults={"slug"=null})
     * @Method("GET")
     */
    public function calendarAction(Request $request, Boquette $boquette = null)
    {
        $start = new \DateTime($request->query->get('start'));
        $end = new \DateTime($request->query->get('end'));
        $end->setTime(23, 59, 59);

        $events = $this->get('pjm.services.evenement_manager')->getBetweenDates($start, $end, $this->getUser(), $boquette, true);

        if (isset($boquette)) {
            // si on précise la boquette on ne s'intéresse pas aux anniversaires et exances
            return new JsonResponse($events);
        }

        $userManager = $this->get('pjm.services.user_manager');
        $anniversaires = $userManager->getBirthdaysBetweenDates($start, $end, $this->getUser(), true);

        $exances = $this->get('pjm.services.trads')->isExanceEnabled() ?
            $userManager->getExancesBetweenDates($start, $end, $this->getUser(), true) :
            array();

        return new JsonResponse(array_merge($events, $anniversaires, $exances));
    }
}
