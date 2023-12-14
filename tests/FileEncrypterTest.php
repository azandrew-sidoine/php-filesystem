<?php

use Drewlabs\Filesystem\FileVault\Encrypter;
use Drewlabs\Filesystem\FileVault\Key;
use PHPUnit\Framework\TestCase;

use function Drewlabs\Filesystem\Proxy\File;

class FileEncrypterTest extends TestCase
{
    public function test_encrypt_function()
    {
        $encrypter = new Encrypter(new Key("base64:" . base64_encode('SuperRealSecretA')));
        $result = $encrypter->encrypt(__DIR__ . '/storage/text.txt', __DIR__ . '/storage/text.txt.enc'); //
        $encrypter->encrypt(__DIR__ . '/storage/image.jpg', __DIR__ . '/storage/image.jpg.enc');
        $this->assertTrue($result);
        $this->assertTrue(File(__DIR__ . '/storage/text.txt.enc')->exists());
    }

    public function test_decrypt_function()
    {
        $encrypter = new Encrypter(new Key("base64:" . base64_encode('SuperRealSecretA')));
        $result = $encrypter->decrypt(__DIR__ . '/storage/text.txt.enc', __DIR__ . '/storage/decrypted/text.txt');
        $encrypter->encrypt(__DIR__ . '/storage/image.jpg.enc', __DIR__ . '/storage/decrypted/image.jpg');
        $this->assertTrue($result);
        $this->assertTrue(File(__DIR__ . '/storage/text.txt')->getContents() === File(__DIR__ . '/storage/text.txt')->getContents());
    }
}
