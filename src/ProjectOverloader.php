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
        'Zend' => 'Zend',
        'Zalt' => 'Zalt',
        );

    /**
     * The previous loadrs
     * @var array Callables
     */
    protected $_previousLoaders;

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

        $this->_previousLoaders = spl_autoload_functions();

        spl_autoload_register(array($this, 'loadClass'), true, true);
    }

    /**
     *
     * @param array $overloaders New overloaders, if overloader exists already the order is not changed
     * @return \Zalt\Loader\ProjectOverloader
     */
    public function addOverloaders(array $overloaders = array())
    {
        $this->setOverloaders($this->_overloaders + $overloaders);

        return $this;
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
     * Loads the given class or interface.
     *
     * @param  string    $class The name of the class
     * @return bool|null True if loaded, null otherwise
     */
    public function loadClass($class)
    {
        if (! $this->_enabled) {
            return null;
        }
        
        echo $class . "\n";
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
