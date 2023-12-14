<?php

use Drewlabs\Filesystem\Adapters\LocalFileSystemAdapter;
use Drewlabs\Filesystem\Contracts\Filesystem;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Ftp\FtpAdapter;
use PHPUnit\Framework\TestCase;
use League\Flysystem\PhpseclibV2\SftpAdapter;

use function Drewlabs\Filesystem\Proxy\FilesystemManager;

class FilesystemManagerTest extends TestCase
{

    public function testCreateDefaultDriver()
    {
        $manager = FilesystemManager();
        $filesystem = $manager->drive();
        $this->assertInstanceOf(Filesystem::class, $filesystem, 'Expect the drive() to create an instance of ' . Filesystem::class);

        // Assert an instance of the LocalFilesystem is created
        $this->assertInstanceOf(LocalFileSystemAdapter::class, $filesystem->adapter(), 'Expect the adapter() method of the filesystem to return an instance of ' . LocalFileSystemAdapter::class);
    }

    public function testCreateFtpDriver()
    {
        $manager = FilesystemManager();
        $filesystem = $manager->drive('ftp');
        $this->assertInstanceOf(FtpAdapter::class, $filesystem->adapter(), 'Expect an instance of ' . FtpAdapter::class . ' to be created');

    }

    public function testCreateSftpDriver()
    {
        $manager = FilesystemManager();
        $filesystem = $manager->drive('sftp');
        $this->assertInstanceOf(SftpAdapter::class, $filesystem->adapter(), 'Expect an instance of ' . SftpAdapter::class . ' to be created');
    }

    public function testCreateS3Driver()
    {
        $manager = FilesystemManager();
        $filesystem = $manager->drive('s3');
        $this->assertInstanceOf(AwsS3V3Adapter::class, $filesystem->adapter(), 'Expect an instance of ' . AwsS3V3Adapter::class . ' to be created');
        $filesystem2 = $manager->cloud();
        $this->assertInstanceOf(AwsS3V3Adapter::class, $filesystem2->adapter(), 'Expect an instance of ' . AwsS3V3Adapter::class . ' to be created');
    }
}