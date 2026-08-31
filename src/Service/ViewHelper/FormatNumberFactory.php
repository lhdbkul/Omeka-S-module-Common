<?php declare(strict_types=1);

namespace Common\Service\ViewHelper;

use Common\View\Helper\FormatNumber;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class FormatNumberFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        return new FormatNumber(
            $services->get(\Laminas\I18n\Translator\TranslatorInterface::class)->getDelegatedTranslator()
        );
    }
}
