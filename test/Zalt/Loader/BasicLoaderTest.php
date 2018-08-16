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
    public function testFirst()
    {
        $loader = new ProjectOverloader([
            'Test1',
            'Test2',
            'Test3',
            ]);
        $this->assertEquals(get_class($loader), 'Zalt\Loader\ProjectOverloader');
    }
}
