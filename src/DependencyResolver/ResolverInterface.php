<?php

namespace Zalt\Loader\DependencyResolver;

use Psr\Container\ContainerInterface;

interface ResolverInterface
{
    public function resolve(string $requestedName, ContainerInterface $container, array $parameters = []);
}