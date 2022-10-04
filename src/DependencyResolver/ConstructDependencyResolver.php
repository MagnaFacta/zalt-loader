<?php

namespace Zalt\Loader\DependencyResolver;

use ReflectionClass;
use ReflectionParameter;
use ReflectionNamedType;
use Psr\Container\ContainerInterface;
use Zalt\Loader\Exception\ServiceNotCreatedException;
use Zalt\Loader\Exception\ServiceNotFoundException;

class ConstructDependencyResolver implements ResolverInterface
{
    public function resolve(ContainerInterface $container, string $requestedName, array $parameters = [])
    {
        $reflector = new ReflectionClass($requestedName);

        if (! $reflector->isInstantiable()) {
            throw new ServiceNotCreatedException("Target class $requestedName is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            return new $requestedName;
        }

        $askedDependencies = $constructor->getParameters();
        $results = $this->resolveDependencies($container, $askedDependencies);

        return $results;
    }

    protected function resolveDependencies($container, array $askedDependencies): array
    {
        return array_map(function(ReflectionParameter $dependency) use ($container) {
            if ($result = $this->resolveContainerDependency($dependency, $container)) {
                return $result;
            }
            return $this->resolveDefaultDependency($dependency);
        }, $askedDependencies);
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