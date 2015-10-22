<?php

namespace PJM\AppBundle\Services;

use Buzz\Browser;
use Doctrine\ORM\EntityManager;
use PJM\AppBundle\Entity\Notifications\Notification;
use PJM\AppBundle\Entity\User;
use PJM\AppBundle\Enum\Notifications\NotificationEnum;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class NotificationManager
{
    private $em;
    private $requestStack;
    private $push;
    private $notificationsList;
    private $mailer;
    private $buzz;
    private $translator;

    public function __construct(EntityManager $em, RequestStack $requestStack, Push $push, Mailer $mailer, Browser $buzz, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->push = $push;
        $this->mailer = $mailer;
        $this->notificationsList = NotificationEnum::$list;
        $this->buzz = $buzz;
        $this->translator = $translator;
    }

    /**
     * @param $key
     * @param $infos
     * @param array|User $users
     * @param bool|true $flush
     * @return bool
     */
    public function send($key, $infos, $users, $flush = true) {
        $notificationType = isset($this->notificationsList[$key]) ? $this->notificationsList[$key] : null;

        // on vérifie que ce type de notification existe
        if (!isset($notificationType)) {
            return false;
        }

        // on vérifie qu'il y a les bonnes infos pour remplir le message
        // attention là il faut mettre les clés dans le bon ordre aussi...
        if ($notificationType['infos'] != array_keys($infos)) {
            return false;
        }

        if ($users instanceof User) {
            $users = array($users);
        }

        /** @var User $user */
        foreach($users as $user) {
            // on enregistre la notification en BDD
            $notification = new Notification();
            $notification->setKey($key);
            $notification->setInfos($infos);
            $user->addNotification($notification);

            // on regarde si l'utilisateur a plus de 50 notifications, si oui on supprime la première
            if ($this->count($user) > 50) {
                $user->removeNotification($this->em->getRepository('PJMAppBundle:Notifications\Notification')->getFirst($user));
            }

            $this->em->persist($user);

            // si l'utilisateur est abonné à ce type de notification, on envoit un push ou/et un webhook
            $settings = $user->getNotificationSettings();
            if ($settings->has($notificationType['type'])) {
                $message = $this->getMessage($notification);
                $this->sendPushToUser($user, $message);
                $this->sendToWebhook($settings->getWebhook(), $message);
                $this->sendToEmail($user->getEmail(), $message);
            }
        }

        if ($flush) {
            $this->em->flush();
        }

        return true;
    }

    public function sendFlash($type, $message)
    {
        $this->requestStack->getCurrentRequest()->getSession()->getFlashBag()->add($type, $message);
    }

    public function sendPushToUser(User $user, $message)
    {
        $this->push->sendNotificationToUser($user, $message);
    }

    public function sendToWebhook($webhook, $message) {
        if (empty($webhook)) {
            return false;
        }

        // format message
        $message = "[Phy'sbook] ".$message." https://physbook.fr";

        $headers = array(
            'content-type' => 'text/plain; charset=utf-8',
        );

        $response = $this->buzz->post($webhook.$message, $headers);

        if ($response->getStatusCode() != 200) {
            $this->sendToEmail(
                'error@physbook.fr',
                'Erreur '.$response->getStatusCode().' lors de l\'accès au webhook "'.$webhook.'"."'
            );

            return false;
        }

        return true;
    }

    public function sendToEmail($email, $message) {
        $this->mailer->sendMessageToEmail($message, $email);
    }

    public function get(User $user)
    {
        $notifications = $user->getNotifications();
        $settings = $user->getNotificationSettings();

        $notifications = $notifications->map(function(Notification $notification) use ($settings) {
            // on remplace les variables par %infos%
            $infos = $notification->getInfos();

            $newKeys = array_map(function($k) {
                return "%".$k."%";
            }, array_keys($infos));

            $notification->setVariables(array_combine(
                $newKeys,
                array_values($infos)
            ));

            // on indique comme non lue ou pas (pour pas que cela soit changé ensuite quand on marque comme lu)
            $notification->setNew(!$notification->getReceived());

            if (isset($this->notificationsList[$notification->getKey()])) {
                $notificationType = $this->notificationsList[$notification->getKey()];

                // on ajoute le type et le path
                $notification->setType($notificationType['type']);
                $notification->setPath($notificationType['path']);

                // on vérifie que l'utilisateur est abonné ou non au type de notification, et si oui on indique "important"
                $notification->setImportant($settings->has($notificationType['type']));
            }

            return $notification;
        });

        return $notifications;
    }

    private function getMessage(Notification $notification, $strip = true) {
        // on remplace les variables par %infos%
        $infos = $notification->getInfos();

        $newKeys = array_map(function($k) {
            return "%".$k."%";
        }, array_keys($infos));

        $infos = array_combine(
            $newKeys,
            array_values($infos)
        );

        $message = $this->translator->trans('notifications.content.'.$notification->getKey(), $infos);

        return $strip ? strip_tags($message) : $message;
    }

    /**
     * @param User $user
     */
    public function markAllAsRead(User $user)
    {
        $notifications = $this->em->getRepository("PJMAppBundle:Notifications\Notification")->findBy(array(
            'user' => $user,
            'received' => false,
        ));

        /** @var Notification $notification */
        foreach ($notifications as $notification) {
            $notification->setReceived(true);
            $this->em->persist($notification);
        }

        $this->em->flush();
    }

    public function count(User $user, $received = null)
    {
        return $this->em->getRepository('PJMAppBundle:Notifications\Notification')->count($user, $received);
    }

    public function getLastNotificationByPushEndpoint($endpoint)
    {
        // on va chercher l'user qui a cet endpoint
        $pushSubscription = $this->em->getRepository('PJMAppBundle:PushSubscription')->findOneBy(array(
            'endpoint' => $endpoint
        ));

        if (empty($pushSubscription)) {
            return false;
        }

        $notification = $this->em->getRepository('PJMAppBundle:Notifications\Notification')->getLast($pushSubscription->getUser());

        if (empty($notification)) {
            return false;
        }

        return array(
            'message' => $this->getMessage($notification)
        );
    }
}