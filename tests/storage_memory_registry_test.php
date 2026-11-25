<?php
/**
 * ezcCacheStorageMemoryRegistryTest
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
 * A memory backend that keeps its data in an array, so the memory storage can
 * be tested without APC, APCu or memcached.
 *
 * @package Cache
 * @subpackage Tests
 */
class ezcCacheArrayTestBackend extends ezcCacheMemoryBackend
{
    public $data = array();

    public function store( $key, $var, $ttl = 0 )
    {
        $this->data[$key] = $var;
        return true;
    }

    public function fetch( $key )
    {
        return array_key_exists( $key, $this->data ) ? $this->data[$key] : false;
    }

    public function delete( $key )
    {
        unset( $this->data[$key] );
        return true;
    }
}

/**
 * The APC plain storage on the array backend.
 *
 * @package Cache
 * @subpackage Tests
 */
class ezcCacheStorageArrayBackendPlain extends ezcCacheStorageApcPlain
{
    protected function createBackend()
    {
        return new ezcCacheArrayTestBackend();
    }
}

/**
 * Test suite for the search registry of the memory storages.
 *
 * @package Cache
 * @subpackage Tests
 */
class ezcCacheStorageMemoryRegistryTest extends ezcTestCase
{
    /**
     * A location whose registry is not in the backend yet: the backend answers
     * false, which must become an empty registry, not a value PHP converts.
     */
    public function testFirstStoreOnEmptyBackendRaisesNoNotice()
    {
        if ( !method_exists( 'ezcCacheStorageApc', 'createBackend' ) )
        {
            $this->markTestSkipped( 'The storage has no createBackend() hook.' );
        }

        $storage = new ezcCacheStorageArrayBackendPlain( 'registry_test', array( 'ttl' => 10 ) );

        set_error_handler( function ( $severity, $message, $file, $line )
        {
            throw new ErrorException( $message, 0, $severity, $file, $line );
        } );
        try
        {
            $storage->store( 'id1', 'value1', array( 'lang' => 'en' ) );
            $restored = $storage->restore( 'id1', array( 'lang' => 'en' ) );
            $count = $storage->countDataItems( 'id1' );
        }
        finally
        {
            restore_error_handler();
        }

        $this->assertSame( 'value1', $restored );
        $this->assertSame( 1, $count );
    }
}
?>
