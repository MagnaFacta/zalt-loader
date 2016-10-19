# Zend Alternative Project overloader

This module allows you to program you library / core application and allow projects using using that
code to create their own sub-classes and have those loaded instead of the original project.

For example

    use Zalt\Loader\ProjectOverloader;

    // ProjectOverloader add's the Zalt and Zend namespaces by itself
    $loader = new ProjectOverloader(['MyProject1', 'MyProject2']);

    $sm = $loader->create('EventManager\\EventManager', $config);

This code will create a new `Zend\EventManager\EventManager` with `$config` as parameter, unless you have
created a `MyProject2\EventManager\EventManager` then that will be loaded. When you have (also) created
a `MyProject1\EventManager\EventManager` then that class will be loaded.
