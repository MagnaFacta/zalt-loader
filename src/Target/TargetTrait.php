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
 * TargetTrait is a default target implementation, that requests variables
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
trait TargetTrait
{
    /**
     * Filters the names that should not be requested.
     *
     * Can be overriden.
     *
     * @param string $name
     * @return boolean
     */
    protected function _filterRequestNames($name)
    {
        return '_' !== $name[0];
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        // parent::afterRegistry();
    }

    /**
     * Allows the loader to set resources.
     *
     * @param string $name Name of resource to set
     * @param mixed $resource The resource.
     * @return boolean True if $resource was OK
     */
    public function answerRegistryRequest($name, $resource)
    {
        $this->$name = $resource;

        return true;
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return true;
    }

    /**
     * Allows the loader to know the resources to set.
     *
     * Returns those object variables defined by the subclass but not at the level of this definition.
     *
     * Can be overruled.
     *
     * @return array of string names
     */
    public function getRegistryRequests()
    {
        return array_filter(array_keys(get_object_vars($this)), array($this, '_filterRequestNames'));
    }
}
