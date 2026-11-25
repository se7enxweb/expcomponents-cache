<?php
/**
 * ezcCacheStorageApcuPlainTest
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
require_once 'wrappers/apcu_wrapper.php';

/**
 * Test suite for ezcCacheStorageApcuPlain class.
 *
 * @package Cache
 * @subpackage Tests
 */
class ezcCacheStorageApcuPlainTest extends ezcCacheStorageTest
{
    /**
     * Test data.
     *
     * @var array(string)
     */
    protected $testData = array(
        1 => "Test 1 2 3 4 5 6 7 8\\\\",
        2 => 'La la la 02064 lololo',
        3 => 12345,
        4 => 12.3746,
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
        $options = array( 'ttl' => 10 );
        $storage = new ezcCacheStorageApcuWrapper( $this->getTempDir(), $options );
        $storage->reset();

        foreach ( $this->testData as $id => $dataArr )
        {
            $storage->store( $id, $dataArr );

            // Hack the cache to be outdated by 100 seconds
            $data = $storage->restore( $id );
            $registry = $storage->getRegistry();

            $location = $this->getTempDir() . DIRECTORY_SEPARATOR;
            $identifier = key( $registry[$location][$id] );
            $registry[$location][$id][$identifier]->time = time() - 100;
            $storage->setRegistry( $registry );

            $data = $storage->restore( $id );
            $this->assertTrue( $data === false, "Restore data broken for ID <{$id}>." );
        }

    }

    // Override test from parent class
    public function testStoreRestoreOutdatedWithAttributes()
    {
        $options = array( 'ttl' => 10 );
        $storage = new ezcCacheStorageApcuWrapper( $this->getTempDir(), $options );
        $storage->reset();

        foreach ( $this->testData as $id => $dataArr )
        {
            $attributes = array(
                'name'      => 'test',
                'title'     => 'Test item',
                'date'      => time() . $id,
            );

            $storage->store( $id, $dataArr, $attributes );

            // Hack the cache to be outdated by 100 seconds
            $data = $storage->restore( $id, $attributes );
            $registry = $storage->getRegistry();

            $location = $this->getTempDir() . DIRECTORY_SEPARATOR;
            $identifier = key( $registry[$location][$id] );
            $registry[$location][$id][$identifier]->time = time() - 100;
            $storage->setRegistry( $registry );

            // Wait for cache to be outdated.
            $data = $storage->restore( $id, $attributes );
            $this->assertTrue( $data === false, "Restore data broken for ID <{$id}>." );
        }
    }

    public function testMockApcuBackend()
    {
        $apcBackend = $this->getMockBuilder( 'ezcCacheApcuBackend' )
                            ->onlyMethods( array( 'store' ) )
                            ->getMock();
        $apcBackend->method( 'store' )->willReturn( false );

        $options = array( 'ttl' => 10 );
        $storage = new ezcCacheStorageApcuWrapper( '.', $options );
        $storage->reset();
        $storage->setBackend( $apcBackend );

        $id = 'id';
        try
        {
            $storage->store( $id, 'data' );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcCacheApcException $e )
        {
            $this->assertEquals( "Apc store failed.", $e->getMessage() );
        }
    }

    public function testStoreResource()
    {
        $resource = fopen( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'wrappers' . DIRECTORY_SEPARATOR . 'apcu_wrapper.php', 'r' );
        try
        {
            $this->storage->store( "key", $resource );
            $this->storage->reset();
            fclose( $resource );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcCacheInvalidDataException $e )
        {
            fclose( $resource );
            $this->assertEquals( "The given data was of type 'resource', which can not be stored. Expecting: 'simple, array, object'.", $e->getMessage() );
        }
    }

    public function testGetRemainingLifetimeId()
    {
        $this->storage->setOptions( array( 'ttl' => 10 ) );

        $this->storage->store( '1', 'data1' );

        // "8 <= " - for those cases where the current second changes after the storage
        $this->assertEquals( true, 8 <= $this->storage->getRemainingLifetime( '1' ) );

    }

    public function testGetRemainingLifetimeAttributes()
    {
        $this->storage->reset();
        $this->storage->setOptions( array( 'ttl' => 10 ) );

        $this->storage->store( '1', 'data1', array( 'type' => 'simple' ) );
        $this->storage->store( '2', 'data2', array( 'type' => 'simple' ) );

        // "8 <= " - for those cases where the current second changes after the storage
        $this->assertGreaterThan(
            8,
            $this->storage->getRemainingLifetime( null, array( 'type' => 'simple' ) )
        );

    }

    public function testGetRemainingLifetimeNoMatch()
    {
        $this->storage->setOptions( array( 'ttl' => 10 ) );

        $this->assertEquals( 0, $this->storage->getRemainingLifetime( 'no_such_id' ) );

    }

    public function testCacheManagerLocationEmpty()
    {
        $options = array( 'ttl' => 10 );
        ezcCacheManager::createCache( 'cache', null, 'ezcCacheStorageApcuPlain', $options );
        $storage = ezcCacheManager::getCache( 'cache' );
        $storage->reset();
        $storage->store( 'key', 'data' );
        $this->assertEquals( 'data', $storage->restore( 'key' ) );
    }

    public function testStorageApcOptions()
    {
        $options = new ezcCacheStorageApcOptions();

        $this->assertTrue( isset( $options->ttl ) );
        $this->assertTrue( isset( $options->lockWaitTime ) );
        $this->assertTrue( isset( $options->maxLockTime ) );
        $this->assertTrue( isset( $options->lockKey ) );
        $this->assertTrue( isset( $options->metaDataKey ) );

        $this->assertEquals( $options->ttl, 86400 );

        $this->assertFalse( isset( $options->foo ), 'Exception not thrown on invalid property.' );

        $this->assertSetPropertyFails(
            $options,
            'ttl',
            array( true, 42.23, array(), new stdClass(), '', 'foo' )
        );
        $this->assertSetPropertyFails(
            $options,
            'lockWaitTime',
            array( true, false, -23, 42.23, array(), new stdClass(), '', 'foo' )
        );
        $this->assertSetPropertyFails(
            $options,
            'maxLockTime',
            array( true, false, -23, 42.23, array(), new stdClass(), '', 'foo' )
        );
        $this->assertSetPropertyFails(
            $options,
            'lockKey',
            array( true, false, 42, -23, 42.23, array(), new stdClass(), '' )
        );
        $this->assertSetPropertyFails(
            $options,
            'metaDataKey',
            array( true, false, 42, -23, 42.23, array(), new stdClass(), '' )
        );

        $this->assertSetProperty( $options, 'ttl', array( 1, 1000 ) );
        $this->assertSetProperty( $options, 'lockWaitTime', array( 1, 10 ) );
        $this->assertSetProperty( $options, 'maxLockTime', array( 1, 10 ) );
        $this->assertSetProperty( $options, 'lockKey', array( 'foo' ) );
        $this->assertSetProperty( $options, 'metaDataKey', array( 'foo' ) );

        try
        {
            $options->foo = 23;
            $this->fail( 'Exception not thrown on set for unknown property.' );
        }
        catch ( ezcBasePropertyNotFoundException $e ) {}
        try
        {
            echo $options->foo;
            $this->fail( 'Exception not thrown on get for unknown property.' );
        }
        catch ( ezcBasePropertyNotFoundException $e ) {}
    }

    public function testStorageOptions()
    {
        $storageOptions = new ezcCacheStorageOptions();
        $storageApcOptions = new ezcCacheStorageApcOptions();
        $arrayOptions = array();

        $storage = new ezcCacheStorageApcuPlain(
            '.',
            array()
        );
        $storage->reset();

        $storage->setOptions( $storageApcOptions );
        $this->assertEquals( $storageApcOptions, $storage->getOptions() );

        $storage->options = $storageApcOptions;
        $this->assertEquals( $storageApcOptions, $storage->getOptions() );

        $storage->setOptions( $storageOptions );
        $this->assertEquals( $storageApcOptions, $storage->getOptions() );

        $storage->options = $storageOptions;
        $this->assertEquals( $storageApcOptions, $storage->getOptions() );

        $storage->setOptions( $arrayOptions );
        $this->assertEquals( $storageApcOptions, $storage->getOptions() );

        $storage->options = $arrayOptions;
        $this->assertEquals( $storageApcOptions, $storage->getOptions() );

        $options = new stdClass();
        try
        {
            $storage->setOptions( $options );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcBaseValueException $e )
        {
            $this->assertEquals(
                "The value 'O:8:\"stdClass\":0:{}' that you were trying to "
                    . "assign to setting 'options' is invalid. Allowed values "
                    . "are: instance of ezcCacheStorageApcOptions.",
                $e->getMessage()
            );
        }
    }

    public function testResetSuccess()
    {
        $options = array( 'ttl' => 10 );
        $storage = new ezcCacheStorageApcuWrapper( '.', $options );
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
    }

    public function testDeleteReturnIds()
    {
        $storage = new ezcCacheStorageApcuWrapper(
            '.',
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
    }

    public function testPurgeNoLimit()
    {
        $storage = new ezcCacheStorageApcuWrapper(
            '.',
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
    }

    public function testPurgeLimit()
    {
        $storage = new ezcCacheStorageApcuWrapper(
            '.',
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
    }

    public function testLockSimple()
    {
        $storage = new ezcCacheStorageApcuWrapper(
            '.',
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
    }

    public function testLockTimeout()
    {
        // Init 2 storages on same Memcache
        $opts = array(
            'ttl'         => 1,
            'maxLockTime' => 1
        );
        $storage = new ezcCacheStorageApcuWrapper(
            '.',
            $opts
        );
        $storage->reset();
        $secondStorage = new ezcCacheStorageApcuWrapper(
            '.',
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
    }

    public function testMetaDataSuccess()
    {
        $opts = array(
            'ttl'         => 1,
        );
        $storage = new ezcCacheStorageApcuWrapper(
            '.',
            $opts
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
    }

    public function testMetaDataFailure()
    {
        $opts = array(
            'ttl'         => 1,
        );
        $storage = new ezcCacheStorageApcuWrapper(
            '.',
            $opts
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

    public function testRoundTrip()
    {
        $this->storage->reset();
        foreach ( $this->testData as $id => $data )
        {
            $this->storage->store( $id, $data );
            $this->assertSame( $data, $this->storage->restore( $id ), "Round trip broken for ID <{$id}>." );
        }
    }

    public function testValueExpires()
    {
        $storage = new ezcCacheStorageApcuPlain( '.', array( 'ttl' => 2 ) );
        $storage->reset();
        $storage->store( 'expiring', 'data' );
        $this->assertSame( 'data', $storage->restore( 'expiring' ) );

        usleep( 3100000 );

        $this->assertFalse( $storage->restore( 'expiring' ) );
    }

    public function testUsableInStack()
    {
        $this->storage->reset();
        $tmpDir = $this->createTempDir( 'ezcCacheStackApcuTest' );
        $metaStorage = new ezcCacheStorageFileArray( $tmpDir );

        $stack = new ezcCacheStack(
            'stack_apcu',
            new ezcCacheStackOptions( array( 'metaStorage' => $metaStorage ) )
        );
        $stack->pushStorage(
            new ezcCacheStackStorageConfiguration( 'apcu_storage', $this->storage, 10, .5 )
        );

        $stack->store( 'stacked', array( 'a' => 1 ) );
        $this->assertSame( array( 'a' => 1 ), $stack->restore( 'stacked' ) );
        $this->assertSame( array( 'a' => 1 ), $this->storage->restore( 'stacked' ) );
    }

    public function testSharesStoreWithApcStorage()
    {
        if ( !extension_loaded( 'apc' ) || !function_exists( 'apc_store' ) )
        {
            $this->markTestSkipped( "PHP must have the apc functions (apcu_bc) next to apcu." );
        }
        if ( !ini_get( 'apc.enabled' ) || !ini_get( 'apc.enable_cli' ) )
        {
            $this->markTestSkipped( "APC is loaded but disabled: run PHP with -d apc.enable_cli=1." );
        }

        $location = $this->getTempDir();
        $apc = new ezcCacheStorageApcPlain( $location, array( 'ttl' => 10 ) );
        $apcu = new ezcCacheStorageApcuPlain( $location, array( 'ttl' => 10 ) );
        $apc->reset();

        $apc->store( 'shared', array( 'x' => 1 ) );
        $this->assertSame( array( 'x' => 1 ), $apcu->restore( 'shared' ) );
    }

    public static function suite()
	{
		return new PHPUnit\Framework\TestSuite( __CLASS__ );
	}
}
?>
