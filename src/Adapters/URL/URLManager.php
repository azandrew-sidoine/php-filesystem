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

namespace Drewlabs\Filesystem\Adapters\URL;

use Drewlabs\Filesystem\Adapters\LocalFileSystemAdapter as LocalAdapter;
use Drewlabs\Filesystem\Contracts\Filesystem;
use Drewlabs\Filesystem\Contracts\URLManager as ContractsURLManager;
use Drewlabs\Filesystem\Helpers\FilesystemManager;
use Drewlabs\Filesystem\Helpers\URLHelper;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;

class URLManager implements ContractsURLManager
{
    /**
     * @var Filesystem
     */
    private $driver;

    public function __construct(Filesystem $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @return Filesystem
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param string $path
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    public function url($path)
    {
        $adapter = $this->getDriver()->adapter();
        if (method_exists($adapter, 'getUrl')) {
            return (new \ReflectionMethod($adapter, 'getUrl'))->invoke($path);
        } elseif ($adapter instanceof S3Adapter) {
            return (new AwsURLManager())->url($path);
        } elseif ($adapter instanceof FtpAdapter) {
            return $this->getFtpUrl('ftp', $path);
        } elseif ($adapter instanceof SftpAdapter) {
            return $this->getFtpUrl('sftp', $path);
        } elseif ($adapter instanceof LocalAdapter) {
            return $adapter->url($path);
        }
        throw new \RuntimeException('This driver does not support retrieving URLs.');
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param string $path
     *
     * @return string
     */
    private function getFtpUrl(string $name, $path)
    {
        $config = FilesystemManager::getConfig($name);

        return ($url = $config['url'] ?? null)
            ? URLHelper::addPathToBaseURL($url, $path)
            : $path;
    }
}
