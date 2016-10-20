<?php

/**
 *
 * @package    Zalt
 * @subpackage Loader\Target
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 MagnaFacta
 * @license    New BSD License
 */

namespace Zalt\Loader\Target;

/**
 * The TargetInterface is a lightweight dependency injection framework that enables an
 * object to tell which central variables can/must be set.
 *
 * This allows sources containing variables, e.g. the ServiceManager, to have their values
 * automatically injected into the TargetInterface object.
 *
 * @package    Zalt
 * @subpackage Loader\Target
 * @copyright  Copyright (c) 2016 MagnaFacta
 * @license    New BSD License
 * @since      Class available since version 1.8.1 Oct 18, 2016 6:11:22 PM
 */
interface TargetInterface
{
    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry();

    /**
     * Allows the source to set request.
     *
     * @param string $name Name of resource to set
     * @param mixed $resource The resource.
     * @return boolean True if $resource was OK
     */
    public function answerRegistryRequest($name, $resource);

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers();

    /**
     * Allows the loader to know the resources to set.
     *
     * @return array of string names
     */
    public function getRegistryRequests();
}
