<?php

namespace SlmQueueTest\Listener\Strategy;

use PHPUnit_Framework_TestCase;
use SlmQueue\Strategy\MaxRunsStrategy;
use SlmQueue\Strategy\MaxTimeStrategy;
use SlmQueue\Worker\Event\BootstrapEvent;
use SlmQueue\Worker\Event\WorkerEventInterface;
use SlmQueue\Worker\Event\ProcessQueueEvent;
use SlmQueue\Worker\Event\ProcessStateEvent;
use SlmQueue\Worker\Result\ExitWorkerLoopResult;
use SlmQueueTest\Asset\SimpleJob;
use SlmQueueTest\Asset\SimpleWorker;

class MaxTimeStrategyTest extends PHPUnit_Framework_TestCase
{
    protected $queue;
    protected $worker;
    /** @var MaxTimeStrategy */
    protected $listener;

    public function setUp()
    {
        $this->queue    = $this->getMock(\SlmQueue\Queue\QueueInterface::class);
        $this->worker   = new SimpleWorker();
        $this->listener = new MaxTimeStrategy();
    }

    public function testListenerInstanceOfAbstractStrategy()
    {
        static::assertInstanceOf(\SlmQueue\Strategy\AbstractStrategy::class, $this->listener);
    }

    public function testMaxTimeDefault()
    {
        static::assertEquals(3600, $this->listener->getMaxTime());
    }

    public function testMaxRunsSetter()
    {
        $this->listener->setMaxTime(7200);

        static::assertEquals(7200, $this->listener->getMaxTime());
    }

    public function testListensToCorrectEventAtCorrectPriority()
    {
        $evm = $this->getMock(\Zend\EventManager\EventManagerInterface::class);
        $priority = 1;

        $evm->expects($this->at(0))->method('attach')
            ->with(WorkerEventInterface::EVENT_BOOTSTRAP, [$this->listener, 'onBootstrap'], 1);
        $evm->expects($this->at(1))->method('attach')
            ->with(WorkerEventInterface::EVENT_PROCESS_QUEUE, [$this->listener, 'checkRuntime'], -1000);
        $evm->expects($this->at(2))->method('attach')
            ->with(WorkerEventInterface::EVENT_PROCESS_IDLE, [$this->listener, 'checkRuntime'], -1000);
        $evm->expects($this->at(3))->method('attach')
            ->with(WorkerEventInterface::EVENT_PROCESS_STATE, [$this->listener, 'onReportQueueState'], 1);

        $this->listener->attach($evm, $priority);
    }

    public function testOnStopConditionCheckHandler()
    {
        $this->listener->setMaxTime(2);

        $this->listener->onBootstrap(new BootstrapEvent($this->worker, $this->queue));

        $result = $this->listener->checkRuntime(new ProcessQueueEvent($this->worker, $this->queue));
        static::assertNull($result);

        $stateResult = $this->listener->onReportQueueState(new ProcessStateEvent($this->worker));
        static::assertContains(' seconds passed', $stateResult->getState());

        sleep(3);

        $result = $this->listener->checkRuntime(new ProcessQueueEvent($this->worker, $this->queue));
        static::assertNotNull($result);
        static::assertInstanceOf(ExitWorkerLoopResult::class, $result);
        static::assertContains('maximum of 2 seconds passed', $result->getReason());

        $stateResult = $this->listener->onReportQueueState(new ProcessStateEvent($this->worker));
        static::assertContains('3 seconds passed', $stateResult->getState());
    }
}
