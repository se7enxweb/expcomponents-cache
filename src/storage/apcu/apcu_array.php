<?php
/**
 * File containing the ezcCacheStorageFileApcuArray class.
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
 * This class is a replacement for the {@link ezcCacheStorageFileArray} class. Tries
 * to serve data from the APCu user cache if possible.
 *
 * It behaves like {@link ezcCacheStorageFileApcArray}, but uses the APCu
 * extension (apcu_* functions) instead of the removed apc_* functions. In the
 * CLI, APCu is only active with apc.enable_cli=1.
 *
 * Options for this class are defined in {@link ezcCacheStorageFileApcArrayOptions}.
 *
 * @package Cache
 * @version //autogentag//
 */
class ezcCacheStorageFileApcuArray extends ezcCacheStorageFileApcArray
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
