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

use Psr\Container\ContainerInterface;
use Zalt\Loader\DependencyResolver\ConstructorDependencyResolver;
use Zalt\Loader\DependencyResolver\ResolverInterface;
use Zalt\Loader\DependencyResolver\SimpleResolver;
use Zalt\Loader\Exception\LoadException;
use Zalt\Loader\Target\TargetInterface;

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
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ResolverInterface
     */
    protected $dependencyResolver;

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
    protected $overloaders = [
        'Zalt' => 'Zalt',
        'Laminas' => 'Laminas',
        'Zend' => 'Zend',
    ];

    /**
     *
     * @var boolean
     */
    protected $requireServiceManager = true;

    /**
     * @var \MUtil\Source
     */
    protected $source;

    /**
     * Global overrule verbose switch
     *
     * @var boolean
     */
    public static $verbose = false;

    /**
     * Show all load attempts until successful
     *
     * @var boolean
     */
    public static $verboseLoad = false;

    /**
     * Show all unsuccessful target set attempts
     *
     * @var boolean
     */
    public static $verboseTarget = false;

    /**
     *
     * @param array $overloaders New overloaders
     * @param boolean $add       Add to default overloaders
     */
    public function __construct(ContainerInterface $container, array $overloaders = array(), $add = true)
    {
        $this->container = $container;

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
    public function addOverloaders(array $overloaders = [])
    {
        foreach($overloaders as &$overloader) {
            $overloader = str_replace('_', '\\', $overloader);
        }
        $this->setOverloaders($overloaders + $this->overloaders);

        return $this;
    }

    public function applyToLegacyTarget(\MUtil\Registry\TargetInterface $target)
    {
        $verbose = self::$verbose || self::$verboseTarget;

        foreach ($target->getRegistryRequests() as $name) {
            $className = ucfirst($name);
            if ($this->legacyPrefix) {
                $className = $this->legacyPrefix . $className;
            }
            if ($this->container->has($className)) {
                $target->answerRegistryRequest($name, $this->container->get($className));
            } elseif ($verbose) {
                echo sprintf("Could not find target name: %s for object of type %s.<br/>\n", $name, get_class($target));
            }
        }

        if ($this->source instanceof \MUtil\Registry\SourceInterface) {
            return $this->source->applySource($target);
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
        $verbose = self::$verbose || self::$verboseTarget;

        foreach ($target->getRegistryRequests() as $name) {
            if ($this->container->has($name)) {
                $target->answerRegistryRequest($name, $this->container->get($name));
            } elseif ('loader' == $name) {
                $target->answerRegistryRequest($name, $this);
            } elseif ($verbose) {
                echo "Could not find target name: $name<br/>\n";
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
     * try to create 'Zalt\MyClass' or else 'Laminas\MyClass' or whatever overloader
     * directories have been set.
     *
     * You can add parameters: $this->create('MyClass', 'x', 'y') will
     * create e.g.: new \Zalt\MyClass('x', 'y')
     *
     * In general this function tries to make sense of what is passed: an array
     * that is not callable is assumed to consist of the className followed by
     * arguments. A callable is called and then the result is processed. If an
     * object is passed or the result of the previous processing it is just
     * checked for having to call applyToTarget().
     *
     * The result is that all these functions result in: new \Zalt\MyClass('x', 'y')
     * <code>
     *  $this->create('MyClass', 'x', 'y');
     *  $this->create(['MyClass', 'x', 'y']);
     *  $this->create(function () { return 'MyClass'; }, 'x', 'y');
     *  $this->create(function () { return new \Zalt\MyClass('x', 'y'); });
     *  $this->create(new \Zalt\MyClass('x', 'y'));
     *
     *  // Output in all cases:
     *  // new \Zalt\MyClass('x', 'y')
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

        if (is_callable($className) && (! moethod_exists($className, '__invoke'))) {
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

            $resolver = $this->getDependencyResolver();
            $params   = $resolver->resolve($class, $this->container, $arguments);

            $object = new $class(...$params);
        }

        if ($object instanceof TargetInterface) {
            $this->applyToTarget($object);
        }

        if ($this->legacyClasses && $object instanceof \MUtil\Registry\TargetInterface) {
            $this->applyToLegacyTarget($object);
        }

        return $object;
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

        /*if ($this->legacyClasses) {
            foreach ($this->overloaders as $folder) {
                $overloaders[] = $folder . '_' . $subFolder;
            }
        }*/

        foreach ($this->overloaders as $folder) {
            $overloaders[] = $folder . '\\' . $subFolder;
        }

        $subOverloader = new self($this->container, $overloaders, false);
        if ($this->legacyClasses) {
            $subOverloader->legacyClasses = true;
            $subOverloader->legacyPrefix  = $this->legacyPrefix;
        }

        if ($this->source instanceof \MUtil\Registry\SourceInterface) {
            return $subOverloader->setSource($this->source);
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

        $verbose = self::$verbose || self::$verboseLoad;
        // echo "[$verbose] [" . self::$verbose . "] [" . self::$verboseLoad . "]<br/>\n";
        $className = $this->formatName($className);

        foreach ($this->overloaders as $prefix) {
            $class = $this->findForPrefix($className, $prefix, $verbose);
            if ($class) {
                return $class;
            }
        }

        throw new LoadException("Could not load class .\\$className for any of the parent namespaces: "
            . implode(', ', $this->overloaders));
    }

    /**
     *
     * @param string $className
     * @param string $prefix
     * @param boolean $verbose
     * @return string
     */
    protected function findForPrefix($className, $prefix, $verbose)
    {
        $class = $prefix . '\\' . $className;
        if ($verbose) {
            echo "Load attempt $class<br/>\n";
        }

        if (class_exists($class, false)) {
            if ($verbose) {
                echo "Load attempt successful! $class<br/>\n";
            }
            return $class;
        }

        if ($this->legacyClasses) {
            $legacyClass = '\\' . strtr($class, '\\', '_');
            if ($verbose) {
                echo "Load attempt $legacyClass<br/>\n";
            }
            if (class_exists($legacyClass, true)) {
                if ($verbose) {
                    echo "Load successful! $legacyClass<br/>\n";
                }
                return $legacyClass;
            }
        }

        if (class_exists($class, true)) {
            if ($verbose) {
                echo "Load attempt successful! $class<br/>\n";
            }
            return $class;
        }
        if ($verbose) {
            echo "Load attempt $class failed<br/>\n";
        }
    }

    /**
     * Normalize class name
     *
     * @param  string $name
     * @return string
     */
    protected function formatName($name)
    {
        return ucfirst(strtr((string) $name, '_', '\\'));
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getDependencyResolver(): ResolverInterface
    {
        if (! $this->dependencyResolver) {
            $this->dependencyResolver = new SimpleResolver();
        }

        return $this->dependencyResolver;
    }

    /**
     *
     * @return \Zalt\Loader\ProjectOverloader
     */
    public static function getInstance()
    {
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
     * @param ResolverInterface $dependencyResolver
     */
    public function setDependencyResolver(ResolverInterface $dependencyResolver): void
    {
        $this->dependencyResolver = $dependencyResolver;
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
     * @param \MUtil\Registry\SourceInterface $source
     */
    public function setSource(\MUtil\Registry\SourceInterface $source)
    {
        $this->source = $source;

        return $this;
    }
}
