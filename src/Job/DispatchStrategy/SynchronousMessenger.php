<?php declare(strict_types=1);

namespace Common\Job\DispatchStrategy;

use Common\Log\Writer\Messenger as MessengerWriter;
use Laminas\Log\Logger;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Stdlib\SplPriorityQueue;
use Omeka\Entity\Job;
use Omeka\Job\DispatchStrategy\StrategyInterface;

/**
 * Run a job synchronously and display its log to the user.
 *
 * A synchronous job runs inside the web request, so its result is expected
 * right away, but it is only written to the job log. This strategy also
 * republishes the log to the messenger, so a quick job gives an instant
 * feedback, like a check done directly by a controller.
 *
 * It is a decorator, not a replacement: it must be passed explicitly to the
 * dispatcher, so the jobs of the other modules keep the standard behaviour,
 * and it wraps the configured synchronous strategy, whatever module provides
 * it.
 *
 * @see \Common\Log\Writer\Messenger
 */
class SynchronousMessenger implements StrategyInterface
{
    /**
     * @var \Omeka\Job\DispatchStrategy\StrategyInterface
     */
    protected $strategy;

    /**
     * @var \Laminas\Log\Logger
     */
    protected $logger;

    /**
     * @var \Laminas\Mvc\Controller\PluginManager
     */
    protected $controllerPlugins;

    public function __construct(
        StrategyInterface $strategy,
        Logger $logger,
        PluginManager $controllerPlugins
    ) {
        $this->strategy = $strategy;
        $this->logger = $logger;
        $this->controllerPlugins = $controllerPlugins;
    }

    public function send(Job $job): void
    {
        $writer = $this->addMessengerWriter();
        try {
            $this->strategy->send($job);
        } finally {
            $this->removeWriter($writer);
        }
    }

    /**
     * The messenger relies on the session, that may be unavailable (api, cli),
     * so a failure is not blocking: the log stays available in the job.
     */
    protected function addMessengerWriter(): ?MessengerWriter
    {
        // The job process (perform-job.php) runs the job synchronously too, but
        // there is no page to display messages on.
        if (PHP_SAPI === 'cli' || !$this->controllerPlugins->has('messenger')) {
            return null;
        }
        try {
            $writer = new MessengerWriter($this->controllerPlugins->get('messenger'));
        } catch (\Throwable $e) {
            return null;
        }
        $this->logger->addWriter($writer);
        return $writer;
    }

    /**
     * Remove a writer added for a single job from the shared logger.
     */
    protected function removeWriter(?MessengerWriter $writer): void
    {
        if (!$writer) {
            return;
        }
        // toArray() does not drain the queue, unlike a direct iteration.
        $writers = $this->logger->getWriters()->toArray();
        $this->logger->setWriters(new SplPriorityQueue());
        foreach ($writers as $existing) {
            if ($existing !== $writer) {
                $this->logger->addWriter($existing);
            }
        }
    }
}
