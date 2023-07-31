<?php

namespace Zalt\Loader\DependencyResolver;

use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Psr\Container\ContainerInterface;
use Zalt\Loader\Exception\DependencyNotFoundException;

/**
 * Resolves dependencies first based on supplied, matched by type params and otherwise through the container
 */
class OrderedParamsContainerResolver extends ConstructorDependencyResolver
{
    protected function resolveDependencies(ContainerInterface $container, array $askedDependencies, array $parameters = []): array
    {
        $parameterIndex = 0;
        return array_map(function(ReflectionParameter $dependency) use ($container, $parameters, &$parameterIndex) {
            if (array_key_exists($parameterIndex, $parameters)) {
                try {
                    $result = $this->resolveParameterDependencyByType($dependency, $parameters[$parameterIndex]);
                    $parameterIndex += 1;
                    return $result;
                } catch(DependencyNotFoundException) {
                    // Try a different resolve strategy
                }
            }
            if ($result = $this->resolveContainerDependency($dependency, $container)) {
                return $result;
            }
            $result = $this->resolveDefaultDependency($dependency);
            if ($result === null && array_key_exists($parameterIndex, $parameters) && $parameters[$parameterIndex] === null) {
                $parameterIndex += 1;
            }
            return $result;
        }, $askedDependencies);
    }

    protected function resolveParameterDependencyByType(ReflectionParameter $dependency, mixed $parameter): mixed
    {
        $reflectionDependencyType = $dependency->getType();

        $reflectionDependencyTypes = null;
        if ($reflectionDependencyType instanceof ReflectionNamedType) {
            $reflectionDependencyTypes = [$reflectionDependencyType];
        }
        if ($reflectionDependencyType instanceof ReflectionUnionType) {
            $reflectionDependencyTypes = $reflectionDependencyType->getTypes();
        }
        if ($reflectionDependencyTypes === null) {
            // Assume the supplied parameter fits..
            return $parameter;
        }

        $dependencyTypes = array_map(function($reflectionDependencyType) { return $reflectionDependencyType->getName(); }, $reflectionDependencyTypes);

        foreach($dependencyTypes as $dependencyType) {
            if ($parameter instanceof $dependencyType) {
                return $parameter;
            }
            if ($parameter instanceof ContainerInterface && $parameter->has($dependencyType)) {
                return $parameter->get($dependencyClassName);
            }

            if ($dependencyType === 'array' && is_array($parameter)) {
                return $parameter;
            }
            if ($dependencyType === 'int' && is_int($parameter)) {
                return $parameter;
            }
            if ($dependencyType === 'string' && is_string($parameter)) {
                return $parameter;
            }
            if ($dependencyType === 'bool' && is_bool($parameter)) {
                return $parameter;
            }
        }

        throw new DependencyNotFoundException();
    }
}