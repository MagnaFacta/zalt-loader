<?php

namespace Zalt\Loader\DependencyResolver;

use ReflectionClass;
use ReflectionParameter;
use ReflectionNamedType;
use Psr\Container\ContainerInterface;
use Zalt\Loader\Exception\ServiceNotCreatedException;
use Zalt\Loader\Exception\ServiceNotFoundException;

class ConstructorDependencyResolver implements ResolverInterface
{
    public function resolve(string $requestedName, ContainerInterface $container, array $parameters = []): array
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
        $results = $this->resolveDependencies($container, $askedDependencies, $parameters);
        return $results;
    }

    protected function resolveDependencies($container, array $askedDependencies, array $parameters): array
    {
        return array_map(function(ReflectionParameter $dependency) use ($container, $parameters) {
            if ($result = $this->resolveArrayDependency($dependency, $parameters)) {
                return $result;
            }
            if ($result = $this->resolveContainerDependency($dependency, $container)) {
                return $result;
            }
            return $this->resolveDefaultDependency($dependency);
        }, $askedDependencies);
    }

    protected function resolveArrayDependency(ReflectionParameter $dependency, array $parameters): mixed
    {
        $dependencyType = $dependency->getType();

        $dependencyTypeName = null;
        if ($dependencyType instanceof ReflectionNamedType) {
            $dependencyTypeName = $dependencyType->getName();
        }

        if (null !== $dependencyTypeName) {
            foreach ($parameters as $parameter) {
                if ($parameter instanceof $dependencyTypeName) {
                    return $parameter;
                }
            }
        }
        $dependencyName = $dependency->getName();
        if (null !== $dependencyName) {
            foreach ($parameters as $parameter) {
                if ($parameter instanceof $dependencyName) {
                    return $parameter;
                }
            }
        }

        return null;
    }

    protected function resolveContainerDependency(ReflectionParameter $dependency, ContainerInterface $container): mixed
    {
        $dependencyType = $dependency->getType();

        $dependencyName = null;
        if ($dependencyType instanceof ReflectionNamedType) {
            $dependencyName = $dependencyType->getName();
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

        return null;
    }

    /**
     * If a construct parameters do not have a class declaration, see if it has a default value,
     * otherwise it can't be loaded
     *
     * @param ReflectionParameter $parameter
     * @return mixed Default value
     * @throws ServiceNotFoundException Dependency can't be resolved
     */
    protected function resolveDefaultDependency(ReflectionParameter $parameter): mixed
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