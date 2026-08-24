<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\AssetMapper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\StimulusBundle\Tests\fixtures\AffectedBundleTestKernel;

/**
 * Boots a real application that installs a bundle contributing its own Stimulus
 * controllers through prepend(), and configures nothing itself.
 *
 * "controller_paths" used to carry the project directory as a node default value, and
 * a Config default is dropped as soon as any source sets the option — so the bundle's
 * prepend took its place and the application lost every one of its own controllers,
 * silently. This test is the regression guard for that.
 */
class AffectedBundleControllersMapTest extends TestCase
{
    protected function setUp(): void
    {
        (new Filesystem())->remove(__DIR__.'/../fixtures/affected_app/var');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(__DIR__.'/../fixtures/affected_app/var');
    }

    public function testTheApplicationControllersAreRegisteredNextToTheBundleOnes()
    {
        $kernel = new AffectedBundleTestKernel('test', true);
        $kernel->boot();

        $map = $kernel->getContainer()
            ->get('stimulus.asset_mapper.controllers_map_generator')
            ->getControllersMap();

        $names = array_keys($map);
        sort($names);

        $this->assertSame(['acme-widget', 'app-widget'], $names, "the application's own controllers must survive the bundle's prepend");
    }
}
