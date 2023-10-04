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

use MUtil\Registry\SourceInterface;
use Psr\Container\ContainerInterface;
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
     * @var ResolverInterface
     */
    protected $dependencyResolver;

    /**
     *
     * @var ProjectOverloader
     */
    private static self|null $instanceOfSelf = null;

    /**
     * find classes with the old '_' namespace notation
     *
     * @var boolean
     */
    public bool $legacyClasses = false;

    /**
     * Prefix for legacy class names registered in the service manager.
     * For projects where such aliases can cause overlap (e.g. Should Zend DB1 or DB2 be used)
     *
     * @var string
     */
    public string|null $legacyPrefix = null;

    /**
     * Array Prefix => Prefix of classes possibly overloaded, ordered from low
     * to high prio (last prefix is tried first).
     *
     * @var array
     */
    protected array $overloaders = [
        'Zend' => 'Zend',
        'Laminas' => 'Laminas',
        'Zalt' => 'Zalt',
    ];

    /**
     *
     * @var boolean
     */
    protected bool $requireServiceManager = true;

    /**
     * @var \MUtil\Source
     */
    protected SourceInterface|null $source = null;

    /**
     * Global overrule verbose switch
     *
     * @var boolean
     */
    public static bool $verbose = false;

    /**
     * Show all load attempts until successful
     *
     * @var boolean
     */
    public static bool $verboseLoad = false;

    /**
     * Show all unsuccessful target set attempts
     *
     * @var boolean
     */
    public static bool $verboseTarget = false;

    /**
     * @param ContainerInterface $container
     * @param array $overloaders New overloaders
     * @param boolean $add       Add to default overloaders
     */
    public function __construct(
        protected readonly ContainerInterface $container,
        array $overloaders = [],
        bool $add = true)
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
     * @param array $overloaders Add overloaders to the stack. Added overloaders
     *                           have higher priority than existing ones (the last
     *                           prefix is tried first).
     *
     * @return self
     */
    public function addOverloaders(array $overloaders = []): self
    {
        foreach($overloaders as &$overloader) {
            $overloader = str_replace('_', '\\', $overloader);
        }
        $this->setOverloaders($this->overloaders + $overloaders);

        return $this;
    }

    public function applyToLegacyTarget(\MUtil\Registry\TargetInterface $target): bool
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
    public function applyToTarget(TargetInterface $target): bool
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
    public function create(mixed $className, mixed ...$arguments): object
    {
        return $this->createWithResolver($className, $this->getDependencyResolver(), ...$arguments);
    }

    /**
     * Creates a copy of this overloader, but working on a sub folder
     *
     * @param string $subFolder
     * @return self
     */
    public function createSubFolderOverloader(string $subFolder): self
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
        $subOverloader->setDependencyResolver($this->getDependencyResolver());

        if ($this->legacyClasses) {
            $subOverloader->legacyClasses = true;
            $subOverloader->legacyPrefix  = $this->legacyPrefix;
        }

        if ($this->source instanceof \MUtil\Registry\SourceInterface) {
            return $subOverloader->setSource($this->source);
        }

        return $subOverloader;
    }

    public function createWithResolver(mixed $className, ResolverInterface|string $resolver, mixed ...$arguments): object
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


            if (!$resolver instanceof ResolverInterface && class_exists($resolver)) {
                $resolver = new $resolver;
                if (!$resolver instanceof ResolverInterface) {
                    throw new LoadException(get_class($resolver) . ' is not a valid resolver');
                }
            }
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
     * Finds the given class or interface.
     *
     * @param  string $className The name of the class, minus the prefix
     * @return string The class name including the correct prefix
     * @throws LoadException
     */
    public function find(string $className): string
    {
        // Return full class specifications immediately
        if (class_exists($className, true)) {
            return $className;
        }

        $verbose = self::$verbose || self::$verboseLoad;
        // echo "[$verbose] [" . self::$verbose . "] [" . self::$verboseLoad . "]<br/>\n";
        $className = $this->formatName($className);

        foreach (array_reverse($this->overloaders) as $prefix) {
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
    protected function findForPrefix(string $className, string $prefix, bool $verbose): string|null
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
        return null;
    }

    /**
     * Normalize class name
     *
     * @param  string $name
     * @return string
     */
    protected function formatName(string $name): string
    {
        return ucfirst(strtr($name, '_', '\\'));
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function getDependencyResolver(): ResolverInterface
    {
        if (! $this->dependencyResolver) {
            // The resolver will default to just passing along the parameters provided.
            // To use more advanced resolving use the ConstructorDependencyResolver or
            // ConstructorDependencyParametersResolver 
            $this->dependencyResolver = new SimpleResolver();
        }

        return $this->dependencyResolver;
    }

    /**
     *
     * @return \Zalt\Loader\ProjectOverloader
     */
    public static function getInstance(): self
    {
        return self::$instanceOfSelf;
    }

    /**
     *
     * @return array Current overloaders
     */
    public function getOverloaders(): array
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
     * @return self
     */
    public function setOverloaders(array $overloaders = []): self
    {
        $this->overloaders = array_combine($overloaders, $overloaders);

        return $this;
    }

    /**
     * @param \MUtil\Registry\SourceInterface $source
     */
    public function setSource(SourceInterface $source)
    {
        $this->source = $source;

        return $this;
    }
}
