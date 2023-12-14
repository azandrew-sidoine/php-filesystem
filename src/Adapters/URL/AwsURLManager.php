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

use Aws\S3\S3Client;
use Drewlabs\Filesystem\Contracts\URLManager;
use Drewlabs\Filesystem\Helpers\FilesystemManager;
use Drewlabs\Filesystem\Helpers\URLHelper;
use League\Flysystem\PathPrefixer;

class AwsURLManager implements URLManager
{
    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param string             $path
     * @param \DateTimeInterface $expiration
     * @param array              $options
     *
     * @return string
     */
    public function temporaryURL($path, $expiration, $options)
    {
        $config = (array) (FilesystemManager::formatS3Config(
            FilesystemManager::getConfig('s3')
        ));
        $client = (new S3Client($config));

        $command = $client->getCommand('GetObject', array_merge([
            'Bucket' => $config['bucket'],
            'Key' => (new PathPrefixer($config['root'] ?? ''))->prefixPath($path),
        ], $options));

        $uri = $client->createPresignedRequest(
            $command,
            $expiration
        )->getUri();

        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (null !== $url = $config['temporary_url'] ?? null) {
            $uri = $this->replaceBaseUrl($uri, $url);
        }

        return (string) $uri;
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param string $path
     *
     * @return string
     */
    public function url($path)
    {
        $config = (array) (FilesystemManager::formatS3Config(
            FilesystemManager::getConfig('s3')
        ));
        $root = $config['root'] ?? '';
        $path = (new PathPrefixer($root))->prefixPath($path);
        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (null !== $url = $config['url'] ?? null) {
            return URLHelper::addPathToBaseURL($url, $path);
        }

        return (new S3Client($config))->getObjectUrl(
            $config['bucket'] ?? null,
            $path
        );
    }

    /**
     * Replace the scheme, host and port of the given UriInterface with values from the given URL.
     *
     * @param \Psr\Http\Message\UriInterface $uri
     * @param string                         $url
     *
     * @return \Psr\Http\Message\UriInterface
     */
    private function replaceBaseUrl($uri, $url)
    {
        $parsed = parse_url($url);

        return $uri
            ->withScheme($parsed['scheme'])
            ->withHost($parsed['host'])
            ->withPort($parsed['port'] ?? null);
    }
}
