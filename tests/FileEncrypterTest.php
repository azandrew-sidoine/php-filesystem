<?php

use Drewlabs\Filesystem\FileVault\Encrypter;
use Drewlabs\Filesystem\FileVault\Key;
use PHPUnit\Framework\TestCase;

use function Drewlabs\Filesystem\Proxy\Directory;
use function Drewlabs\Filesystem\Proxy\File;

class FileEncrypterTest extends TestCase
{
    /**
     * this method is called before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Directory(__DIR__ . '/storage2/decrypted')->createIfNotExists(0755, true);
        if(!File(__DIR__ . '/storage2/text.txt')->exists()) {
            File(__DIR__ . '/storage2/text.txt')->write('new contents');
        }
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        if (Directory(__DIR__ . '/storage2')->exists()) {
            Directory(__DIR__ . '/storage2')->delete();
        }
    }


    public function test_encrypt_function()
    {
        $encrypter = new Encrypter(new Key("base64:" . base64_encode('SuperRealSecretA')));
        $result = $encrypter->encrypt(__DIR__ . '/storage2/text.txt', __DIR__ . '/storage2/text.txt.enc'); //
        $this->assertTrue($result);
        $this->assertTrue(File(__DIR__ . '/storage2/text.txt.enc')->exists());
    }

    /**
     * @depends test_encrypt_function 
     */
    public function test_decrypt_function()
    {
        $encrypter = new Encrypter(new Key("base64:" . base64_encode('SuperRealSecretA')));
        $result = $encrypter->decrypt(__DIR__ . '/storage2/text.txt.enc', __DIR__ . '/storage2/decrypted/text.txt');
        $this->assertTrue($result);
        $this->assertTrue(File(__DIR__ . '/storage2/text.txt')->getContents() === File(__DIR__ . '/storage2/text.txt')->getContents());
    }
}
