<?php

use Drewlabs\Filesystem\Directory as DirectoryClass;
use Drewlabs\Filesystem\File as FileClass;
use Drewlabs\Filesystem\Ownership;
use Drewlabs\Filesystem\Path;
use PHPUnit\Framework\TestCase;

use function Drewlabs\Filesystem\Proxy\Chmod;
use function Drewlabs\Filesystem\Proxy\Directory;
use function \Drewlabs\Filesystem\Proxy\File as LocalFile;
use function Drewlabs\Filesystem\Proxy\Path;

class CreatorFnTest extends TestCase
{

    public function testCreateFileInstance()
    {
        $file = LocalFile(__DIR__ . '/storage/format.txt');

        $this->assertInstanceOf(FileClass::class, $file, 'Expect the File method to return an instance of File class');
    }

    public function testCreateDirectoryInstance()
    {
        $directory = Directory(__DIR__ . '/storage');

        $this->assertTrue($directory->isDirectory(), 'Expect the directory to be a file directory');

        $this->assertInstanceOf(DirectoryClass::class, $directory, 'Expect the returned value of the Directory function to be an instance of Directory class');
    }

    public function testCreatePathInstance()
    {
        $path = Path(__DIR__ . '/storage');
        $this->assertTrue($path->exists());
        $this->assertInstanceOf(Path::class, $path, 'Expect the returned value of the Directory function to be an instance of Path class');
    }

    public function testCreateOwnershipInstance()
    {
        $chmod = Chmod();
        $this->assertTrue($chmod->chmod(Path(__DIR__ . '/storage'), 0755));
        $this->assertIsInt($chmod->chmod(__DIR__ . '/storage'), 'Expect the chmod method to return an integer');
        $this->assertInstanceOf(Ownership::class, $chmod, 'Expect the returned value of the Directory function to be an instance of Ownership class');
    }

}