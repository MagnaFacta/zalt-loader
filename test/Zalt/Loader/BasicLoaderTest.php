<?php

/**
 *
 * @package    Zalt-loader
 * @subpackage BasicLoaderTest
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Expression copyright is undefined on line 44, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 */

namespace Zalt\Loader;

use Zalt\Loader\ProjectOverloader;

/**
 *
 * @package    Zalt-loader
 * @subpackage BasicLoaderTest
 * @copyright  Expression copyright is undefined on line 56, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.3 Aug 16, 2018 12:55:49 PM
 */
class BasicLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var ProjectOverloader
     */
    protected $overLoader;

    /**
     *
     * @return ProjectOverloader
     */
    public function getBasicProjectOverloader()
    {
        return $this->overLoader;
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->overLoader = new ProjectOverloader([
            'Test3',
            'Test2',
            'Test1',
            ]);
    }

    public function providerLoadClass()
    {
        return [
            ['Test1', 'OnlyIn1'],
            ['Test2', 'OnlyIn2'],
            ['Test3', 'OnlyIn3'],
            ['Test3', 'In3and2and1'],
        ];
    }

    public function providerLoadInstanceOf()
    {
        return [
            ['In3and2and1', 'Test1\\In3and2and1'],
            ['In3and2and1', 'Test2\\In3and2and1'],
            ['In3and2and1', 'Test3\\In3and2and1'],
            ['In3and2', 'Test3\\In3and2'],
            ['In3and2', 'Test2\\In3and2'],
            ['In3and1', 'Test3\\In3and1'],
            ['In3and1', 'Test1\\In3and1'],
            ['In2and1', 'Test2\\In2and1'],
            ['In2and1', 'Test1\\In2and1'],
        ];
    }

    public function testClassLoader()
    {
        $loader = $this->getBasicProjectOverloader();
        $this->assertEquals(get_class($loader), 'Zalt\Loader\ProjectOverloader');
    }

    /**
     *
     * @param string $namespace
     * @param string $subClass
     *
     * @dataProvider providerLoadClass
     */
    public function testLoadClass($namespace, $subClass)
    {
        $loader = $this->getBasicProjectOverloader();
        $class  = $loader->find($subClass);
        $this->assertEquals($class, $namespace . '\\' . $subClass);
    }

    /**
     *
     * @param string $namespace
     * @param string $subClass
     *
     * @dataProvider providerLoadInstanceOf
     */
    public function testLoadInstanceOf($subClass, ...$instances)
    {
        $loader = $this->getBasicProjectOverloader();
        $class =  $loader->create($subClass);
        foreach ($instances as $instance) {
            $this->assertInstanceOf($instance, $class);
        }
    }
}
