<?php

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Zalt\Loader;

use Zalt\Mock\SimpleServiceManager;

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.3 Aug 30, 2018 12:43:24 PM
 */
class TargetLoaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     *
     * @var ProjectOverloader
     */
    protected $overLoader;

    protected SimpleServiceManager $sm;

    /**
     *
     * @return ProjectOverloader
     */
    public function getProjectOverloader()
    {
        return $this->overLoader;
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->sm = new SimpleServiceManager([
            'var321' => new \Test3\In3and2and1(),
            'var31' => new \Test3\In3and1(null),
            ]);

        $this->overLoader = new ProjectOverloader($this->sm, [
            'Test3',
            'Test2',
            'Test1',
            ]);

//        $this->overLoader->createServiceManager([
//            'var321' => 'Test3\In3and2and1',
//            'var31' => ['Test3\In3and1', [null]],
//            ]);
    }

    public function testClassLoader()
    {
        $loader = $this->getProjectOverloader();
        $class =  $loader->create('Target1');

        $this->assertInstanceOf('Test1\Target1', $class);
        $this->assertInstanceOf('Zalt\Loader\ProjectOverloader', $class->loader);
        $this->assertInstanceOf('Test3\In3and1', $class->var31);
        $this->assertInstanceOf('Test3\In3and2and1', $class->var321);
    }

}
