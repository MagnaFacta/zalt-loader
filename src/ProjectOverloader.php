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
use Zend\ServiceManager\ServiceManager;

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
    private static $instanceOfSelf;

    /**
     * find classes with the old '_' namespace notation
     *
     * @var boolean
     */
    public $legacyClasses = false;

    /**
     * Prefix for legacy class names registered in the service manager.
     * For projects where such aliases can cause overlap (e.g. Should Zend DB1 or DB2 be used)
     *
     * @var string
     */
    public $legacyPrefix;

    /**
     * Array Prefix => Prefix of classes possibly overloaded
     *
     * @var array
     */
    protected $overloaders = array(
        'Zalt' => 'Zalt',
        'Zend' => 'Zend',
        );

    /**
     *
     * @var boolean
     */
    protected $requireServiceManager = true;

    /**
     *
     * @var ServiceLocatorInterface
     */
    protected $serviceManager;

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
     * Show all unsuccesful target set attempts
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
        if (! self::$instanceOfSelf instanceof self) {
            // Only set for initial overloader
            self::$instanceOfSelf = $this;
        }

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
        $this->setOverloaders($overloaders + $this->overloaders);

        return $this;
    }

    public function applyToLegacyTarget(\MUtil_Registry_TargetInterface $target)
    {
        if (! $this->serviceManager instanceof ServiceLocatorInterface) {
            if ($this->requireServiceManager) {
                throw new LoadException("Calling applyToTarget while ServiceManager is not set.");
            }

            return false;
        }

        $verbose = $this->verbose || $this->verboseTarget;

        foreach ($target->getRegistryRequests() as $name) {
            $className = ucfirst($name);
            if ($this->legacyPrefix) {
                $className = $this->legacyPrefix . $className;
            }
            if ($this->serviceManager->has($className)) {
                $target->answerRegistryRequest($name, $this->serviceManager->get($className));
            } elseif ($verbose) {
                echo "Could not find target name: $name\n";
            }
        }

        $output = $target->checkRegistryRequestsAnswers();

        $target->afterRegistry();

        return $output;
    }

    /**
     *
     * @param TargetInterface $target
     * @return boolean
     * @throws LoadException
     */
    public function applyToTarget(TargetInterface $target)
    {
        if (! $this->serviceManager instanceof ServiceLocatorInterface) {
            if ($this->requireServiceManager) {
                throw new LoadException("Calling applyToTarget while ServiceManager is not set.");
            }

            return false;
        }

        $verbose = $this->verbose || $this->verboseTarget;

        foreach ($target->getRegistryRequests() as $name) {
            if ($this->serviceManager->has($name)) {
                $target->answerRegistryRequest($name, $this->serviceManager->get($name));
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
     * Creates a new object of the given class.
     *
     * Simple use is with a string class name: $this->create('MyClass') will
     * try to create 'Zalt\MyClass' or else 'Zend\MyClass' or whatever overloader
     * directories have been set.
     *
     * You can add parameters: $this->create('MyClass', 'x', 'y') will
     * create e.g.: new Zalt\MyClass('x', 'y')
     *
     * In general this function tries to make sense of what is passed: an array
     * that is not callable is assumed to consist of the className followed by
     * arguments. A callable is called and then the result is processed. If an
     * object is passed or the result of the previous processing it is just
     * checked for having to call applyToTarget().
     *
     * The result is that all these functions result in: new Zalt\MyClass('x', 'y')
     * <code>
     *  $this->create('MyClass', 'x', 'y');
     *  $this->create(['MyClass', 'x', 'y']);
     *  $this->create(function () { return 'MyClass'; }, 'x', 'y');
     *  $this->create(function () { return new \Zalt\MyClass('x', 'y'); });
     *  $this->create(new \Zalt\MyClass('x', 'y'));
     *
     *  // Output in all cases:
     *  // new Zalt\MyClass('x', 'y')
     *
     *  // But this generates an error:
     *  $this->create(['MyClass',  'x'], 'y');
     * </code>
     *
     * @param  mixed  $className The name of the class, minus the prefix
     * @param  array  $arguments Class loading arguments
     * @return object The created object
     * @throws LoadException
     */
    public function create($className, ...$arguments)
    {
        if (is_array($className) && (! is_callable($className))) {
            if ($arguments) {
                throw new LoadException('Create() with an array $className cannot have any other arguments.');
            }

            $classTmp  = array_shift($className);
            $arguments = $className;
            $className = $classTmp;
        }

        if (is_callable($className) && (! method_exists($className, '__invoke'))) {
            $className = call_user_func_array($className, $arguments);
        }

        if (is_object($className)) {
            $object = $className;
        } else {
            $class = $this->find($className);
            if (! $class) {
                throw new LoadException("Create() could not load class .\\$className for any of the parent namespaces: "
                    . implode(', ', $this->overloaders));
            }

            $object = new $class(...$arguments);
        }

        if ($object instanceof TargetInterface) {
            $this->applyToTarget($object);
        }

        if ($this->legacyClasses && $object instanceof \MUtil_Registry_TargetInterface) {
            $this->applyToLegacyTarget($object);
        }

        return $object;
    }

    /**
     * Create a service manager using a simpler syntax to create factory objects created
     * though this loader.
     *
     * See the example for the possibilities:
     * <code>
     * $this->createServiceManager([
     *      'ob1' => 'Overloaded\\Class\\Created\\Without\\Arguments',
     *      'ob2' => 'Zend\Full\\Class\\Created\\Without\\Arguments',
     *      'ob3' => ['Overloaded\\Class\\Created\\With\\Arguments', ['Arg1', 'Arg2]],
     *      'ob4' => new InvokableFactoryEtcClass('x', 'y'),
     *      'ob5' => funtion () { return new stdClass() },
     * ]);
     * </code>
     *
     * This loader is automatically set as the loader
     *
     * This function also sets the service manager for this object if it was not yet set.
     *
     * @param array  $loaderFactories      string => string=classname|array[classname, params]|factory object
     * @param array  $serviceManagerConfig An optional full configuration array for the service manager,
     *                                     the previous $loaderFactories are added to this array.
     * @param string $loaderName           Name used to set this object to if not already set in the array. Disable
     *                                     this function by setting it to null or false.
     * @return ServiceManager
     */
    public function createServiceManager(array $loaderFactories = [], array $serviceManagerConfig = [], $loaderName = 'loader')
    {
        if ($loaderName && (! isset($serviceManagerConfig['services'][$loaderName]))) {
            $serviceManagerConfig['services'][$loaderName] = $this;
        }
        foreach ($loaderFactories as $name => $creator) {
            if (is_string($creator)) {
                $factor = $this->serviceManagerFactory($creator);
            } elseif (is_array($creator)) {
                $class  = array_shift($creator);
                $args   = array_shift($creator);
                $factor = $this->serviceManagerFactory($class, ...$args);
            } else {
                $factor = $creator;
            }
            $serviceManagerConfig['factories'][$name] = $factor;
        }
        $serviceManager = $this->create('ServiceManager\\ServiceManager', $serviceManagerConfig);

        if (! $this->serviceManager) {
            $this->serviceManager = $serviceManager;
        }

        return $serviceManager;
    }

    /**
     * Creates a copy of this overloader, but working on a sub folder
     *
     * @param string $subFolder
     * @return \self
     */
    public function createSubFolderOverloader($subFolder)
    {
        $overloaders = [];

        if ($this->legacyClasses) {
            foreach ($this->overloaders as $folder) {
                $overloaders[] = $folder . '_' . $subFolder;
            }
        }

        foreach ($this->overloaders as $folder) {
            $overloaders[] = $folder . '\\' . $subFolder;
        }

        $subOverloader = new self($overloaders, false);
        if ($this->legacyClasses) {
            $subOverloader->legacyClasses = true;
            $subOverloader->legacyPrefix  = $this->legacyPrefix;
        }

        if ($this->serviceManager instanceof ServiceLocatorInterface) {
            $subOverloader->setServiceManager($this->serviceManager);
            $subOverloader->legacyClasses = $this->legacyClasses;
            $subOverloader->legacyPrefix = $this->legacyPrefix;
        }
        return $subOverloader;
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
        // Return full class specifications immediately
        if (class_exists($className, true)) {
            return $className;
        }

        $verbose = $this->verbose || $this->verboseLoad;

        foreach ($this->overloaders as $prefix) {
            $class = $prefix . '\\' . $className;
            if ($verbose) {
                echo "Load attempt $class\n";
            }

            if ($this->legacyClasses) {
                $legacyClass = '\\' . strtr($class, '\\', '_');
                if ($verbose) {
                    echo "Load attempt $legacyClass\n";
                }
                if (class_exists($legacyClass, true)) {
                    if ($verbose) {
                        echo "Load successful! $class\n";
                    }
                    return $legacyClass;
                }
            }

            if (class_exists($class, true)) {
                if ($verbose) {
                    echo "Load attempt successful! $class\n";
                }
                return $class;

            } elseif ($verbose) {
                echo "Load attempt $class failed\n";
            }
        }

        throw new LoadException("Could not load class .\\$className for any of the parent namespaces: "
            . implode(', ', $this->overloaders));
    }

    /**
     *
     * @return \Zalt\Loader\ProjectOverloader
     */
    public static function getInstance()
    {
        if (! self::$instanceOfSelf instanceof ProjectOverloader) {
            new ProjectOverloader();
        }

        return self::$instanceOfSelf;
    }

    /**
     *
     * @return array Current overloaders
     */
    public function getOverloaders()
    {
        return $this->overloaders;
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
        return $this->serviceManager;
    }

    /**
     * Ignore missing service managers (e.g. for testing).
     *
     * @return \Zalt\Loader\ProjectOverloader
     */
    public function ignoreMissingServiceManager()
    {
        $this->requireServiceManager = false;

        return $this;
    }

    /**
     * Is a missing service managers ignored (e.g. for testing).
     *
     * @return \Zalt\Loader\ProjectOverloader
     */
    public function isServiceManagerRequired()
    {
        return $this->requireServiceManager;
    }

    /**
     * Is a service managers required when used.
     *
     * @return boolean
     */
    public function requireServiceManager()
    {
        return $this->requireServiceManager;
    }

    /**
     * Creates an object loader for the service manager
     *
     * @param  string    $className The name of the class, minus the prefix
     * @param  array     $arguments Class loadiung arguments
     * @return object    The created object
     * @throws LoadException
     */
    public function serviceManagerFactory($className, ...$arguments)
    {
        $this->find($className);
        $loader = $this;
        return function () use ($loader, $className, $arguments) {
            return $loader->create($className, ...$arguments);
        };
    }

    /**
     *
     * @param array $overloaders New overloaders
     * @return \Zalt\Loader\ProjectOverloader
     */
    public function setOverloaders(array $overloaders = array())
    {
        $this->overloaders = array_combine($overloaders, $overloaders);

        return $this;
    }

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceManager
     */
    public function setServiceManager(ServiceLocatorInterface $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        if (($serviceManager instanceof ServiceManager) && (! $serviceManager->has('loader'))) {
            $serviceManager->setService('loader', $this);
        }

        return $this;
    }
}
