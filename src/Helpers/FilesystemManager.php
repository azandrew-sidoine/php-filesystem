<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Filesystem\Helpers;

class FilesystemManager
{
    /**
     * Format the given S3 configuration with the default options.
     *
     * @return array
     */
    public static function formatS3Config(array $config)
    {
        $config += ['version' => 'latest'];
        if (!empty($config['key']) && !empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        return $config;
    }

    /**
     * Get the filesystem connection configuration.
     *
     * @param string $name
     *
     * @return array
     */
    public static function getConfig($name)
    {
        return ConfigurationManager::getInstance()->get("disks.{$name}") ?? [];
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public static function getDefaultDriver()
    {
        return ConfigurationManager::getInstance()->get('default', 'local');
    }

    /**
     * Get the default cloud driver name.
     *
     * @return string
     */
    public static function getDefaultCloudDriver()
    {
        return ConfigurationManager::getInstance()->get('cloud', 's3');
    }
}
