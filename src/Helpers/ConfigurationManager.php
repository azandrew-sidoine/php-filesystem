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

use ArrayAccess;

class ConfigurationManager implements ArrayAccess
{
    private const DEFAULTS = [
        'default' => 'local',
        'cloud' => 's3',
        'disks' => [
            'local' => [
                'driver' => 'local',
                'root' => '',
                'url' => null,
            ],
            'public' => [
                'driver' => 'local',
                'root' => '',
                'url' => null,
                'visibility' => 'public',
            ],
            's3' => [
                'driver' => 's3',
                'key' => '',
                'secret' => '',
                'region' => 'us-west',
                'bucket' => '',
                'url' => null,
                'endpoint' => null,
            ],
            'ftp' => [
                'driver' => 'ftp',
                'root' => '/root/tmp',
                'user' => 'admin',
                'password' => 'password'
            ],
            'sftp' => [
                'driver' => 'sftp',
                'root' => '/root/tmp',
                'user' => 'admin',
                'password' => 'password'
            ]

        ],
    ];
    /**
     * Static class instance.
     *
     * @var self
     */
    private static $instance;

    /**
     * Configurations cache property.
     *
     * @var array
     */
    private $config;

    /**
     * Private constructor to prevent users from calling new on the current class.
     */
    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (null === static::$instance) {
            $self = new static();
            $self = $self->setConfig($self->config ?? self::DEFAULTS);
            static::$instance = $self;
        }

        return static::$instance;
    }

    public static function configure(array $config = [])
    {
        // Configure the authentication package
        $self = (new static())->setConfig(
            array_merge(self::DEFAULTS, $config ?? [])
        );
        static::$instance = $self;

        return $self;
    }

    public function get($key = null, $default = null)
    {
        $valueCallback = static function (array $array, string $key, $default = null) {
            $keyExistsCallback = static function ($list, $k) {
                if ($list instanceof \ArrayAccess) {
                    return $list->offsetExists($k);
                }

                return \array_key_exists($k, $list);
            };
            $isArrayCallback = static function ($list) {
                return \is_array($list) || $list instanceof \ArrayAccess;
            };
            if (null === $key) {
                return $array;
            }

            if ($key instanceof \Closure) {
                return $key($array) ?? ($default instanceof \Closure ? $default() : $default);
            }

            if ($keyExistsCallback($array, $key)) {
                return $array[$key];
            }

            if (false === strpos($key, '.')) {
                return $array[$key] ?? ($default instanceof \Closure ? $default() : $default);
            }

            foreach (explode('.', $key) as $segment) {
                if ($isArrayCallback($array) && $keyExistsCallback($array, $segment)) {
                    $array = $array[$segment];
                } else {
                    return $default instanceof \Closure ? $default() : $default;
                }
            }

            return $array;
        };
        if (null === $key) {
            return array_merge($this->config, []);
        }
        $value = $valueCallback($this->config, $key, $default);

        return null === $value ? ($default instanceof \Closure ? $default() : $default) : $value;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return null !== $this->get($offset, null);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset, null);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('Configuration manager class is a readonly class, operations changing the class state are not allowed');
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Configuration manager class is a readonly class, operations changing the class state are not allowed');
    }

    private function setConfig(array $config)
    {
        $this->config = $config ?? [];

        return $this;
    }
}
