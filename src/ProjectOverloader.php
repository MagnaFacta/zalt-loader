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
        if (! self::$_instance instanceof self) {
            // Only set for initial overloader
            self::$_instance = $this;
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
                    . implode(', ', $this->_overloaders));
            }

            $object = new $class(...$arguments);
        }

        if ($object instanceof TargetInterface) {
            $this->applyToTarget($object);
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
        $sm = $this->create('ServiceManager\\ServiceManager', $serviceManagerConfig);

        if (! $this->_serviceManager) {
            $this->_serviceManager = $sm;
        }

        return $sm;
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
        foreach ($this->_overloaders as $folder) {
            $overloaders[] = $folder . DIRECTORY_SEPARATOR . $subFolder;
        }

        $ol = new self($overloaders, false);
        if ($this->_serviceManager instanceof ServiceLocatorInterface) {
            $ol->setServiceManager($this->_serviceManager);
        }
        return $ol;
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

        // Return full class specifications at once
        if (class_exists($className, true)) {
            return $className;
        }

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

        if (($serviceManager instanceof ServiceManager) && (! $serviceManager->has('loader'))) {
            $serviceManager->setService('loader', $this);
        }

        return $this;
    }
}
