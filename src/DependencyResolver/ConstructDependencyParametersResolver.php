<?php

namespace Zalt\Loader\DependencyResolver;

use ReflectionParameter;
use ReflectionNamedType;

class ConstructDependencyParametersResolver extends ConstructDependencyResolver
{
    public function __construct(protected ?string $leftOverParameterName = null)
    {}

    protected function resolveDependencies($container, array $askedDependencies, array $parameters = []): array
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

    protected function resolveParameterDependency(ReflectionParameter $dependency, array &$parameters)
    {
        $dependencyType = $dependency->getType();
        if ($dependencyType instanceof ReflectionNamedType) {
            $dependencyClassName = $dependencyType->getName();
            if (array_key_exists($dependencyClassName, $parameters)) {
                $dependency = $parameters[$dependencyClassName];
                unset($parameters[$dependencyClassName]);
                return $dependency;
            }
        }

        $dependencyName = $dependency->getName();
        if (array_key_exists($dependencyName, $parameters)) {
            $dependency = $parameters[$dependencyName];
            unset($parameters[$dependencyName]);
            return $dependency;
        }

        return null;
    }
}