<?php
namespace Webkul\UserBundle\Services;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// use Webkul\UserBundle\EventListener\ActivitySubscriber;
// use Webkul\CoreBundle\EventListener\WorkflowSubscriber;
// use Webkul\UserBundle\Event\ActivityEvent;

/**
 * RoomType uses event dispatcher to trigger api
 * lifecycle events.
 */
class EventManager
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * Initialize push with event dispatcher
     * Can be a service in concept. By default it
     * hooks standard pagination subscriber
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     */
    
    public function __construct(ContainerInterface $container, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->container = $container;
        $this->eventDispatcher = $eventDispatcher;
        if (is_null($this->eventDispatcher)) {
            $this->eventDispatcher = new EventDispatcher;
            $this->eventDispatcher->addSubscriber(new WorkflowSubscriber);
            $this->eventDispatcher->addSubscriber(new ActivitySubscriber);
            $this->eventDispatcher->addSubscriber(new \Webkul\AppBundle\EventSubscriber\SlackSubscriber($container));
            $this->eventDispatcher->addSubscriber(new \Webkul\AppBundle\EventSubscriber\Messaging\AmazonMessagingSubscriber($container));
        }

        $this->activityEvent = new ActivityEvent($this->container);
    }

    public function trigger($params)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        
        if (!empty($request)) {
            $skippableEvents = ['ticket.created', 'agent.created', 'customer.created'];
            $request_params = $request->request->all() ?: json_decode($request->getContent(), true);

            // Skip Workflow
            if (isset($request_params['skipWorkflow']) && true === $request_params['skipWorkflow'] && in_array($params['event'], $skippableEvents)) {
                return;
            }
        }

        try {
            if($params['entity']) {
                // dump($params);die;
                $this->activityEvent->setParameters($params);
                $this->eventDispatcher->dispatch($params['event'], $this->activityEvent);
            }
        } catch(\Exception $e) {
            if($this->container->get('request')->server->get('SCRIPT_NAME') == '/app_dev.php'){
                dump($e);
                die;
            } else {
                $logger = $this->container->get('paypal_logger');
                $logger->error("Error : ".$e->getMessage());
            }
        }
    }
}
