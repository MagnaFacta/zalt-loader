<?php

namespace Zalt\Loader\DependencyResolver;

use Psr\Container\ContainerInterface;
use ReflectionParameter;
use ReflectionNamedType;

class ConstructorDependencyParametersResolver extends ConstructorDependencyResolver
{
    public function __construct(protected ?string $leftOverParameterName = null)
    {}

    protected function resolveDependencies(ContainerInterface $container, array $askedDependencies, array $parameters = []): array
    {
        return array_map(function(ReflectionParameter $dependency) use ($container, $parameters) {
            if ($this->leftOverParameterName !== null && $dependency->getName() === $this->leftOverParameterName) {
                return $parameters;
            }
            if ($result = $this->resolveParameterDependency($dependency, $parameters)) {
                return $result;
            }
            if ($result = $this->resolveContainerDependency($dependency, $container)) {
                return $result;
            }
            return $this->resolveDefaultDependency($dependency);
        }, $askedDependencies);
    }

    protected function resolveParameterDependency(ReflectionParameter $dependency, array $parameters): mixed
    {
        $dependencyType = $dependency->getType();
        if ($dependencyType instanceof ReflectionNamedType) {
            $dependencyClassName = $dependencyType->getName();
            foreach ($parameters as $parameter) {
                if ($parameter instanceof $dependencyClassName) {
                    return $parameter;
                }
                if ($parameter instanceof ContainerInterface) {
                    if ($parameter->has($dependencyClassName)) {
                        return $parameter->get($dependencyClassName);
                    }
                }
            }
        }

        $dependencyName = $dependency->getName();
        if (null !== $dependencyName) {
            foreach ($parameters as $parameter) {
                if ($parameter instanceof $dependencyName) {
                    return $parameter;
                }
                if ($parameter instanceof ContainerInterface) {
                    if ($parameter->has($dependencyName)) {
                        return $parameter->get($dependencyName);
                    }
                }
            }
        }
        
        return null;
    }
}