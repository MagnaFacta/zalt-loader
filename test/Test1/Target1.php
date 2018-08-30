<?php

/**
 *
 * @package    Test1
 * @subpackage Target1
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Expression copyright is undefined on line 44, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 */

namespace Test1;

use Zalt\Loader\ProjectOverloader;
use Zalt\Loader\Target\TargetAbstract;

/**
 *
 * @package    Test1
 * @subpackage Target1
 * @copyright  Expression copyright is undefined on line 56, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.3 Aug 30, 2018 12:41:07 PM
 */
class Target1 extends TargetAbstract
{
    /**
     *
     * @var ProjectOverloader
     */
    public $loader;

    /**
     *
     * @var \Test3\In3and1
     */
    public $var31;

    /**
     *
     * @var \Test3\In3and2and1
     */
    public $var321;
}
