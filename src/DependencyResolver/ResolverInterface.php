<?php

namespace Zalt\Loader\DependencyResolver;

use Psr\Container\ContainerInterface;

interface ResolverInterface
{
    public function resolve(ContainerInterface $container, string $requestedName, array $parameters = []);
}