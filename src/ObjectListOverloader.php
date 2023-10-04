<?php

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Zalt\Loader;

use Zalt\Loader\ProjectOverloader;
use Zalt\Loader\Target\TargetInterface;
use Zalt\Loader\Exception\LoadException;

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class ObjectListOverloader
{
    /**
     * The prefix/path location to look for classes.
     *
     * The standard value is
     * - <Project_name> => application/classes
     * - \Gems => library/Gems/classes
     *
     * But an alternative could be:
     * - Demopulse => application/classes
     * - Pulse => application/classes
     * - \Gems => library/Gems/classes
     *
     * @var array Of prefix => path strings for class lookup
     */
    protected $_dirs = [];

    /**
     * @var string Subdir for overloading
     */
    protected $_subDir = '';

    /**
     * @var \Zalt\Loader\ProjectOverloader
     */
    protected $_subLoader;
    
    public function __construct($subDir, ProjectOverloader $loader, array $dirs)
    {
        $this->_subDir = $subDir;
        
        $this->_subLoader = $loader->createSubFolderOverloader($this->_subDir);

        foreach ($dirs as $name => $dir) {
            $sub = $dir . DIRECTORY_SEPARATOR . $this->_subDir;
            if (file_exists($sub)) {
                $this->_dirs[$name] = $sub;
            }
        }
    }
    
    /**
     *
     * @param string $subType An subdirectory (may contain multiple levels split by '/'
     * @return array An array of type prefix => classname
     */
    protected function _getDirs($subType)
    {
        $paths = [];
        if (DIRECTORY_SEPARATOR == '/') {
            $mainDir = str_replace('\\', DIRECTORY_SEPARATOR, $subType);
        } else {
            $mainDir = $subType;
        }
        foreach ($this->_dirs as $name => $dir) {
            $prefix = $name . '\\'. $this->_subDir . '\\' . $subType . '\\';
            $fullPath = $dir . DIRECTORY_SEPARATOR . $mainDir;
            if (file_exists($fullPath)) {
                $paths[$prefix] = $fullPath;
            }
        }

        return $paths;
    }

    /**
     * Returns a list of selectable classes with an empty element as the first option.
     *
     * @param string $classType The class or interface that must me implemented
     * @param array  $paths Array of prefix => path to search
     * @param string $nameMethod The method to call to get the name of the class
     * @return array of classname => name
     */
    public function _listAll($classType, $paths, $nameMethod = 'getName')
    {
        $results   = array();

        foreach ($paths as $prefix => $path) {
            $parts = explode('_', $prefix, 2);

            try {
                $globIter = new \GlobIterator($path . DIRECTORY_SEPARATOR . '*.php');
            } catch (\RuntimeException $e) {
                // We skip invalid dirs
                continue;
            }

            foreach($globIter as $fileinfo) {
                $filename    = $fileinfo->getFilename();
                $className   = $prefix . substr($filename, 0, -4);
                $classNsName = '\\' . strtr($className, '_', '\\');
                // \MUtil\EchoOut\EchoOut::track($filename);
                // Take care of double definitions
                if (isset($results[$className])) {
                    continue;
                }

                if (! (class_exists($className, false) || class_exists($classNsName, false))) {
                    include($path . DIRECTORY_SEPARATOR . $filename);
                }

                if ((! class_exists($className, false)) && class_exists($classNsName, false)) {
                    $className = $classNsName;
                }
                $class = new $className();

                if ($class instanceof $classType) {
                    if ($class instanceof TargetInterface) {
                        $this->_subLoader->applyToTarget($class);
                    } elseif ($class instanceof \MUtil\Registry\TargetInterface) {
                        $this->_subLoader->applyToLegacyTarget($class);
                    } 

                    $results[$className] = trim($class->$nameMethod()) . ' (' . $className . ')';
                }
                // \MUtil\EchoOut\EchoOut::track($eventName);
            }

        }
        natcasesort($results);
        return $results;
    }

    /**
     * Returns a list of selectable screens with an empty element as the first option.
     *
     * @param string $subType The type (i.e. lookup directory with an associated class) of the parts to list
     * @param string $subClass The class / interface the object should have
     * @param string $function Function name to all on all objects
     * @return array of classname => name
     */
    public function listObjects($subType, $subClass, $function = 'getName')
    {
        $paths = $this->_getDirs($subType);

        return $this->_listAll($subClass, $paths, $function);
    }

    /**
     * Loads and initiates an screen class and returns the class (without triggering the screen itself).
     *
     * @param string $subName The class name of the individual screen to load
     * @param string $subType The type (i.e. lookup directory with an associated class) of the screen
     * @param string $subClass The class / interface the object should have
     * @return mixed $subClass or throws exeception
     */
    public function loadObject($subName, $subType, $subClass)
    {
        // \MUtil\EchoOut\EchoOut::track($subName);
        if (! class_exists($subName, true)) {
            throw new LoadException("The part '$subName' of type '$subType' cannot be loaded.");
        }

        $object = new $subName();

        if (! $object instanceof $subClass) {
            throw new LoadException("The part '$subName' of type '$subType' is not an instance of '$subClass'.");
        }

        if ($object instanceof TargetInterface) {
            $this->_subLoader->applyToTarget($object);
        } elseif ($object instanceof \MUtil\Registry\TargetInterface) {
            $this->_subLoader->applyToLegacyTarget($object);
        }

        return $object;
    }
}