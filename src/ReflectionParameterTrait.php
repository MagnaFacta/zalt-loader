<?php

// declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Loader;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;
use Zalt\Loader\Exception\ServiceNotCreatedException;
use Zalt\Loader\Exception\ServiceNotFoundException;

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @since      Class available since version 1.0
 */
trait ReflectionParameterTrait
{
    public function resolveConstructorArguments(ContainerInterface $container, $requestedName, int $firstParamsSkipped = 0): array
    {
        $reflector = new ReflectionClass($requestedName);

        if (! $reflector->isInstantiable()) {
            throw new ServiceNotCreatedException("Target class $requestedName is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            return [];
        }

        $askedDependencies = $constructor->getParameters();
        if ($firstParamsSkipped) {
            $askedDependencies = array_slice($askedDependencies, $firstParamsSkipped, null, true);
        }

        $results = $this->resolveDependencies($askedDependencies, $container);

        return $results;
    }

    protected function resolveDependencies(array $askedDependencies, ContainerInterface $container): array
    {
        return array_map(function(ReflectionParameter $dependency) use ($container) {
            $dependencyType = $dependency->getType();

            $dependencyName = null;
            if ($dependencyType instanceof \ReflectionNamedType) {
                $dependencyName = $dependencyType->getName();
            }

            // Check for aliases
            if (isset($this->aliases[$dependencyName])) {
                $dependencyName = $this->aliases[$dependencyName];
            }

            // return container if asked
            if ($dependencyName === ContainerInterface::class) {
                return $container;
            }

            if (null !== $dependencyName) {
                // Return named parameter
                if ($container->has($dependencyName)) {
                    return $container->get($dependencyName);
                }

                // Returned aliased service
                if ($container->has($dependency->getName())) {
                    return $container->get($dependency->getName());
                }
            }

            // Try the default value
            return $this->resolveDefault($dependency);
        }, $askedDependencies);
    }

    /**
     * If a construct parameters do not have a class declaration, see if it has a default value,
     * otherwise it can't be loaded
     *
     * @param ReflectionParameter $parameter
     * @return mixed Default value
     * @throws ServiceNotFoundException Dependency can't be resolved
     */
    protected function resolveDefault(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new ServiceNotFoundException("Dependency [$parameter->name] can't be resolved in class {$parameter->getDeclaringClass()->getName()}");
    }

}