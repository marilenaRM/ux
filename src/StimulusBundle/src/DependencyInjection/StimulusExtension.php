<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\DependencyInjection;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class StimulusExtension extends Extension implements PrependExtensionInterface, ConfigurationInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $config = $this->processConfiguration($this, $configs);

        $container->findDefinition('stimulus.asset_mapper.controllers_map_generator')
            ->replaceArgument(2, $this->getControllerPaths($config['controller_paths'], $container))
            ->replaceArgument(3, $config['controllers_json']);

        if (!class_exists(ImportMapConfigReader::class)) {
            $container->removeDefinition('stimulus.asset_mapper.auto_import_locator');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__.'/../../assets/dist' => '@symfony/stimulus-bundle',
                ],
                'excluded_patterns' => [
                    '*.d.ts',
                    '*/controllers.json',
                ],
            ],
        ]);
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('stimulus');
        $rootNode = $treeBuilder->getRootNode();
        \assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode
            ->children()
                ->arrayNode('controller_paths')
                    ->info('Directories scanned for Stimulus controllers. "%kernel.project_dir%/assets/controllers" is added automatically when it exists, so bundles can contribute their own directory without replacing it.')
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('controllers_json')
                    ->defaultValue('%kernel.project_dir%/assets/controllers.json')
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * The application's own controllers directory used to be the default value of the
     * "controller_paths" node. A node default only applies when *no* configuration
     * source sets the option, so a bundle prepending its own directory took the
     * default's place and silently unregistered every controller of the application:
     * pages kept rendering, no error was raised anywhere.
     *
     * It is therefore appended here instead, once every source has been merged. Last
     * position is deliberate: controllers are keyed by name and the last path wins, so
     * an application controller always takes precedence over a bundle one sharing its
     * name. Applications that already list the directory explicitly keep a single
     * entry, and applications that have no such directory get none — which also lets
     * an application that does not use Stimulus itself depend on a bundle that does.
     *
     * @param list<string> $controllerPaths
     *
     * @return list<string>
     */
    private function getControllerPaths(array $controllerPaths, ContainerBuilder $container): array
    {
        $projectControllerPath = $container->getParameter('kernel.project_dir').'/assets/controllers';

        if (\in_array($projectControllerPath, $controllerPaths, true)) {
            return $controllerPaths;
        }

        if (!$container->fileExists($projectControllerPath, false)) {
            return $controllerPaths;
        }

        $controllerPaths[] = $projectControllerPath;

        return $controllerPaths;
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
