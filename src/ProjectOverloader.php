<?php

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 MagnaFacta
 * @license    New BSD License
 */
namespace Zalt\Loader;

use Zalt\Loader\Exception\LoadException;
use Zalt\Loader\Target\TargetInterface;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 *
 *
 * @package    Zalt
 * @subpackage Loader
 * @copyright  Copyright (c) 2016 MagnaFacta
 * @license    New BSD License
 * @since      Class available since version 1.8.1 Oct 18, 2016 6:11:22 PM
 */
class ProjectOverloader
{
    /**
     *
     * @var ProjectOverloader
     */
    private static $_instance;

    /**
     *
     * @var boolean
     */
    protected $_requireServiceManager = true;

    /**
     * Array Prefix => Prefix of classes possibly overloaded
     *
     * @var array
     */
    protected $_overloaders = array(
        'Zalt' => 'Zalt',
        'Zend' => 'Zend',
        );

    /**
     *
     * @var ServiceLocatorInterface
     */
    protected $_serviceManager;

    /**
     * Global overrule verbose switch
     *
     * @var boolean
     */
    public $verbose = false;

    /**
     * Show all load attempts until successful
     *
     * @var boolean
     */
    public $verboseLoad = false;

    /**
     * Show all unsuccesful targer set attempts
     *
     * @var boolean
     */
    public $verboseTarget = false;

    /**
     *
     * @param array $overloaders New overloaders
     * @param boolean $add       Add to default overloaders
     */
    public function __construct(array $overloaders = array(), $add = true)
    {
        self::$_instance = $this;

        if ($overloaders) {
            if ($add) {
                $this->addOverloaders($overloaders);
            } else {
                $this->setOverloaders($overloaders);
            }
        }
    }

    /**
     *
     * @param array $overloaders New overloaders, first overloader is tried first
     * @return \Zalt\Loader\ProjectOverloader
     */
    public function addOverloaders(array $overloaders = array())
    {
        $this->setOverloaders($overloaders + $this->_overloaders);

        return $this;
    }

    /**
     *
     * @param TargetInterface $target
     * @return boolean
     * @throws LoadException
     */
    public function applyToTarget(TargetInterface $target)
    {
        if (! $this->_serviceManager instanceof ServiceLocatorInterface) {
            if ($this->_requireServiceManager) {
                throw new LoadException("Calling applyToTarger while ServiceManager is not set.");
            }

            return false;
        }

        $verbose = $this->verbose || $this->verboseTarget;

        foreach ($target->getRegistryRequests() as $name) {
            if ($this->_serviceManager->has($name)) {
                $target->answerRegistryRequest($name, $this->_serviceManager->get($name));
            } elseif ('loader' == $name) {
                $target->answerRegistryRequest($name, $this);
            } elseif ($verbose) {
                echo "Could not find target name: $name\n";
            }
        }

        $output = $target->checkRegistryRequestsAnswers();

        $target->afterRegistry();

        return $output;
    }

    /**
     * Loads the given class or interface.
     *
     * @param  string    $className The name of the class, minus the prefix
     * @param  array     $arguments Class loadiung arguments
     * @return object    The created object
     * @throws LoadException
     */
    public function create($className, ...$arguments)
    {
        $class = $this->find($className);
        if (! $class) {
            throw new LoadException("Xreate not load class .\\$className for any of the parent namespaces: "
                . implode(', ', $this->_overloaders));
        }

        $object = new $class(...$arguments);

        if ($object instanceof TargetInterface) {
            $this->applyToTarget($object);
        }

        return $object;
    }

    /**
     * Finds the given class or interface.
     *
     * @param  string $className The name of the class, minus the prefix
     * @return string The class name including the correct prefix
     * @throws LoadException
     */
    public function find($className)
    {
        $verbose = $this->verbose || $this->verboseLoad;

        foreach ($this->_overloaders as $prefix) {
            $class = $prefix . '\\' . $className;

            if ($verbose) {
                echo "Load attempt $class\n";
            }
            if (class_exists($class, true)) {
                if ($verbose) {
                    echo "Load succesful!\n";
                }
                return $class;
            }
        }

        throw new LoadException("Could not load class .\\$className for any of the parent namespaces: "
            . implode(', ', $this->_overloaders));
    }

    /**
     *
     * @return \Zalt\Loader\ProjectOverloader
     */
    public static function getInstance()
    {
        if (! self::$_instance instanceof ProjectOverloader) {
            new ProjectOverloader();
        }

        return self::$_instance;
    }

    /**
     *
     * @return array Current overloaders
     */
    public function getOverloaders()
    {
        return $this->_overloaders;
    }

    /**
     *
     * @return boolean True when project overloading is enabled
     */
    public function getOverloading()
    {
        return $this->_enabled;
    }

    /**
     * Get service manager
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceManager()
    {
        return $this->_serviceManager;
    }

    /**
     * Ignore missing service managers (e.g. for testing).
     *
     * @return \Zalt\Loader\ProjectOverloader
     */
    public function ignoreMissingServiceManager()
    {
        $this->_requireServiceManager = false;

        return $this;
    }

    /**
     * Is a missing service managers ignored (e.g. for testing).
     *
     * @return \Zalt\Loader\ProjectOverloader
     */
    public function isServiceManagerRequired()
    {
        return $this->_requireServiceManager;
    }

    /**
     * Is a service managers required when used.
     *
     * @return boolean
     */
    public function requireServiceManager()
    {
        return $this->_requireServiceManager;
    }

    /**
     *
     * @param array $overloaders New overloaders
     * @return \Zalt\Loader\ProjectOverloader
     */
    public function setOverloaders(array $overloaders = array())
    {
        $this->_overloaders = array_combine($overloaders, $overloaders);

        return $this;
    }

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceManager
     */
    public function setServiceManager(ServiceLocatorInterface $serviceManager)
    {
        $this->_serviceManager = $serviceManager;

        return $this;
    }
}
