<?php

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 MagnaFacta
 * @license    New BSD License
 */
namespace Zalt\Loader;

/**
 *
 *
 * @package    Zalt
 * @subpackage Loader
 * @copyright  Copyright (c) 2016 MagnaFacta
 * @license    New BSD License
 * @since      Class available since version 1.8.1 Oct 18, 2016 6:11:22 PM
 */
class ProjectOverloader
{
    /**
     *
     * @var ProjectOverloader
     */
    private static $_instance;

    /**
     *
     * @var boolean
     */
    protected $_enabled = true;

    /**
     * Array Prefix => Prefix of classes possibly overloaded
     *
     * @var array
     */
    protected $_overloaders = array(
        'Zalt' => 'Zalt',
        'Zend' => 'Zend',
        );

    /**
     *
     * @param array $overloaders New overloaders
     * @param boolean $add       Add to default overloaders
     * @param boolean $enable    Enable project overloader
     */
    public function __construct(array $overloaders = array(), $add = true, $enable = true)
    {
        self::$_instance = $this;

        if ($overloaders) {
            if ($add) {
                $this->addOverloaders($overloaders);
            } else {
                $this->setOverloaders($overloaders);
            }
        }

        if ($enable) {
            $this->enableOverloading();
        } else {
            $this->disableOverloading();
        }
    }

    /**
     *
     * @param array $overloaders New overloaders, first overloader is tried first
     * @return \Zalt\Loader\ProjectOverloader
     */
    public function addOverloaders(array $overloaders = array())
    {
        $this->setOverloaders($overloaders + $this->_overloaders);

        return $this;
    }

    /**
     * Loads the given class or interface.
     *
     * @param  string    $className The name of the class, minus the prefix
     * @param  array     $arguments Class loadiung arguments
     * @return object    The created object
     */
    public function create($className, ...$arguments)
    {
        if (! $this->_enabled) {
            return null;
        }

        foreach ($this->_overloaders as $prefix) {
            $class = $prefix . '\\' . $className;

            echo "$class\n";
            if (class_exists($class, true)) {
                return new $class(...$arguments);
            }
        }
    }

    /**
     *
     * @return \Zalt\Loader\ProjectOverloader
     */
    public function disableOverloading()
    {
        $this->_enabled = false;

        return $this;
    }

    /**
     *
     * @return \Zalt\Loader\ProjectOverloader
     */
    public function enableOverloading()
    {
        $this->_enabled = true;

        return $this;
    }

    /**
     *
     * @return \Zalt\Loader\ProjectOverloader
     */
    public static function getInstance()
    {
        if (! self::$_instance instanceof ProjectOverloader) {
            new ProjectOverloader();
        }

        return self::$_instance;
    }

    /**
     *
     * @return array Current overloaders
     */
    public function getOverloaders()
    {
        return $this->_overloaders;
    }

    /**
     *
     * @return boolean True when project overloading is enabled
     */
    public function getOverloading()
    {
        return $this->_enabled;
    }

    /**
     *
     * @param array $overloaders New overloaders
     * @return \Zalt\Loader\ProjectOverloader
     */
    public function setOverloaders(array $overloaders = array())
    {
        $this->_overloaders = array_combine($overloaders, $overloaders);

        return $this;
    }
}
