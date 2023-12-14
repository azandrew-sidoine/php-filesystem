<?php

namespace Drewlabs\Filesystem\FileVault;

use Drewlabs\Filesystem\Helpers\Str;
use function Drewlabs\Filesystem\Proxy\FilesystemManager;

final class Vault
{
    /**
     * The storage disk.
     *
     * @var string
     */
    private $disk;

    /**
     * The encryption key.
     *
     * @var Key
     */
    private $key;

    /**
     * The storage adapter.
     *
     * @var \League\Flysystem\FilesystemAdapter
     */
    private $adapter;

    /**
     * 
     * @param Key $key 
     * @return self 
     */
    public function __construct(Key $key)
    {
        $this->key = $key;
    }

    /**
     * Set the disk where the files are located.
     *
     * @param  string  $disk
     * @return self
     */
    public function disk($disk)
    {
        $this->disk = $disk;
        return $this;
    }

    /**
     * Set the encryption key.
     *
     * @param  Key  $key
     * @return $this
     */
    public function key(key $key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Encrypt the passed file and saves the result in a new file with ".enc" as suffix.
     *
     * @param string $source Path to file that should be encrypted, relative to the storage disk specified
     * @param string $dst   File name where the encryped file should be written to, relative to the storage disk specified
     * @return $this
     */
    public function encrypt(string $source, ?string $dst = null, bool $deleteAfterDecrypt = true)
    {
        $this->adapt();
        $dst = $dst ?? "{$source}.enc";
        $srcpath = $this->getLocation($source);
        $dstpath = $this->getLocation($dst);
        // Create a new encrypter instance
        $encrypter = new Encrypter($this->key);
        // If encryption is successful, delete the source file
        if ($encrypter->encrypt($srcpath, $dstpath) && $deleteAfterDecrypt) {
            FilesystemManager()->disk($this->disk)->delete($source);
        }
        return $this;
    }

    /**
     * Dencrypt the passed file and saves the result in a new file, removing the
     * last 4 characters from file name.
     *
     * @param string $source Path to file that should be decrypted
     * @param string $dst   File name where the decryped file should be written to.
     * @return $this
     */
    public function decrypt($source, $dst = null, $deleteAfterDecrypt = true)
    {
        $this->adapt();
        if (null === $dst) {
            $dst = Str::endsWith($source, '.enc')
                ? Str::replaceLast('.enc', '', $source)
                : $source . '.dec';
        }
        $srcpath = $this->getLocation($source);
        $dstpath = $this->getLocation($dst);
        // Create a new encrypter instance
        $encrypter = new Encrypter($this->key);
        // If decryption is successful, delete the source file
        if ($encrypter->decrypt($srcpath, $dstpath) && $deleteAfterDecrypt) {
            FilesystemManager()->disk($this->disk)->delete($source);
        }

        return $this;
    }

    public function streamDecrypt($path)
    {
        $this->adapt();
        // Create a new encrypter instance
        $encrypter = new Encrypter($this->key);
        return $encrypter->decrypt($this->getLocation($path), 'php://output');
    }

    private function getLocation(string $path)
    {
        return FilesystemManager()->disk($this->disk)->url($path);
    }

    private function adapt()
    {
        if ($this->adapter) {
            return;
        }
        $this->adapter = FilesystemManager()->disk($this->disk)->adapter();
    }
}
