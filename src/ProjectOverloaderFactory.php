<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Loader;

use Psr\Container\ContainerInterface;

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @since      Class available since version 1.0
 */
class ProjectOverloaderFactory
{
    static public array $defaultOverLoaderPaths = ['Zalt', 'Laminas', 'Mezzio',  'Symfony', 'Zend'];
    
    public function __invoke(ContainerInterface $container)
    {
        /**
         * @var mixed[]
         */
        $config = $container->get('config');
        if (isset($config['overLoader']['Paths'])) {
            $overloaderPaths = (array) $config['overLoader']['Paths'];
        } else {
            $overloaderPaths = self::$defaultOverLoaderPaths;
        }
        $addTo = isset($config['overLoader']['AddTo']) ? (bool) $config['overLoader']['AddTo'] : true;
        
        $overloader = new ProjectOverloader($container, $overloaderPaths, $addTo);
        
        if (isset($config['overLoader']['LegacyPrefix'])) {
            $overloader->legacyClasses = true;
            $overloader->legacyPrefix = $config['overLoader']['LegacyPrefix'];
        }
        if (class_exists('MUtil\Model')) {
            \MUtil\Model::setSource($overloader->createSubFolderOverloader('Model'));
        }
        return $overloader;
    }

}