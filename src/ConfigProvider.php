<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Loader;

/**
 *
 * @package    Zalt
 * @subpackage Loader
 * @since      Class available since version 1.0
 */
class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'overLoader' => [
                'AddTo' => false,
                'Paths' => ProjectOverloaderFactory::$defaultOverLoaderPaths,
                // 'LegacyPrefix' => 'Legacy',
                ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            // Legacy MUtil Framework aliases
            'aliases'    => [
                ProjectOverloader::class => ProjectOverloader::class,
            ],
            'invokables' => [
                ProjectOverloader::class => ProjectOverloaderFactory::class,
            ],
        ];
    }

}