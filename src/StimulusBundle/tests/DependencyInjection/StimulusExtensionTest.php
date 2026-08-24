<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\StimulusBundle\DependencyInjection\StimulusExtension;

class StimulusExtensionTest extends TestCase
{
    private const PROJECT_DIR = __DIR__.'/../fixtures';
    private const BUNDLE_CONTROLLER_PATH = '/vendor/acme/admin-bundle/assets/controllers';

    public function testTheProjectControllersDirectoryIsRegisteredWhenNothingIsConfigured()
    {
        $this->assertSame(
            [self::PROJECT_DIR.'/assets/controllers'],
            $this->getControllerPaths([])
        );
    }

    /**
     * A bundle shipping Stimulus controllers contributes its own directory from
     * prepend(). The project directory must survive next to it: it used to be the
     * default value of the node, and a default is dropped as soon as any source sets
     * the option, so the application silently lost all of its own controllers.
     */
    public function testTheProjectControllersDirectorySurvivesABundleContributingItsOwn()
    {
        $this->assertSame(
            [self::BUNDLE_CONTROLLER_PATH, self::PROJECT_DIR.'/assets/controllers'],
            $this->getControllerPaths([['controller_paths' => [self::BUNDLE_CONTROLLER_PATH]]])
        );
    }

    /**
     * The project directory comes last on purpose: controllers are keyed by name and
     * the last path wins, so an application controller overrides a bundle one.
     */
    public function testTheProjectControllersDirectoryIsNotDuplicatedWhenAlreadyConfigured()
    {
        $this->assertSame(
            [self::PROJECT_DIR.'/assets/controllers', self::BUNDLE_CONTROLLER_PATH],
            $this->getControllerPaths([
                ['controller_paths' => [self::PROJECT_DIR.'/assets/controllers']],
                ['controller_paths' => [self::BUNDLE_CONTROLLER_PATH]],
            ])
        );
    }

    public function testNoControllersDirectoryIsRegisteredWhenTheProjectHasNone()
    {
        $container = new ContainerBuilder();
        // this directory has no "assets/controllers" child
        $container->setParameter('kernel.project_dir', __DIR__);

        (new StimulusExtension())->load([], $container);

        $this->assertSame([], $this->getControllerPathsArgument($container));
    }

    /**
     * @param array<array<string, mixed>> $configs
     *
     * @return list<string>
     */
    private function getControllerPaths(array $configs): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', self::PROJECT_DIR);

        (new StimulusExtension())->load($configs, $container);

        return $this->getControllerPathsArgument($container);
    }

    /**
     * @return list<string>
     */
    private function getControllerPathsArgument(ContainerBuilder $container): array
    {
        return $container
            ->findDefinition('stimulus.asset_mapper.controllers_map_generator')
            ->getArgument(2);
    }
}
