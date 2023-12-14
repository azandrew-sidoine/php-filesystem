<?php

namespace Drewlabs\Filesystem\FileVault;

use Exception;

/**
 * Provides a Stringeable object for generating cipher used by
 * {@see Encrypter} class for encrypting and decrypting file contents
 * 
 * @package Drewlabs\Filesystem\FileVault
 */
class Key
{
    /**
     * 
     * @var string
     */
    private $cipher;

    /**
     * 
     * @var string
     */
    private $value;

    public function __construct(string $value = null, string $cipher = 'AES-128-CBC')
    {
        $this->value = $value;
        $this->cipher = $cipher ?? 'AES-128-CBC';
    }

    /**
     * Generate a new encryption key that suits the cipher method
     * 
     * @param string $cipher 
     * @return Key 
     * @throws Exception 
     */
    public static function make(string $cipher = 'AES-128-CBC')
    {
        return new self(random_bytes($cipher === 'AES-128-CBC' ? 16 : 32), $cipher);
    }

    /**
     * Returns the cipher used in generating the key
     * 
     * @return string 
     */
    public function cipher()
    {
        return $this->cipher;
    }

    /**
     * Create a new encryption string value for the given cipher.
     * 
     * @return string
     * 
     * @throws Exception 
     */
    public function __toString()
    {
        return $this->value;
    }
}