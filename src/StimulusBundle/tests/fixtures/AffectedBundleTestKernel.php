<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\fixtures;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\StimulusBundle\Tests\fixtures\acme_admin_bundle\AcmeAdminBundle;

/**
 * An application that installs a bundle shipping its own Stimulus controllers, and
 * that does not configure "stimulus" at all — so its own controllers directory is
 * only known through the default behaviour of StimulusBundle.
 */
class AffectedBundleTestKernel extends Kernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new StimulusBundle(),
            new AcmeAdminBundle(),
        ];
    }

    public function getProjectDir(): string
    {
        return __DIR__.'/affected_app';
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache';
    }

    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('stimulus.asset_mapper.controllers_map_generator')->setPublic(true);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'foo000',
            'http_method_override' => false,
            'asset_mapper' => [
                'paths' => [
                    'assets' => '',
                    __DIR__.'/acme_admin_bundle/assets' => 'acme/admin-bundle',
                ],
                'importmap_path' => '%kernel.project_dir%/importmap.php',
            ],
            'test' => true,
            'php_errors' => ['log' => true],
        ]);

        // NOTE: "stimulus" is deliberately left unconfigured.
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->register('logger', NullLogger::class);
        $container->addCompilerPass($this, PassConfig::TYPE_BEFORE_REMOVING);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
    }
}
