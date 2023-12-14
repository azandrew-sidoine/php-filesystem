<?php

namespace Drewlabs\Filesystem\FileVault;

use Drewlabs\Filesystem\Exceptions\IOException;
use Drewlabs\Filesystem\Helpers\Str;
use Exception;

use function Drewlabs\Filesystem\Proxy\File;

final class Encrypter
{
    /**
     * Define the number of blocks that should be read from the source file for each chunk.
     * We chose 255 because on decryption we want to read chunks of 4kb ((255 + 1)*16).
     */
    private const FILE_ENCRYPTION_BLOCKS = 255;

    /**
     * The encryption key.
     *
     * @var string
     */
    private $key;

    /**
     * The algorithm used for encryption.
     *
     * @var string
     */
    private $cipher;

    /**
     * Source file handle
     * 
     * @var resource|false
     */
    private $srcHandle;

    /**
     * Destination file handle
     * 
     * @var resource|false
     */
    private $dstHandle;

    /**
     * Create a new encrypter instance.
     *
     * @param  Key  $key
     * @return self
     *
     * @throws \RuntimeException
     */
    public function __construct(Key $key)
    {
        // If the key starts with "base64:", we will need to decode the key before handing
        // it off to the encrypter. Keys may be base-64 encoded for presentation and we
        // want to make sure to convert them back to the raw bytes before encrypting.
        if (Str::startsWith($key_ = (string)$key, 'base64:')) {
            $key_ = base64_decode(substr($key_, strlen('base64:')));
        }
        if ($this->supported($key_, $cipher = $key->cipher())) {
            $this->key = $key_;
            $this->cipher = $cipher;
        } else {
            throw new Exception('The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths.');
        }
    }

    /**
     * Determine if the given key and cipher combination is valid.
     *
     * @param  string  $key
     * @param  string  $cipher
     * @return bool
     */
    private function supported($key, $cipher)
    {
        $length = mb_strlen($key, '8bit');
        return ($cipher === 'AES-128-CBC' && $length === 16) ||
            ($cipher === 'AES-256-CBC' && $length === 32);
    }

    /**
     * Encrypts the source file and saves the result in a new file.
     *
     * @param string $path  Path to file that should be encrypted
     * @param string $dstPath  File name where the encryped file should be written to.
     * @return bool
     */
    public function encrypt($path, $dstPath)
    {
        // We keep reference to file descriptors in private properties
        // so that in case of unhandled exceptions, we successfully cleanup
        // resources using class destructors
        $this->dstHandle = $this->openDestFile($dstPath);
        $this->srcHandle = $this->openSourceFile($path);

        if (!is_resource($this->srcHandle) || !is_resource($this->dstHandle)) {
            throw new Exception('Encryption Error: Fail to open source or destination files');
        }

        // Put the initialzation vector to the beginning of the destination file
        $iv = openssl_random_pseudo_bytes(16);
        fwrite($this->dstHandle, $iv);
        $chunkSize = ceil(File($path)->getSize() / (16 * self::FILE_ENCRYPTION_BLOCKS));

        $index = 0;
        while (!feof($this->srcHandle)) {
            $plain = @fread($this->srcHandle, 16 * self::FILE_ENCRYPTION_BLOCKS);
            if (false === $plain) {
                throw new Exception("Encryption Error: Failed to read from source file at $path");
            }
            $cipher = @openssl_encrypt($plain, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
            // Because Amazon S3 will randomly return smaller sized chunks:
            // Check if the size read from the stream is different than the requested chunk size
            // In this scenario, request the chunk again, unless this is the last chunk
            if (
                strlen($plain) !== 16 * self::FILE_ENCRYPTION_BLOCKS
                && $index + 1 < $chunkSize
            ) {
                fseek($this->srcHandle, 16 * self::FILE_ENCRYPTION_BLOCKS * $index);
                continue;
            }
            // Use the first 16 bytes of the ciphertext as the next initialization vector
            $iv = substr($cipher, 0, 16);
            fwrite($this->dstHandle, $cipher);
            $index++;
        }
        $this->closeHandles();
        return true;
    }

    /**
     * Decrypts the source file and saves the result in a new file.
     *
     * @param string $path   Path to file that should be decrypted
     * @param string $dstPath  File name where the decryped file should be written to.
     * @return bool
     */
    public function decrypt($path, $dstPath)
    {
        // We keep reference to file descriptors in private properties
        // so that in case of unhandled exceptions, we successfully cleanup
        // resources using class destructors
        $this->dstHandle = $this->openDestFile($dstPath);
        $this->srcHandle = $this->openSourceFile($path);

        if (!is_resource($this->srcHandle) || !is_resource($this->dstHandle)) {
            throw new Exception('Decryption Error: Fail to open source or destination files');
        }

        // Get the initialzation vector from the beginning of the file
        $iv = fread($this->srcHandle, 16);
        $chinkSize = ceil((filesize($path) - 16) / (16 * (self::FILE_ENCRYPTION_BLOCKS + 1)));

        $index = 0;
        while (!feof($this->srcHandle)) {
            // We have to read one block more for decrypting than for encrypting because of the initialization vector
            $cipher = fread($this->srcHandle, 16 * (self::FILE_ENCRYPTION_BLOCKS + 1));
            if (false === $cipher) {
                throw new Exception("Decryption Error: Failed to read from source file at $path");
            }
            $plain = openssl_decrypt($cipher, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
            // Because Amazon S3 will randomly return smaller sized chunks:
            // Check if the size read from the stream is different than the requested chunk size
            // In this scenario, request the chunk again, unless this is the last chunk
            if (
                strlen($cipher) !== 16 * (self::FILE_ENCRYPTION_BLOCKS + 1)
                && $index + 1 < $chinkSize
            ) {
                fseek($this->srcHandle, 16 + 16 * (self::FILE_ENCRYPTION_BLOCKS + 1) * $index);
                continue;
            }

            if ($plain === false) {
                throw new Exception('Decryption failed');
            }
            // Get the the first 16 bytes of the ciphertext as the next initialization vector
            $iv = substr($cipher, 0, 16);
            fwrite($this->dstHandle, $plain);
            $index++;
        }
        $this->closeHandles();
        return true;
    }

    private function openDestFile($path)
    {
        if (($fd = fopen($path, 'w')) === false) {
            throw new Exception('Cannot open file for writing');
        }
        return $fd;
    }

    private function openSourceFile($path)
    {
        $contextOpts = Str::startsWith($path, 's3://') ? ['s3' => ['seekable' => true]] : [];
        if (($fd = @fopen($path, 'r', false, stream_context_create($contextOpts))) === false) {
            throw IOException::open($path);
        }
        return $fd;
    }

    /**
     * 
     * @return void 
     */
    private function closeHandles()
    {
        foreach ([$this->dstHandle, $this->srcHandle] as $value) {
            if (is_resource($value)) {
                fclose($value);
            }
        }
    }

    public function __destruct()
    {
        $this->closeHandles();
    }
}
