<?php
/**
 * Copyright (c) 2018 Tiko Lakin.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * See LICENSE.md for full license text.
 */
namespace Allure\Behat\Exception;

interface ArtifactExceptionInterface
{
    /**
     * ArtifactExceptionInterface constructor.
     *
     * @param $message
     * @param \Behat\Mink\Driver\CoreDriver $driver
     * @param \Exception $previous
     */
    public function __construct($message, \Behat\Mink\Driver\CoreDriver $driver, \Exception $previous = null);

    /**
     * @return string|void
     */
    public function getUrl();

    /**
     * @return string|void
     */
    public function getScreenPath();

    /**
     * @return string|void
     */
    public function getHtmlPath();
}
