<?php

/**
 * @package    Zalt
 * @subpackage Loader\Target
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 MagnaFacta
 * @license    New BSD License
 */

namespace Zalt\Loader\Target;

/**
 * TargetAbstract is a default target object, that requests variables
 * for all defined instance variables with names not starting with '_'.
 *
 * I.e. variables in a class inheriting from TargetAbstract can be
 * initialized by a source even when they are protected or private.
 *
 * This object is also usefull to copy the code to implement your own version of this class.
 *
 * @package    Zalt
 * @subpackage Loader\Target
 * @copyright  Copyright (c) 2016 MagnaFacta
 * @license    New BSD License
 * @since      Class available since version 1.8.1 Oct 18, 2016 6:11:22 PM
 */
abstract class TargetAbstract implements TargetInterface
{
    use TargetTrait;
}
