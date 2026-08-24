<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\fixtures\acme_admin_bundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Stands for an installed bundle shipping its own Stimulus controllers, contributed
 * the way bundles in the wild do it. It is deliberately not written defensively: the
 * point of the fixture is that such a bundle must not be able to unregister the
 * controllers of the application installing it.
 */
class AcmeAdminBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return $this->extension ??= new AcmeAdminExtension();
    }
}

class AcmeAdminExtension extends Extension implements PrependExtensionInterface
{
    public function getAlias(): string
    {
        return 'acme_admin';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('stimulus', [
            'controller_paths' => [__DIR__.'/assets/controllers'],
        ]);
    }
}
