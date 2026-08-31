<?php declare(strict_types=1);

namespace Common\Service\Job\DispatchStrategy;

use Common\Job\DispatchStrategy\SynchronousMessenger;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SynchronousMessengerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        // The configured synchronous strategy is wrapped, so the improvements
        // of the other modules (module Log) are kept.
        return new SynchronousMessenger(
            $services->get('Omeka\Job\DispatchStrategy\Synchronous'),
            $services->get('Omeka\Logger'),
            $services->get('ControllerPluginManager')
        );
    }
}
