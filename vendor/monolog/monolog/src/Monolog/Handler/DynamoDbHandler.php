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

use Mailster\Aws\Sdk;
use Mailster\Aws\DynamoDb\DynamoDbClient;
use Mailster\Monolog\Formatter\FormatterInterface;
use Mailster\Aws\DynamoDb\Marshaler;
use Mailster\Monolog\Formatter\ScalarFormatter;
use Mailster\Monolog\Logger;
/**
 * Amazon DynamoDB handler (http://aws.amazon.com/dynamodb/)
 *
 * @link https://github.com/aws/aws-sdk-php/
 * @author Andrew Lawson <adlawson@gmail.com>
 */
class DynamoDbHandler extends \Mailster\Monolog\Handler\AbstractProcessingHandler
{
    public const DATE_FORMAT = 'Y-m-d\\TH:i:s.uO';
    /**
     * @var DynamoDbClient
     */
    protected $client;
    /**
     * @var string
     */
    protected $table;
    /**
     * @var int
     */
    protected $version;
    /**
     * @var Marshaler
     */
    protected $marshaler;
    /**
     * @param int|string $level
     */
    public function __construct(\Mailster\Aws\DynamoDb\DynamoDbClient $client, string $table, $level = \Mailster\Monolog\Logger::DEBUG, bool $bubble = \true)
    {
        if (\defined('Aws\\Sdk::VERSION') && \version_compare(\Mailster\Aws\Sdk::VERSION, '3.0', '>=')) {
            $this->version = 3;
            $this->marshaler = new \Mailster\Aws\DynamoDb\Marshaler();
        } else {
            $this->version = 2;
        }
        $this->client = $client;
        $this->table = $table;
        parent::__construct($level, $bubble);
    }
    /**
     * {@inheritdoc}
     */
    protected function write(array $record) : void
    {
        $filtered = $this->filterEmptyFields($record['formatted']);
        if ($this->version === 3) {
            $formatted = $this->marshaler->marshalItem($filtered);
        } else {
            $formatted = $this->client->formatAttributes($filtered);
        }
        $this->client->putItem(['TableName' => $this->table, 'Item' => $formatted]);
    }
    protected function filterEmptyFields(array $record) : array
    {
        return \array_filter($record, function ($value) {
            return !empty($value) || \false === $value || 0 === $value;
        });
    }
    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormatter() : \Mailster\Monolog\Formatter\FormatterInterface
    {
        return new \Mailster\Monolog\Formatter\ScalarFormatter(self::DATE_FORMAT);
    }
}
