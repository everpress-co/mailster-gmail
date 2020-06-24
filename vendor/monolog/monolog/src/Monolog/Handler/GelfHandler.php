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
namespace Mailster\Monolog\Handler;

use Mailster\Gelf\PublisherInterface;
use Mailster\Monolog\Logger;
use Mailster\Monolog\Formatter\GelfMessageFormatter;
use Mailster\Monolog\Formatter\FormatterInterface;
/**
 * Handler to send messages to a Graylog2 (http://www.graylog2.org) server
 *
 * @author Matt Lehner <mlehner@gmail.com>
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class GelfHandler extends \Mailster\Monolog\Handler\AbstractProcessingHandler
{
    /**
     * @var PublisherInterface|null the publisher object that sends the message to the server
     */
    protected $publisher;
    /**
     * @param PublisherInterface $publisher a publisher object
     * @param string|int         $level     The minimum logging level at which this handler will be triggered
     * @param bool               $bubble    Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(\Mailster\Gelf\PublisherInterface $publisher, $level = \Mailster\Monolog\Logger::DEBUG, bool $bubble = \true)
    {
        parent::__construct($level, $bubble);
        $this->publisher = $publisher;
    }
    /**
     * {@inheritdoc}
     */
    protected function write(array $record) : void
    {
        $this->publisher->publish($record['formatted']);
    }
    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter() : \Mailster\Monolog\Formatter\FormatterInterface
    {
        return new \Mailster\Monolog\Formatter\GelfMessageFormatter();
    }
}
