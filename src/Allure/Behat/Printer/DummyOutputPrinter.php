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
namespace Allure\Behat\Printer;

use Behat\Testwork\Output\Printer\OutputPrinter as PrinterInterface;

class DummyOutputPrinter implements PrinterInterface
{
    protected $outputPath = 'allure-results';
    protected $outputStyles;
    protected $rendererFiles;

    /**
     * Sets output path.
     *
     * @param string $path
     */
    public function setOutputPath($path)
    {
        $this->outputPath = $path;
    }

    /**
     * Returns output path.
     *
     * @return string|null
     *
     * @deprecated since 3.1, to be removed in 4.0
     */
    public function getOutputPath()
    {
        return $this->outputPath;
    }

    /**
     * Sets output styles.
     *
     * @param array $styles
     */
    public function setOutputStyles(array $styles)
    {
        $this->outputStyles = $styles;
    }

    /**
     * Returns output styles.
     *
     * @return array
     *
     * @deprecated since 3.1, to be removed in 4.0
     */
    public function getOutputStyles()
    {
        return $this->outputStyles;
    }

    /**
     * Forces output to be decorated.
     *
     * @param bool $decorated
     */
    public function setOutputDecorated($decorated)
    {
        // TODO: Implement setOutputDecorated() method.
    }

    /**
     * Returns output decoration status.
     *
     * @return bool|null
     *
     * @deprecated since 3.1, to be removed in 4.0
     */
    public function isOutputDecorated()
    {
        return true;
    }

    /**
     * Sets output verbosity level.
     *
     * @param int $level
     */
    public function setOutputVerbosity($level)
    {
        // TODO: Implement setOutputVerbosity() method.
    }

    /**
     * Returns output verbosity level.
     *
     * @return int
     *
     * @deprecated since 3.1, to be removed in 4.0
     */
    public function getOutputVerbosity()
    {
        // TODO: Implement getOutputVerbosity() method.
    }

    /**
     * Writes message(s) to output stream.
     *
     * @param string|array $messages message or array of messages
     */
    public function write($messages)
    {
        // TODO: Implement write() method.
    }

    /**
     * Writes newlined message(s) to output stream.
     *
     * @param string|array $messages message or array of messages
     */
    public function writeln($messages = '')
    {
        // TODO: Implement writeln() method.
    }

    /**
     * Clear output stream, so on next write formatter will need to init (create)
     * it again.
     */
    public function flush()
    {
        // TODO: Implement flush() method.
    }
}
