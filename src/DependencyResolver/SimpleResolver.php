<?php

namespace Zalt\Loader\DependencyResolver;

use Psr\Container\ContainerInterface;

class SimpleResolver implements ResolverInterface
{
    public function resolve(ContainerInterface $container, string $requestedName, array $parameters = [])
    {
        return $parameters;
    }
}