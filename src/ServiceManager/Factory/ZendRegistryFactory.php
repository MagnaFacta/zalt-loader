<?php

/**
 *
 * @package    ServiceManager
 * @subpackage Factory
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

namespace Zalt\Loader\ServiceManager\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 *
 * @package    ServiceManager
 * @subpackage Factory
 * @license    No free license, do not copy
 * @since      Class available since version 1.9.1
 */
class ZendRegistryFactory implements \Laminas\ServiceManager\Factory\AbstractFactoryInterface
{
    /**
     * @var \Zend_Registry
     */
    protected $registry;

    /**
     * ZendRegistryFactory constructor.
     *
     * @param null|\Zend_Registry $registry
     */
    public function __construct(\Zend_Registry $registry = null) 
    {
        if ($registry) {
            $this->registry = $registry;
        } else {
            $this->registry = \Zend_Registry::getInstance();
        }
    }
    
    protected function _findName($requestedName)
    {
        if ($this->registry->offsetExists($requestedName)) {
            return $requestedName;
        }
        $lname = lcfirst($requestedName);
        if ($this->registry->offsetExists($lname)) {
            return $lname;
        }
        
        return null;
    }
    
    
    /**
     * Factory can create the service if there is a key for it in the registry
     *
     * {@inheritdoc}
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        // echo '[' . $requestedName . "]\n";
        return (boolean) $this->_findName($requestedName); 
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $name = $this->_findName($requestedName);
        
        if (! $name) {
            throw new ServiceNotCreatedException(sprintf('Cannot find %s in the Zend Registry.', $requestedName));
        }
        
        return $this->registry->offsetGet($name);
    }
}