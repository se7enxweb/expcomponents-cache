<?php
/**
 * ezcCacheApcuBackendTest
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
 * Test suite for ezcCacheApcuBackend class.
 *
 * @package Cache
 * @subpackage Tests
 */
class ezcCacheApcuBackendTest extends ezcTestCase
{
    protected $backend;

    public static function suite()
    {
        return new \PHPUnit\Framework\TestSuite( __CLASS__ );
    }

    protected function tearDown() : void
    {
        if ( $this->backend )
        {
            $this->backend->reset();
        }
        $this->backend = null;
    }

    /**
     * Skips unless APCu is loaded and enabled, with the message of what is missing.
     */
    protected function requireApcu()
    {
        if ( !extension_loaded( 'apcu' ) )
        {
            $this->markTestSkipped( "PHP must have the apcu extension." );
        }
        if ( !apcu_enabled() )
        {
            $this->markTestSkipped( "APCu is loaded but disabled: run PHP with -d apc.enable_cli=1." );
        }
    }

    public function testRefusedWithoutExtension()
    {
        if ( extension_loaded( 'apcu' ) )
        {
            $this->markTestSkipped( "PHP has the apcu extension." );
        }
        try
        {
            new ezcCacheApcuBackend();
            $this->fail( "Expected ezcBaseExtensionNotFoundException." );
        }
        catch ( ezcBaseExtensionNotFoundException $e )
        {
            $this->assertStringContainsString( 'apcu', $e->getMessage() );
        }
    }

    public function testRefusedWhenDisabled()
    {
        if ( !extension_loaded( 'apcu' ) )
        {
            $this->markTestSkipped( "PHP must have the apcu extension." );
        }
        if ( apcu_enabled() )
        {
            $this->markTestSkipped( "APCu is enabled: run PHP with -d apc.enable_cli=0 to test the refusal." );
        }
        try
        {
            new ezcCacheApcuBackend();
            $this->fail( "Expected ezcCacheApcException." );
        }
        catch ( ezcCacheApcException $e )
        {
            $this->assertStringContainsString( 'apc.enable_cli', $e->getMessage() );
        }
    }

    public function testStoreFetchDelete()
    {
        $this->requireApcu();
        $this->backend = new ezcCacheApcuBackend();

        $this->assertTrue( $this->backend->store( 'expc_apcu_a', array( 'x' => 1 ), 10 ) );
        $this->assertSame( array( 'x' => 1 ), $this->backend->fetch( 'expc_apcu_a' ) );
        $this->assertTrue( $this->backend->delete( 'expc_apcu_a' ) );
        $this->assertFalse( $this->backend->fetch( 'expc_apcu_a' ) );
    }

    public function testLock()
    {
        $this->requireApcu();
        $this->backend = new ezcCacheApcuBackend();

        $this->backend->acquireLock( 'expc_apcu_lock', 1000, 5 );
        $this->assertNotFalse( apcu_fetch( 'expc_apcu_lock' ) );
        $this->backend->releaseLock( 'expc_apcu_lock' );
        $this->assertFalse( apcu_fetch( 'expc_apcu_lock' ) );
    }

    public function testResetClearsEntries()
    {
        $this->requireApcu();
        $this->backend = new ezcCacheApcuBackend();

        $this->backend->store( 'expc_apcu_b', 'v', 10 );
        $this->backend->reset();
        $this->assertFalse( $this->backend->fetch( 'expc_apcu_b' ) );
    }

    public function testApcStorageRefusedWithoutApcExtension()
    {
        if ( extension_loaded( 'apc' ) )
        {
            $this->markTestSkipped( "PHP has the apc extension." );
        }
        try
        {
            new ezcCacheStorageApcPlain( sys_get_temp_dir() );
            $this->fail( "Expected ezcBaseExtensionNotFoundException." );
        }
        catch ( ezcBaseExtensionNotFoundException $e )
        {
            $this->assertStringContainsString( 'apc', $e->getMessage() );
        }
    }
}
?>
