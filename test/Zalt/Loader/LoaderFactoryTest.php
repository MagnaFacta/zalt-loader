<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Loader;

use Zalt\Mock\SimpleServiceManager;

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @since      Class available since version 1.0
 */
class LoaderFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyConfig()
    {
        $input  = ProjectOverloaderFactory::$defaultOverLoaderPaths;
        $sm     = new SimpleServiceManager(['config' => [],]);
        $overFc = new ProjectOverloaderFactory();
        $over   = $overFc($sm);
        $result = array_combine($input, $input);

        $this->assertInstanceOf(ProjectOverloader::class, $over);
        $this->assertEquals($result, $over->getOverloaders());
    }

    public function testOnlyConfig()
    {
        $input  = ['XUtil', 'YUtil'];
        $sm     = new SimpleServiceManager(['config' => ['overLoader' => [
            'Paths' => $input,
            'AddTo' => false,
            ]]]);
        
        $overFc = new ProjectOverloaderFactory();
        $over   = $overFc($sm);
        $output = $input;
        $result = array_combine($output, $output);

        $this->assertInstanceOf(ProjectOverloader::class, $over);
        $this->assertEquals($result, $over->getOverloaders());
    }

    public function testSpecifiedConfig()
    {
        $input  = ['XUtil', 'YUtil'];
        $sm     = new SimpleServiceManager(['config' => ['overLoader' => [
            'Paths' => $input,
            'AddTo' => true,
            ]]]);
        $overFc = new ProjectOverloaderFactory();
        $over   = $overFc($sm);
        $output = array_merge($input, ['Zalt', 'Laminas', 'Zend']);  
        $result = array_combine($output, $output);

        $this->assertInstanceOf(ProjectOverloader::class, $over);
        $this->assertEquals($result, $over->getOverloaders());
    }
}