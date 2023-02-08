<?php

namespace Zalt\Loader;

use Psr\Container\ContainerInterface;
use Zalt\Loader\DependencyResolver\ConstructorDependencyResolver;

class ConstructorProjectOverloader extends ProjectOverloader
{
    public function __construct(ContainerInterface $container, array $overloaders = array(), $add = true)
    {
        parent::__construct($container, $overloaders, $add);
        $this->setDependencyResolver(new ConstructorDependencyResolver());
    }
}
