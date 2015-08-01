<?php

namespace SlmQueue\Strategy\Factory;

use SlmQueue\Strategy\AttachQueueListenersStrategy;
use SlmQueue\Strategy\StrategyPluginManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * AttachQueueListenersStrategyFactory
 */
class AttachQueueListenersStrategyFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return AttachQueueListenersStrategy
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $sm             = $serviceLocator->getServiceLocator();
        $pluginManager  = $sm->get(StrategyPluginManager::class);
        $config         = $sm->get('Config');
        $strategyConfig = $config['slm_queue']['worker_strategies']['queues'];

        return new AttachQueueListenersStrategy($pluginManager, $strategyConfig);
    }
}
