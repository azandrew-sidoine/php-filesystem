<?php

use Drewlabs\Filesystem\FileVault\Key;
use Drewlabs\Filesystem\FileVault\Vault;
use Drewlabs\Filesystem\Helpers\ConfigurationManager;
use PHPUnit\Framework\TestCase;

use function Drewlabs\Filesystem\Proxy\Directory;
use function Drewlabs\Filesystem\Proxy\File;
use function Drewlabs\Filesystem\Proxy\FilesystemManager;
use function Drewlabs\Filesystem\Proxy\Path;

class FileVaultTest extends TestCase
{
    private static $key;

    protected function setUp(): void
    {
        if (null === self::$key) {
            self::$key = Key::make();
        }
        // Create the storage directory if it does not exits before running tests
        Directory(__DIR__ . '/storage/app/')->createIfNotExists();
        ConfigurationManager::getInstance()->configure([
            'default' => 'local',
            'disks' => [
                'local' => [
                    'driver' => 'local',
                    'root' => (string)(Path(__DIR__ . '/storage/app/')->canonicalize())
                ],
                'public' => [
                    'driver' => 'local',
                    'root' => '',
                    'url' => null,
                    'visibility' => 'public',
                ],
                's3' => [
                    'driver' => 's3',
                    'key' => '',
                    'secret' => '',
                    'region' => 'us-west',
                    'bucket' => '',
                    'url' => null,
                    'endpoint' => null,
                ],
                'ftp' => [
                    'driver' => 'ftp',
                    'root' => '/root/tmp',
                    'user' => 'admin',
                    'password' => 'password'
                ],
                'sftp' => [
                    'driver' => 'sftp',
                    'root' => '/root/tmp',
                    'user' => 'admin',
                    'password' => 'password'
                ]
            ]
        ]);
        $contents = <<<EOD
        Lorem Ipsum is simply dummy text of the printing and typesetting industry. 
Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, 
when an unknown printer took a galley of type and scrambled it to make a type specimen book. 
It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. 
It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, 
and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.
EOD;
        // Create file before each test
        File(__DIR__ . '/storage/app/text.txt')->write($contents);
    }

    public function test_file_vault_encrypt()
    {
        $vault = new Vault(self::$key);
        $vault->disk('local')->encrypt('text.txt', null, false);
        $this->assertTrue(FilesystemManager()->disk('local')->fileExists('text.txt'));
        $vault->disk('local')->encrypt('text.txt');
        $this->assertFalse(FilesystemManager()->disk('local')->fileExists('text.txt'));
        $this->assertTrue(FilesystemManager()->disk('local')->fileExists('text.txt.enc'));
    }

    public function test_file_vault_decrypt()
    {
        $vault = new Vault(self::$key);
        $vault->disk('local')->decrypt('text.txt.enc');
        $this->assertFalse(FilesystemManager()->disk('local')->fileExists('text.txt.enc'));
        $this->assertTrue(FilesystemManager()->disk('local')->fileExists('text.txt'));
    }
}
