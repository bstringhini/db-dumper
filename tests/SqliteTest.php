<?php

namespace Spatie\DbDumper\Test;

use PHPUnit\Framework\TestCase;
use Spatie\DbDumper\Compressors\Bzip2Compressor;
use Spatie\DbDumper\Compressors\GzipCompressor;
use Spatie\DbDumper\Databases\Sqlite;

class SqliteTest extends TestCase
{
    /** @test */
    public function it_provides_a_factory_method()
    {
        $this->assertInstanceOf(Sqlite::class, Sqlite::create());
    }

    /** @test */
    public function it_can_generate_a_dump_command()
    {
        $dumpCommand = Sqlite::create()
            ->setDbName('dbname.sqlite')
            ->getDumpCommand('dump.sql');

        $expected = "echo 'BEGIN IMMEDIATE;\n.dump' | 'sqlite3' --bail 'dbname.sqlite' > \"dump.sql\"";

        $this->assertEquals($expected, $dumpCommand);
    }

    /** @test */
    public function it_can_generate_a_dump_command_with_gzip_compressor_enabled()
    {
        $dumpCommand = Sqlite::create()
            ->setDbName('dbname.sqlite')
            ->useCompressor(new GzipCompressor())
            ->getDumpCommand('dump.sql');

        $expected = '((((echo \'BEGIN IMMEDIATE;
.dump\' | \'sqlite3\' --bail \'dbname.sqlite\'; echo $? >&3) | gzip > "dump.sql") 3>&1) | (read x; exit $x))';

        $this->assertEquals($expected, $dumpCommand);
    }

    /** @test */
    public function it_can_generate_a_dump_command_with_bzip2_compressor_enabled()
    {
        $dumpCommand = Sqlite::create()
            ->setDbName('dbname.sqlite')
            ->useCompressor(new Bzip2Compressor())
            ->getDumpCommand('dump.sql');

        $expected = '((((echo \'BEGIN IMMEDIATE;
.dump\' | \'sqlite3\' --bail \'dbname.sqlite\'; echo $? >&3) | bzip2 > "dump.sql") 3>&1) | (read x; exit $x))';

        $this->assertEquals($expected, $dumpCommand);
    }

    /** @test */
    public function it_can_generate_a_dump_command_with_only_specific_tables_included()
    {
        $dumpCommand = Sqlite::create()
            ->setDbName('dbname.sqlite')
            ->includeTables(['users', 'posts'])
            ->getDumpCommand('dump.sql');

        $expected = "echo 'BEGIN IMMEDIATE;\n.dump users posts' | 'sqlite3' --bail 'dbname.sqlite' > \"dump.sql\"";

        $this->assertEquals($expected, $dumpCommand);
    }

    /** @test */
    public function it_can_generate_a_dump_command_without_excluded_tables_included()
    {
        $dbPath = __DIR__ . '/stubs/testDB.sqlite';
        $dumpCommand = Sqlite::create()
            ->setDbName($dbPath)
            ->excludeTables(['tb2', 'tb3'])
            ->getDumpCommand('dump.sql');

        $expected = "echo 'BEGIN IMMEDIATE;\n.dump tb1 tb4' | 'sqlite3' --bail '{$dbPath}' > \"dump.sql\"";

        $this->assertEquals($expected, $dumpCommand);
    }

    /** @test */
    public function it_can_return_current_db_table_list()
    {
        $dbPath = __DIR__ . '/stubs/testDB.sqlite';
        $dumpCommand = Sqlite::create()
            ->setDbName($dbPath)
            ->getDbTables();

        $expected = ['tb1', 'tb2', 'tb3', 'tb4'];

        $this->assertEquals($expected, $dumpCommand);
    }

    /** @test */
    public function it_can_generate_a_dump_command_with_absolute_paths()
    {
        $dumpCommand = Sqlite::create()
            ->setDbName('/path/to/dbname.sqlite')
            ->setDumpBinaryPath('/usr/bin')
            ->getDumpCommand('/save/to/dump.sql');

        $expected = "echo 'BEGIN IMMEDIATE;\n.dump' | '/usr/bin/sqlite3' --bail '/path/to/dbname.sqlite' > \"/save/to/dump.sql\"";

        $this->assertEquals($expected, $dumpCommand);
    }

    /** @test */
    public function it_can_generate_a_dump_command_with_absolute_paths_having_space_and_brackets()
    {
        $dumpCommand = Sqlite::create()
            ->setDbName('/path/to/dbname.sqlite')
            ->setDumpBinaryPath('/usr/bin')
            ->getDumpCommand('/save/to/new (directory)/dump.sql');

        $expected = "echo 'BEGIN IMMEDIATE;\n.dump' | '/usr/bin/sqlite3' --bail '/path/to/dbname.sqlite' > \"/save/to/new (directory)/dump.sql\"";

        $this->assertEquals($expected, $dumpCommand);
    }

    /** @test */
    public function it_successfully_creates_a_backup()
    {
        $dbPath = __DIR__ . '/stubs/database.sqlite';
        $dbBackupPath = __DIR__ . '/temp/backup.sql';

        Sqlite::create()
            ->setDbName($dbPath)
            ->useCompressor(new GzipCompressor())
            ->dumpToFile($dbBackupPath);

        $this->assertFileExists($dbBackupPath);
        $this->assertNotEquals(0, filesize($dbBackupPath), 'Sqlite dump cannot be empty');
    }
}
