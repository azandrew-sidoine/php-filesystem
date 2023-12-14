<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Filesystem\Streams;

/**
 * @internal
 */
final class Reader
{
    public const READ_WRITE_DICT = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true,
        ],
    ];
    /**
     * @var resource
     */
    private $resource;

    /**
     * @var bool
     */
    private $seekable;

    /**
     * @var bool
     *
     * */
    private $readable;

    /**
     * Class constructor.
     *
     * @param resource $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    public function __destruct()
    {
        $this->close();
    }

    #[\ReturnTypeWillChange]
    public function __toString(): string
    {
        try {
            $this->rewind();

            return $this->getContents();
        } catch (\Throwable $e) {
            if (\PHP_VERSION_ID >= 70400) {
                throw $e;
            }
            trigger_error(sprintf('%s::__toString exception: %s', self::class, (string) $e), \E_USER_ERROR);

            return '';
        }
    }

    #[\ReturnTypeWillChange]
    public function rewind(): void
    {
        if ($this->isSeekable()) {
            $this->seek(0);
        }
    }

    /**
     * Factory constructor of the reader class.
     *
     * @param resource $body
     *
     * @return static
     */
    public static function new($body)
    {
        $new = new static($body);
        $meta = stream_get_meta_data($new->resource);
        $new->seekable = $meta['seekable'] && 0 === fseek($new->resource, 0, \SEEK_CUR);
        $new->readable = isset(self::READ_WRITE_DICT['read'][$meta['mode']]);

        return $new;
    }

    /**
     * Returns the content of the internal resource.
     *
     * @throws \RuntimeException
     *
     * @return string|false
     */
    public function getContents()
    {
        if (!$this->readable) {
            throw new \RuntimeException('Resource is not readable');
        }
        if (!isset($this->resource)) {
            throw new \RuntimeException('Stream is detached');
        }
        if (false === $contents = stream_get_contents($this->resource)) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    #[\ReturnTypeWillChange]
    public function close(): void
    {
        if (!isset($this->resource)) {
            return;
        }
        if (\is_resource($this->resource)) {
            fclose($this->resource);
        }
        $this->detach();
    }

    public function detach()
    {
        if (!isset($this->resource)) {
            return;
        }
        $result = $this->resource;
        unset($this->resource);
        $this->readable = $this->seekable = false;

        return $result;
    }

    private function isSeekable()
    {
        return true === $this->seekable;
    }

    #[\ReturnTypeWillChange]
    private function seek($offset, $whence = \SEEK_SET): void
    {
        if (!$this->seekable) {
            throw new \RuntimeException(sprintf('%s is not seekable', __CLASS__));
        }

        if (-1 === fseek($this->resource, $offset, $whence)) {
            throw new \RuntimeException('Unable to seek to stream position '.$offset.' with whence '.var_export($whence, true));
        }
    }
}
