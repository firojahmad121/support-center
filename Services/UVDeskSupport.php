<?php

namespace Webkul\UVDesk\SupportCenterBundle\Services;

use Webkul\UVDesk\CoreBundle\Entity\User;
use Webkul\UVDesk\CoreBundle\Entity\UserInstance;
use Webkul\UVDesk\SupportCenterBundle\Entity\Type;
use Webkul\UVDesk\SupportCenterBundle\Entity\Ticket;
use Webkul\UVDesk\SupportCenterBundle\Entity\Status;
use Webkul\UVDesk\SupportCenterBundle\Entity\Thread;
use Webkul\UVDesk\SupportCenterBundle\Entity\Priority;
use Webkul\UVDesk\SupportCenterBundle\Entity\user_group;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

class UVDeskSupport
{
    private $entityManager;
    private $container;

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->entityManager = $entityManager;
        $this->container = $container;
    }

    public function createNewTicket($ticketDetail)
    {
        $em = $this->entityManager;
        $typeRepo = $em->getRepository('SupportCenterBundle:Type');
        $ticketRepo = $em->getRepository('SupportCenterBundle:Ticket');
        $threadRepo = $em->getRepository('SupportCenterBundle:Thread');
        $statusRepo = $em->getRepository('SupportCenterBundle:Status');
        $priorityRepo = $em->getRepository('SupportCenterBundle:Priority');
        $groupRepo = $em->getRepository('SupportCenterBundle:user_group');

        $currentUser = $this->container->get('security.token_storage')->getToken();

        if(!$currentUser || $currentUser->getUser() == "anon.") {
            $customerName = $ticketDetail['name'];
            $currentUserEmail = $ticketDetail['from'];
        } else {
            $customerName = "";
            $currentUserEmail = $currentUser->getUser()->getEmail();
        }
        // create user if donot exist
        $customer = $this->verifyCustomer($currentUserEmail, $customerName);

        $type = $ticketDetail['type'];
        $subject = $ticketDetail['subject'];
        $reply = $ticketDetail['reply'];

        $type = $typeRepo->findOneBy(['name' => $type]);
        $status = $statusRepo->findOneBy(['name' => 'Open']);
        $group = $groupRepo->findOneBy(['name' => 'Default']);
        $priority = $priorityRepo->findOneBy(['name' => 'Low']);

        if($customer) {
            $newTicket = new Ticket;
            $newTicket->setAgent(null);
            $newTicket->setType($type);
            $newTicket->setUserGroup($group);
            $newTicket->setSource("website");
            $newTicket->setStatus($status);
            $newTicket->setCustomer($customer);
            $newTicket->setPriority($priority);
            $newTicket->setCreatedAt(new \DateTime('now'));
            $newTicket->setSubject($ticketDetail['subject']);
    
            $newThread = new Thread;
            $newThread->setUser($customer);
            $newThread->setReply($reply);
            $newThread->setSource("Website");
            $newThread->setTicket($newTicket);
            $newThread->setThreadType("create");
            $newTicket->setCreatedAt(new \DateTime('now'));
            $newThread->setFullname($customer->getFirstName() . ' ' . $customer->getLastName());
            
            $em->persist($newTicket);
            $em->persist($newThread);
            $em->flush();
        }
    }

    public function createNewThread($ticketId, $threadDetail)
    {
        $em = $this->entityManager;
        $ticketRepo = $em->getRepository('SupportCenterBundle:Ticket');
        $threadRepo = $em->getRepository('SupportCenterBundle:Thread');

        $currentUser = $this->getCurrentUser();
        $fullName = $currentUser->getFirstName() . ' ' . $currentUser->getLastName();

        $ticket = $ticketRepo->findOneBy(['id' => $ticketId ]);
        
        $newThread = new Thread;
        $newThread->setUser($currentUser);
        $newThread->setTicket($ticket);
        $newThread->setSource("Website");
        $newThread->setFullname($fullName);
        $newThread->setReply($threadDetail['reply']);
        $newThread->setCreatedAt(new \DateTime('now'));
        $newThread->setThreadType($threadDetail['threadType']);

        $em->persist($newThread);
        $em->flush();
    }

    public function verifyCustomer($customerEmail, $customerName)
    {
        $em = $this->entityManager;
        $userRepo = $em->getRepository('CoreBundle:User');
        $userRolesRepo = $em->getRepository('CoreBundle:Role');
        $user_ins_repo = $em->getRepository('CoreBundle:UserInstance');
        $user = $userRepo->findOneBy(['email' => $customerEmail]);

        $name = $this->split_name($customerName);

        if(!$user) {
            $user = new User;
            $user->setEmail($customerEmail);
            $user->setPassword(Null);
            $user->setFirstName($name[0]);
            $user->setLastName($name[1]);
            $user->setIsEnabled(1);

            $customerRole = $userRolesRepo->findOneBy(['id' => 4]);

            $userIns = new UserInstance;
            $userIns->setUser($user);
            $userIns->setRole($customerRole);
            $userIns->setsource("website");
            $userIns->setCreatedAt(new \DateTime('now'));
            $userIns->setUpdatedAt(new \DateTime('now'));
            $userIns->setIsActive(1);
            $userIns->setIsVerified(1);
            $userIns->setIsStarred(0);

            $em->persist($user);
            $em->persist($userIns);
            $em->flush();
        }

        return $user;
    }

    public function split_name($name) {
        $name = trim($name);
        $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $first_name = trim( preg_replace('#'.$last_name.'#', '', $name ) );
        return array($first_name, $last_name);
    }

    public function getCurrentUser()
    {
        return $currentUser = $this->container->get('security.token_storage')->getToken()->getUser();
    }

    public function convertToFormat($date, $format = "d-M g:ia")
    {
        if($date)
            return $date->format($format);
        else
            return null;
    }
    
}