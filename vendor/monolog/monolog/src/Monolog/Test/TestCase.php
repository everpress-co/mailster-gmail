<?php

declare (strict_types=1);
/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Mailster\Monolog\Test;

use Mailster\Monolog\Logger;
use Mailster\Monolog\DateTimeImmutable;
use Mailster\Monolog\Formatter\FormatterInterface;
/**
 * Lets you easily generate log records and a dummy formatter for testing purposes
 * *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class TestCase extends \Mailster\PHPUnit\Framework\TestCase
{
    /**
     * @return array Record
     */
    protected function getRecord($level = \Mailster\Monolog\Logger::WARNING, $message = 'test', array $context = []) : array
    {
        return ['message' => (string) $message, 'context' => $context, 'level' => $level, 'level_name' => \Mailster\Monolog\Logger::getLevelName($level), 'channel' => 'test', 'datetime' => new \Mailster\Monolog\DateTimeImmutable(\true), 'extra' => []];
    }
    protected function getMultipleRecords() : array
    {
        return [$this->getRecord(\Mailster\Monolog\Logger::DEBUG, 'debug message 1'), $this->getRecord(\Mailster\Monolog\Logger::DEBUG, 'debug message 2'), $this->getRecord(\Mailster\Monolog\Logger::INFO, 'information'), $this->getRecord(\Mailster\Monolog\Logger::WARNING, 'warning'), $this->getRecord(\Mailster\Monolog\Logger::ERROR, 'error')];
    }
    /**
     * @suppress PhanTypeMismatchReturn
     */
    protected function getIdentityFormatter() : \Mailster\Monolog\Formatter\FormatterInterface
    {
        $formatter = $this->createMock(\Mailster\Monolog\Formatter\FormatterInterface::class);
        $formatter->expects($this->any())->method('format')->will($this->returnCallback(function ($record) {
            return $record['message'];
        }));
        return $formatter;
    }
}
