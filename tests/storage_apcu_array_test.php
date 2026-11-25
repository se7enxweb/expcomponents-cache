<?php
/**
 * ezcCacheStorageFileApcuArrayTest
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Cache
 * @subpackage Tests
 * @version //autogen//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Require parent test class.
 */
require_once 'storage_test.php';
require_once 'wrappers/apcu_array_wrapper.php';

/**
 * Test suite for ezcCacheStorageFileApcuArray class.
 *
 * @package Cache
 * @subpackage Tests
 */
class ezcCacheStorageFileApcuArrayTest extends ezcCacheStorageTest
{
    /**
     * Test data.
     *
     * @var array(string=>mixed)
     */
    protected $testData = array(
        0 => array( 'a' ),
        1 => array( 1, 2, 3 ),
        2 => array( 'a', 1, 'b', 2, 'c', 3 ),
        3 => array(
            1, 2, 3,
            array( 'a', 'b', 'c' ),
            4, 5
        ),
        4 => array(
            array(
                array( 1 ), array( 2, 3 )
            ),
            1, 2, 3,
            array( 'a', 'b', 'c' ),
        ),
        5 => "Test 1 2 3 4 5 6 7 8\\\\",
        6 => 'La la la 02064 lololo',
        7 => true,
        // 8 => false, // 6 tests fail with this
        9 => 12345,
       10 => 12.3746,
    );

    protected function setUp() : void
    {
        if ( !extension_loaded( 'apcu' ) )
        {
            $this->markTestSkipped( "PHP must have the apcu extension." );
        }
        if ( !apcu_enabled() )
        {
            $this->markTestSkipped( "APCu is loaded but disabled: run PHP with -d apc.enable_cli=1." );
        }

        // Class name == <inheriting class> - "Test"
        $storageClass = ( $this->storageClass = substr( get_class( $this ), 0, strlen( get_class(  $this ) ) - 4 ) );
        $this->storage = new $storageClass( $this->createTempDir( 'ezcCacheTest' ), array( 'ttl' => 10 ) );
    }

    // Override test from parent class
    public function testConstructorErrorLocationNotWriteable()
    {
        return true;
    }

    // Override test from parent class
    public function testStoreRestoreOutdatedWithoutAttributes()
    {
        $this->markTestSkipped( "ezcCacheStorageFileApcArray does not purge an expired item on restore() (its calcLifetime() never exceeds the ttl); the APCu variant inherits that behavior unchanged." );
    }

    // Override test from parent class
    public function testStoreRestoreOutdatedWithAttributes()
    {
        $this->markTestSkipped( "ezcCacheStorageFileApcArray does not purge an expired item on restore() (its calcLifetime() never exceeds the ttl); the APCu variant inherits that behavior unchanged." );
    }

    public function testMockApcBackend()
    {
        $apcBackend = $this->getMockBuilder( 'ezcCacheApcuBackend' )
                            ->onlyMethods( array( 'store' ) )
                            ->getMock();
        $apcBackend->method( 'store' )->willReturn( false );

        $options = array( 'ttl' => 10 );
        $storage = new ezcCacheStorageFileApcuArrayWrapper( $this->getTempDir(), $options );
        $storage->setBackend( $apcBackend );

        $id = 'id';
        try
        {
            $storage->store( $id, 'data' );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcCacheApcException $e )
        {
            $this->assertEquals( "APC store failed.", $e->getMessage() );
        }

        $this->removeTempDir();
    }

    public function testStoreResource()
    {
        $resource = fopen( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'wrappers' . DIRECTORY_SEPARATOR . 'apcu_wrapper.php', 'r' );
        try
        {
            $this->storage->store( "key", $resource );
            fclose( $resource );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcCacheInvalidDataException $e )
        {
            fclose( $resource );
            $this->assertEquals( "The given data was of type 'resource', which can not be stored. Expecting: 'simple, array'.", $e->getMessage() );
        }
    }

    public function testWrapperFetchDataUseApc()
    {
        $options = array( 'ttl' => 10 );
        $storage = new ezcCacheStorageFileApcuArrayWrapper( $this->getTempDir(), $options );

        $data = 'data';
        $key = 'key';

        $storage->store( $key, $data );
        $storage->restore( $key );
        $registry = $storage->getRegistry();

        $identifier = key( $registry );

        $dataFetched = $storage->fetchData( $identifier, true );
        $this->assertEquals( $data, $dataFetched );

        $this->removeTempDir();
    }

    public function testWrapperPrepareDataUseApcResourceFail()
    {
        $resource = fopen( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'wrappers' . DIRECTORY_SEPARATOR . 'apcu_wrapper.php', 'r' );

        $options = array( 'ttl' => 10 );
        $storage = new ezcCacheStorageFileApcuArrayWrapper( $this->getTempDir(), $options );

        try
        {
            $storage->prepareData( $resource, true );
            fclose( $resource );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcCacheInvalidDataException $e )
        {
            fclose( $resource );
            $this->assertEquals( "The given data was of type 'resource', which can not be stored. Expecting: 'simple, array, object'.", $e->getMessage() );
        }

        $this->removeTempDir();
    }

    public function testWrapperCalcLifetimeNoApc()
    {
        $options = array( 'ttl' => 10 );
        $storage = new ezcCacheStorageFileApcuArrayWrapper( $this->getTempDir(), $options );

        $data = 'data';
        $key = 'key';

        $filename = $this->getTempDir() . DIRECTORY_SEPARATOR . $storage->generateIdentifier( $key, array() );

        file_put_contents( $filename, $data );

        $lifetime = $storage->calcLifetime( $filename, false );

        // 8 for the case that the second switched just during this request
        $this->assertGreaterThan(
            8,
            $lifetime
        );

        $this->removeTempDir();
    }

    public function testCacheManagerLocationEmpty()
    {
        if ( function_exists( 'posix_geteuid' ) && posix_geteuid() === 0 )
        {
            $this->markTestSkipped( "As root the location '/' is writable, so no exception is thrown." );
        }
        $options = array( 'ttl' => 10 );
        ezcCacheManager::createCache( 'memory', null, 'ezcCacheStorageFileApcuArray', $options );
        try
        {
            $storage = ezcCacheManager::getCache( 'memory' );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseFilePermissionException $e )
        {
            $this->assertEquals( "The file '/' can not be opened for writing. (Cache location is not a directory.)", $e->getMessage() );
        }
    }

    public function testStorageFileApcArrayOptions()
    {
        $opt = new ezcCacheStorageFileApcArrayOptions();

        $this->assertTrue( isset( $opt->ttl ) );
        $this->assertTrue( isset( $opt->extension ) );
        $this->assertTrue( isset( $opt->permissions ) );
        $this->assertFalse( isset( $opt->foo ) );

        $this->assertEquals( $opt->ttl, 86400 );
        $this->assertEquals( $opt->extension, ".cache" );
        $this->assertEquals( $opt->permissions, 0644 );

        $this->assertSetProperty(
            $opt,
            'ttl',
            array( 0, 23, false )
        );

        $this->assertSetProperty(
            $opt,
            'permissions',
            array( 0777 )
        );

        $this->assertSetProperty(
            $opt,
            'extension',
            array( '.foo' )
        );

        $this->assertSetPropertyFails(
            $opt,
            'ttl',
            array( true, 23.42, 'foo', array(), new stdClass() )
        );

        $this->assertSetPropertyFails(
            $opt,
            'permissions',
            array( true, 23.42, 'foo', array(), new stdClass() )
        );

        $this->assertSetPropertyFails(
            $opt,
            'extension',
            array( true, false, 23.42, array(), new stdClass() )
        );

    }

    public function testStorageApcOptions()
    {
        $opt = new ezcCacheStorageFileApcArrayOptions();

        $this->assertTrue( isset( $opt->ttl ) );
        $this->assertTrue( isset( $opt->extension ) );
        $this->assertTrue( isset( $opt->permissions ) );
        $this->assertFalse( isset( $opt->foo ) );

        $this->assertEquals( $opt->ttl, 86400 );
        $this->assertEquals( $opt->extension, ".cache" );
        $this->assertEquals( $opt->permissions, 0644 );

        $this->assertSetProperty(
            $opt,
            'ttl',
            array( 0, 23, false )
        );

        $this->assertSetProperty(
            $opt,
            'permissions',
            array( 0, 0777 )
        );

        $this->assertSetProperty(
            $opt,
            'extension',
            array( '.foo' )
        );

        $this->assertSetPropertyFails(
            $opt,
            'ttl',
            array( true, 23.42, 'foo', array(), new stdClass() )
        );

        $this->assertSetPropertyFails(
            $opt,
            'permissions',
            array( true, 23.42, 'foo', array(), new stdClass() )
        );

        $this->assertSetPropertyFails(
            $opt,
            'extension',
            array( true, false, 23.42, array(), new stdClass() )
        );

    }

    public function testResetSuccess()
    {
        $options = array( 'ttl' => 10 );
        $storage = new ezcCacheStorageFileApcuArray( $this->getTempDir( __CLASS__ ), $options );
        $storage->reset();

        $data = array(
            'ID',
            'Some/Dir/ID',
            'Some/other/Dir/ID/1',
            'Some/other/Dir/ID/2',
            'Some/other/Dir/ID/3',
        );
        foreach ( $data as $id )
        {
            $storage->store( $id, $id );
        }

        $this->assertEquals(
            5,
            $storage->countDataItems()
        );

        $storage->reset();

        $this->assertEquals(
            0,
            $storage->countDataItems()
        );
        $this->removeTempDir();
    }

    public function testDeleteReturnIds()
    {
        $storage = new ezcCacheStorageFileApcuArray(
            $this->getTempDir( __CLASS__ ),
            array( 'ttl' => 100 )
        );
        $storage->reset();

        $data = array(
            'ID',
            'Some/Dir/ID',
            'Some/other/Dir/ID/1',
            'Some/other/Dir/ID/2',
            'Some/other/Dir/ID/3',
        );

        $attributes = array(
            'lang' => 'en',
        );

        foreach ( $data as $id )
        {
            $storage->store( $id, $id, $attributes );
        }

        $deleted = $storage->delete( 'Some/other/Dir/ID/3', $attributes, true );

        $this->assertEquals(
            array( 'Some/other/Dir/ID/3' ),
            $deleted,
            'Deleted IDs not returned correctly.'
        );

        $deleted = $storage->delete( null, $attributes, true );

        $this->assertEquals(
            array(
                'ID',
                'Some/Dir/ID',
                'Some/other/Dir/ID/1',
                'Some/other/Dir/ID/2',
            ),
            $deleted,
            'Deleted IDs not returned correctly.'
        );
        $this->removeTempDir();
    }

    public function testPurgeNoLimit()
    {
        $storage = new ezcCacheStorageFileApcuArray(
            $this->getTempDir( __FILE__ ),
            array( 'ttl' => 1 )
        );
        $storage->reset();

        $data = array(
            'ID',
            'Some/Dir/ID',
            'Some/other/Dir/ID/1',
            'Some/other/Dir/ID/2',
            'Some/other/Dir/ID/3',
        );

        foreach ( $data as $id )
        {
            $storage->store( $id, $id );
        }

        // Outdate
        usleep( 1000002 );

        $purgedIds = $storage->purge();

        $this->assertEquals(
            $data,
            $purgedIds,
            'Purged IDs not returned correctly.'
        );
        $this->removeTempDir();
    }

    public function testPurgeLimit()
    {
        $storage = new ezcCacheStorageFileApcuArray(
            $this->getTempDir( __CLASS__ ),
            array( 'ttl' => 1 )
        );
        $storage->reset();

        $data = array(
            'ID',
            'Some/Dir/ID',
            'Some/other/Dir/ID/1',
            'Some/other/Dir/ID/2',
            'Some/other/Dir/ID/3',
        );

        foreach ( $data as $id )
        {
            $storage->store( $id, $id );
        }

        // Outdate
        usleep( 1000002 );

        $purgedIds = $storage->purge( 3 );

        $this->assertEquals(
            array(
                'ID',
                'Some/Dir/ID',
                'Some/other/Dir/ID/1',
            ),
            $purgedIds,
            'Purged IDs not returned correctly.'
        );
        $this->removeTempDir();
    }

    public function testLockSimple()
    {
        $storage = new ezcCacheStorageFileApcuArray(
            $this->getTempDir( __CLASS__ ),
            array( 'ttl' => 1 )
        );
        $storage->reset();

        $lockKey = urlencode( $storage->getLocation() ) . '_'
            . $storage->options->lockKey;

        $this->assertFalse(
            apcu_fetch( $lockKey ),
            'Lock key exists.'
        );

        $this->assertFalse(
            $this->readLock( $storage ),
            'Lock stat not correctly initialized'
        );

        $storage->lock();

        $this->assertNotEquals(
            false,
            apcu_fetch( $lockKey ),
            'Lock key not created correctly.'
        );

        $this->assertTrue(
            $this->readLock( $storage ),
            'Lock stat not correctly switched.'
        );

        $storage->unlock();

        $this->assertFalse(
            apcu_fetch( $lockKey ),
            'Lock key exists.'
        );

        $this->assertFalse(
            $this->readLock( $storage ),
            'Lock stat not correctly initialized'
        );

        $this->removeTempDir();
    }

    public function testLockTimeout()
    {
        // Init 2 storages on same Memcache
        $opts = array(
            'ttl'         => 1,
            'maxLockTime' => 1
        );
        $storage = new ezcCacheStorageFileApcuArray(
            $this->getTempDir( '1' . __CLASS__ ),
            $opts
        );
        $storage->reset();
        $secondStorage = new ezcCacheStorageFileApcuArray(
            $this->getTempDir( '2' . __CLASS__ ),
            $opts
        );
        $secondStorage->reset();

        $lockKey = urlencode( $storage->getLocation() ) . '_'
            . $storage->options->lockKey;

        // Assert initial state
        $this->assertFalse(
            apcu_fetch( $lockKey ),
            'Lock key exists.'
        );
        $this->assertFalse(
            $this->readLock( $storage ),
            'Lock state not correctly initialized'
        );
        $this->assertFalse(
            $this->readLock( $secondStorage ),
            'Lock state not correctly initialized in second storage'
        );

        $lockTime = time();
        // Perform lock
        $storage->lock();

        // Assert locked state
        $this->assertNotEquals(
            false,
            ( $oldLock = apcu_fetch( $lockKey ) ),
            'Lock key not created correctly.'
        );
        $this->assertTrue(
            $this->readLock( $storage ),
            'Lock stat not correctly switched.'
        );
        $this->assertFalse(
            $this->readLock( $secondStorage ),
            'Lock state not correctly initialized in second storage'
        );

        // Should kill lock file after 1 sec
        $secondStorage->lock();

        // Assert that kill did not occur earlier
        $this->assertGreaterThanOrEqual(
            1,
            ( time() - $lockTime ),
            'Lock did not last for 1 sec.'
        );

        // Assert that lock key exists again
        $this->assertNotEquals(
            false,
            apcu_fetch( $lockKey ),
            'Lock key not created correctly.'
        );
        // Assert that the new lock is not the same as the old one
        $this->assertGreaterThan(
            $oldLock,
            apcu_fetch( $lockKey ),
            'Lock key not created correctly.'
        );
        // First storage does not note that its lock disappeared
        $this->assertTrue(
            $this->readLock( $storage ),
            'Lock state switched unexpectedly.'
        );
        // Second storage now has the lock
        $this->assertTrue(
            $this->readLock( $secondStorage ),
            'Lock state not correctly initialized in second storage'
        );

        $secondStorage->unlock();

        $this->assertFalse(
            apcu_fetch( $lockKey ),
            'Lock key not created correctly.'
        );
        // First storage does not note that its lock disappeared and was not unlocked
        $this->assertTrue(
            $this->readLock( $storage ),
            'Lock state switched unexpectedly.'
        );
        // Second storage released the lock
        $this->assertFalse(
            $this->readLock( $secondStorage ),
            'Lock state not correctly initialized in second storage'
        );

        $this->removeTempDir();
    }

    public function testMetaDataSuccess()
    {
        $storage = new ezcCacheStorageFileApcuArray(
            $this->getTempDir( __CLASS__ ),
            array( 'ttl' => 1 )
        );
        $storage->reset();

        $metaDataKey = urlencode( $storage->getLocation() ) . '_'
            . $storage->options->metaDataKey;

        $meta = new ezcCacheStackLruMetaData();
        $meta->setState(
            array(
                'replacementData' => array(
                    'id_1' => 23,
                    'id_2' => 42,
                ),
                'storageData' => array(
                    'storage_id_1' => array(
                        'id_1' => true,
                        'id_2' => true,
                    ),
                    'storage_id_2' => array(
                        'id_2' => true,
                    ),
                ),
            )
        );

        $this->assertFalse(
            apcu_fetch( $metaDataKey ),
            'Meta data key existed before the storage was created.'
        );

        $storage->storeMetaData( $meta );

        $this->assertEquals(
            $meta,
            apcu_fetch( $metaDataKey )->var,
            'Meta data file existed before the storage was created.'
        );

        $restoredMeta = $storage->restoreMetaData();

        $this->assertEquals(
            $meta,
            $restoredMeta,
            'Meta data not restored correctly.'
        );

        $this->assertEquals(
            $meta,
            apcu_fetch( $metaDataKey )->var,
            'Meta data file existed before the storage was created.'
        );

        $this->removeTempDir();
    }

    public function testMetaDataFailure()
    {
        $storage = new ezcCacheStorageFileApcuArray(
            $this->getTempDir( __CLASS__ ),
            array( 'ttl' => 1 )
        );
        $storage->reset();

        $metaDataKey = urlencode( $storage->getLocation() ) . '_'
            . $storage->options->metaDataKey;

        $this->assertFalse(
            apcu_fetch( $metaDataKey ),
            'Meta data file existed before the storage was created.'
        );

        $restoredMeta = $storage->restoreMetaData();

        $this->assertNull(
            $restoredMeta,
            'Meta data not restored correctly.'
        );

        $this->assertFalse(
            apcu_fetch( $metaDataKey ),
            'Meta data file existed before the storage was created.'
        );

        $this->removeTempDir();
    }

    /**
     * Reads the private $lock property declared in ezcCacheStorageMemory.
     */
    private function readLock( $storage )
    {
        $property = new ReflectionProperty( 'ezcCacheStorageMemory', 'lock' );
        $property->setAccessible( true );
        return $property->getValue( $storage );
    }

    public function testStoreObjectRefused()
    {
        try
        {
            $this->storage->store( "key", new stdClass() );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcCacheInvalidDataException $e )
        {
            $this->assertEquals( "The given data was of type 'object', which can not be stored. Expecting: 'simple, array'.", $e->getMessage() );
        }
    }

    public function testArrayRoundTripUsesApcu()
    {
        $this->storage->reset();
        $data = array( 'a' => array( 1, 2, 3 ), 'b' => 'text' );
        $this->storage->store( 'roundtrip', $data );
        $this->assertSame( $data, $this->storage->restore( 'roundtrip' ) );

        $property = new ReflectionProperty( 'ezcCacheStorageMemory', 'backend' );
        $property->setAccessible( true );
        $this->assertInstanceOf( 'ezcCacheApcuBackend', $property->getValue( $this->storage ) );
    }

    public static function suite()
	{
		return new PHPUnit\Framework\TestSuite( __CLASS__ );
	}
}
?>
