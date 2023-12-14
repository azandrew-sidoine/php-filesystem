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

namespace Drewlabs\Filesystem\Adapters;

use Drewlabs\Filesystem\Adapters\Traits\FilesystemManager as TraitsFilesystemManager;
use Drewlabs\Filesystem\Contracts\Filesystem;
use Drewlabs\Filesystem\Exceptions\FilesystemCreatorException;
use Drewlabs\Filesystem\Helpers\FilesystemManager as HelpersFilesystemManager;
use League\Flysystem\FilesystemAdapter as AdapterInterface;
use League\Flysystem\FilesystemOperator as FilesytemInterface;

class Manager
{
    use TraitsFilesystemManager;

    /**
     * The array of resolved filesystem drivers.
     *
     * @var array
     */
    private $disks = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    private $creators = [];

    /**
     * Create a new filesystem manager instance.
     *
     * @return self
     */
    public function __construct()
    {
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->disk()->$method(...$parameters);
    }

    /**
     * Get a filesystem instance.
     *
     * @param string|null $name
     *
     * @return Filesystem
     */
    public function drive(?string $name = null)
    {
        return $this->disk($name);
    }

    /**
     * Get a filesystem instance.
     *
     * @return Filesystem
     */
    public function disk(?string $name = null)
    {
        $name = $name ?: HelpersFilesystemManager::getDefaultDriver();

        return $this->disks[$name] = $this->get($name);
    }

    /**
     * Get a default cloud filesystem instance.
     *
     * @return Filesystem
     */
    public function cloud()
    {
        $name = HelpersFilesystemManager::getDefaultCloudDriver();

        return $this->disks[$name] = $this->get($name);
    }

    /**
     * Build an on-demand disk.
     *
     * @param string|array $config
     *
     * @return Filesystem
     */
    public function build($config)
    {
        return $this->resolve(
            'ondemand',
            \is_array($config) ? $config : [
                'driver' => 'local',
                'root' => $config,
            ]
        );
    }

    /**
     * Set the given disk instance.
     *
     * @param string $name
     * @param mixed  $disk
     *
     * @return self
     */
    public function set($name, $disk)
    {
        $this->disks[$name] = $disk;

        return $this;
    }

    /**
     * Unset the given disk instances.
     *
     * @param array|string $disk
     *
     * @return self
     */
    public function forgetDisk($disk)
    {
        $disk = \is_array($disk) ? $disk : (\is_string($disk) ? [$disk] : (array) $disk);
        foreach ($disk as $diskName) {
            unset($this->disks[$diskName]);
        }

        return $this;
    }

    /**
     * Disconnect the given disk and remove from local cache.
     *
     * @param string|null $name
     *
     * @return void
     */
    public function purge($name = null)
    {
        $name = $name ?? HelpersFilesystemManager::getDefaultDriver();

        unset($this->disks[$name]);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param string $driver
     *
     * @return self
     */
    public function extend($driver, \Closure $callback)
    {
        $this->creators[$driver] = $callback;

        return $this;
    }

    /**
     * Attempt to get the disk from the local cache.
     *
     * @param string $name
     *
     * @return AdapterInterface
     */
    protected function get($name)
    {
        return $this->disks[$name] ?? $this->resolve($name);
    }

    /**
     * Call a custom driver creator.
     *
     * @return Filesystem
     */
    protected function callCustomCreator(array $config)
    {
        if (null === $driver = $config['driver'] ?? null) {
            throw new \InvalidArgumentException('driver key is required in the provided configuration entries');
        }
        $driver = $this->creators[$driver]($config);
        if (!($driver instanceof FilesytemInterface)) {
            throw new FilesystemCreatorException('Creator function does not meet definitions requirements. instance of '.FilesytemInterface::class.' must be returned');
        }

        return $driver;
    }

    /**
     * Resolve the given disk.
     *
     * @param string     $name
     * @param array|null $config
     *
     * @throws \InvalidArgumentException
     *
     * @return Filesystem
     */
    private function resolve($name, $config = null)
    {
        $config = $config ?? HelpersFilesystemManager::getConfig($name);
        if (empty($config['driver'])) {
            throw new \InvalidArgumentException("Disk [{$name}] does not have a configured driver.");
        }
        $name = $config['driver'];
        if (isset($this->creators[$name])) {
            return $this->callCustomCreator($config);
        }
        $driverMethod = 'create'.ucfirst($name).'Driver';
        if (!method_exists($this, $driverMethod)) {
            throw new \InvalidArgumentException("Driver [{$name}] is not supported.");
        }
        return $this->{$driverMethod}($config);
    }
}
