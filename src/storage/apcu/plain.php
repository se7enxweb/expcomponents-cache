<?php
/**
 * File containing the ezcCacheStorageApcuPlain class.
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
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @filesource
 */

/**
 * This storage implementation stores data in the APCu user cache.
 *
 * It behaves like {@link ezcCacheStorageApcPlain}, but uses the APCu
 * extension (apcu_* functions) instead of the removed apc_* functions. In the
 * CLI, APCu is only active with apc.enable_cli=1.
 *
 * This storage can also be used with {@link ezcCacheStack}.
 *
 * @package Cache
 * @version //autogentag//
 */
class ezcCacheStorageApcuPlain extends ezcCacheStorageApcPlain
{
    /**
     * Creates the APCu backend this storage stores its data in.
     *
     * @return ezcCacheApcuBackend
     */
    protected function createBackend()
    {
        return new ezcCacheApcuBackend();
    }
}
?>
