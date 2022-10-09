<?php

namespace Zalt\Loader\DependencyResolver;

use Psr\Container\ContainerInterface;

class SimpleResolver implements ResolverInterface
{
    public function resolve(string $requestedName, ContainerInterface $container, array $parameters = [])
    {
        return $parameters;
    }
}