<?php

use Drewlabs\Filesystem\Adapters\LocalFilesystemAdapter;
use Drewlabs\Filesystem\Exceptions\Compact\BaseException;
use Drewlabs\Filesystem\Exceptions\FileNotFoundException;
use Drewlabs\Filesystem\Exceptions\MoveException;
use Drewlabs\Filesystem\Exceptions\PathNotFoundException;
use Drewlabs\Filesystem\Exceptions\ReadFileException;
use Drewlabs\Filesystem\Exceptions\UnableToRetrieveMetadataException;
use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Visibility;

use function Drewlabs\Filesystem\Proxy\Directory;

class LocalFileSystemAdapterTest extends FilesystemAdapterTestCase
{
    /**
     * this method is called before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Directory(__DIR__ . '/storage')->createIfNotExists(0755, true);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        if (Directory(__DIR__ . '/storage')->exists()) {
            Directory(__DIR__ . '/storage')->delete();
        }
    }

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        return new LocalFilesystemAdapter(__DIR__ . '/storage');
    }

    /**
     * @test
     */
    public function fetching_file_size_of_a_directory(): void
    {
        $this->expectException(BaseException::class);

        $adapter = $this->adapter();

        $this->runScenario(function () use ($adapter) {
            $adapter->createDirectory('path', new Config());
            $adapter->fileSize('path/');
        });
    }

    /**
     * @test
     */
    public function fetching_visibility_of_non_existing_file(): void
    {
        $this->expectException(UnableToRetrieveMetadataException::class);

        $this->runScenario(function () {
            $this->adapter()->visibility('non-existing-file.txt');
        });
    }

    /**
     * @test
     */
    public function setting_visibility_on_a_file_that_does_not_exist(): void
    {
        $this->expectException(PathNotFoundException::class);

        $this->runScenario(function () {
            $this->adapter()->setVisibility('path.txt', Visibility::PRIVATE);
        });
    }

    /**
     * @test
     */
    public function reading_a_file_that_does_not_exist(): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->runScenario(function () {
            $this->adapter()->read('path.txt');
        });
    }

    /**
     * @test
     */
    public function moving_a_file_that_does_not_exist(): void
    {
        $this->expectException(MoveException::class);

        $this->runScenario(function () {
            $this->adapter()->move('source.txt', 'destination.txt', new Config());
        });
    }

    /**
     * @test
     */
    public function failing_to_read_a_non_existing_file_into_a_stream(): void
    {
        $this->expectException(ReadFileException::class);

        $this->adapter()->readStream('something.txt');
    }

    /**
     * @test
     */
    public function failing_to_read_a_non_existing_file(): void
    {
        $this->expectException(ReadFileException::class);

        $this->adapter()->readStream('something.txt');
    }

    /**
     * @test
     */
    public function fetching_unknown_mime_type_of_a_file(): void
    {
        $this->givenWeHaveAnExistingFile(
            'unknown-mime-type.md5',
            @file_get_contents(__DIR__ . '/test_files/unknown-mime-type.md5')
        );

        $this->expectException(UnableToRetrieveMetadataException::class);

        $this->runScenario(function () {
            $this->adapter()->mimeType('unknown-mime-type.md5');
        });
    }

    /**
     * @test
     */
    public function fetching_file_size_of_non_existing_file(): void
    {
        $this->expectException(UnableToRetrieveMetadataException::class);

        $this->runScenario(function () {
            $this->adapter()->fileSize('non-existing-file.txt');
        });
    }

    /**
     * @test
     */
    public function trying_to_delete_a_non_existing_file(): void
    {
        $this->expectException(FileNotFoundException::class);
        $adapter = $this->adapter();

        $adapter->delete('path.txt');
        $fileExists = $adapter->fileExists('path.txt');

        $this->assertFalse($fileExists);
    }

    /**
     * @test
     */
    public function fetching_last_modified_of_non_existing_file(): void
    {
        $this->expectException(UnableToRetrieveMetadataException::class);

        $this->runScenario(function () {
            $this->adapter()->lastModified('non-existing-file.txt');
        });
    }

    /**
     * @test
     */
    public function fetching_mime_type_of_non_existing_file(): void
    {
        $this->expectException(UnableToRetrieveMetadataException::class);

        $this->runScenario(function () {
            $this->adapter()->mimeType('non-existing-file.txt');
        });
    }

    /**
     * @test
     */
    public function writing_a_file_with_a_stream(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $writeStream = stream_with_contents('contents');

            $adapter->writeStream('path.txt', $writeStream, new Config());

            $fileExists = $adapter->fileExists('path.txt');

            $this->assertTrue($fileExists);
        });
    }



    /**
     * @test
     */
    public function writing_a_file_with_an_empty_stream(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $writeStream = stream_with_contents('');

            $adapter->writeStream('path.txt', $writeStream, new Config());

            $fileExists = $adapter->fileExists('path.txt');

            $this->assertTrue($fileExists);

            $contents = $adapter->read('path.txt');
            $this->assertEquals('', $contents);
        });
    }
}
