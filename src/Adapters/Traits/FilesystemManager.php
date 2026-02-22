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

namespace Drewlabs\Filesystem\Adapters\Traits;

use Aws\S3\S3Client;
use Drewlabs\Filesystem\Adapters\LocalFileSystemAdapter as LocalAdapter;
use Drewlabs\Filesystem\Adapters\URL\URLManager;
use Drewlabs\Filesystem\Contracts\Filesystem;
use Drewlabs\Filesystem\Helpers\Arr;
use Drewlabs\Filesystem\Helpers\FilesystemManager as HelpersFilesystemManager;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter as S3VisibilityConverter;
use League\Flysystem\Filesystem as LeagueFilesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemAdapter as AdapterInterface;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter as CachedAdapter;
use League\Flysystem\PathNormalizer;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;

trait FilesystemManager
{
    /**
     * Create an instance of the local driver.
     *
     * @return Filesystem
     */
    public function createLocalDriver(array $config)
    {
        // TODO : Add permission handlers to Local Adapter
        $links = ($config['links'] ?? null) === 'skip'
            ? LocalAdapter::SKIP_LINKS
            : LocalAdapter::DISALLOW_LINKS;

        return $this->createFlysystem(
            new LocalAdapter(
                $config['root'] ?? '',
                $config['lock'] ?? \LOCK_EX,
                $links
            ),
            $config
        );
    }

    /**
     * Create an instance of the ftp driver.
     *
     * @return Filesystem
     */
    public function createFtpDriver(array $config)
    {
        return $this->createFlysystem(
            new FtpAdapter(
                // Connection options
                FtpConnectionOptions::fromArray([
                    'host' => $config['host'] ?? '127.0.0.1', // required
                    'root' => $config['root'] ?? '/root/tmp/', // required
                    'username' => $config['user'] ?? '', // required
                    'password' => $config['password'] ?? '', // required
                    'port' => $config['port'] ?? 21,
                    'ssl' => $config['ssl'] ?? false,
                    'timeout' => 90,
                    'utf8' => false,
                    'passive' => true,
                    'transferMode' => \FTP_BINARY,
                    'systemType' => null,
                    'ignorePassiveAddress' => null,
                    'timestampsOnUnixListingsEnabled' => false,
                    'recurseManually' => true,
                ])
            ),
            $config
        );
    }

    /**
     * Create an instance of the sftp driver.
     *
     * @return Filesystem
     */
    public function createSftpDriver(array $config)
    {
        return $this->createFlysystem(
            new SftpAdapter(
                new SftpConnectionProvider(
                    $config['host'] ?? '127.0.0.1',
                    $config['user'] ?? '',
                    $config['password'] ?? '',
                    $config['private_key'] ?? null,
                    $config['passphrase'] ?? null,
                    $config['port'] ?? 22, // port (optional, default: 22)
                    $config['agent'] ?? false, // use agent (optional, default: false)
                    $config['timeout'] ?? 30, // timeout (optional, default: 10)
                    $config['max_attempts'] ?? 10, // max tries (optional, default: 4)
                    null, // host fingerprint (optional, default: null),
                    null, // connectivity checker (must be an implementation of 'League\Flysystem\PhpseclibV2\ConnectivityChecker' to check if a connection can be established (optional, omit if you don't need some special handling for setting reliable connections)
                ),
                $config['root'] ?? '/root/tmp/', // required
                PortableVisibilityConverter::fromArray(
                    $config['visibility'] ?? [
                        'file' => [
                            'public' => 0640,
                            'private' => 0604,
                        ],
                        'dir' => [
                            'public' => 0740,
                            'private' => 7604,
                        ],
                    ]
                )
            ),
            $config
        );
    }

    /**
     * Create an instance of the Amazon S3 driver.
     *
     * @return Filesystem
     */
    public function createS3Driver(array $config)
    {
        $s3Config = HelpersFilesystemManager::formatS3Config($config);
        $root = $s3Config['root'] ?? '';
        $options = $config['options'] ?? [];
        $streamReads = $config['stream_reads'] ?? false;
        $visibility = $config['visibility'] ?? 'public';

        return $this->createFlysystem(
            new S3Adapter(
                new S3Client($s3Config),
                $s3Config['bucket'],
                $root,
                new S3VisibilityConverter(
                    $visibility === 'public' ?  Visibility::PUBLIC : Visibility::PRIVATE
                ),
                null,
                $options,
                $streamReads
            ),
            $config
        );
    }

    /**
     * Create a Flysystem instance with the given adapter.
     *
     * @return Filesystem
     */
    private function createFlysystem(AdapterInterface $adapter, array $config)
    {
        $cache = $config['cache'] ?? null;
        unset($config['cache']);

        $config = Arr::only($config, ['visibility', 'disable_asserts', 'url', 'temporary_url']);

        if ($cache) {
            // Check to create a cached adapter
            $adapter = new CachedAdapter();
        }

        return new class($adapter, $config ?? []) extends LeagueFilesystem implements Filesystem {
            /**
             * @var FilesystemAdapter
             */
            private $adapter_;

            public function __construct(
                FilesystemAdapter $adapter,
                array $config = [],
                ?PathNormalizer $pathNormalizer = null
            ) {
                $this->adapter_ = $adapter;
                parent::__construct($adapter, $config, $pathNormalizer);
            }

            public function adapter()
            {
                return $this->adapter_;
            }

            public function url(string $path)
            {
                return (new URLManager($this))->url($path);
            }
        };
    }
}
