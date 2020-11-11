<?php

/**
 *
 * @package    Zalt
 * @subpackage Loader\Translate
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

namespace Zalt\Loader\Translate;

/**
 * Add auto translate functions to a class
 *
 * @package    Zalt
 * @subpackage Loader\Translate
 * @license    No free license, do not copy
 * @since      Class available since version 1.9.1
 */
class TranslateableAbstract extends \Zalt\Loader\Target\TargetAbstract
{
    use TranslateableTrait;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        // parent::afterRegistry();

        $this->initTranslateable();
    }
}