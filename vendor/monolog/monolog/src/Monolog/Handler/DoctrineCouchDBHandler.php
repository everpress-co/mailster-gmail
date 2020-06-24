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

use Mailster\Monolog\Logger;
use Mailster\Monolog\Formatter\NormalizerFormatter;
use Mailster\Monolog\Formatter\FormatterInterface;
use Mailster\Doctrine\CouchDB\CouchDBClient;
/**
 * CouchDB handler for Doctrine CouchDB ODM
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class DoctrineCouchDBHandler extends \Mailster\Monolog\Handler\AbstractProcessingHandler
{
    private $client;
    public function __construct(\Mailster\Doctrine\CouchDB\CouchDBClient $client, $level = \Mailster\Monolog\Logger::DEBUG, bool $bubble = \true)
    {
        $this->client = $client;
        parent::__construct($level, $bubble);
    }
    /**
     * {@inheritDoc}
     */
    protected function write(array $record) : void
    {
        $this->client->postDocument($record['formatted']);
    }
    protected function getDefaultFormatter() : \Mailster\Monolog\Formatter\FormatterInterface
    {
        return new \Mailster\Monolog\Formatter\NormalizerFormatter();
    }
}
