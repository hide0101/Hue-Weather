<?php
/**
 * Phue: Philips Hue PHP Client
 *
 * @author    Michael Squires <sqmk@php.net>
 * @copyright Copyright (c) 2012 Michael K. Squires
 * @license   http://github.com/sqmk/Phue/wiki/License
 */

namespace Phue\Command;

use Phue\Client;
use Phue\Transport\TransportInterface;

/**
 * Set group action command
 */
class SetGroupState extends SetLightState implements SchedulableInterface
{
    /**
     * Group Id
     *
     * @var string
     */
    protected $groupId;

    /**
     * Constructs a command
     *
     * @param mixed $group Group Id or Group object
     */
    public function __construct($group)
    {
        $this->groupId = (string) $group;
    }

    /**
     * Send command
     *
     * @param Client $client Phue Client
     */
    public function send(Client $client)
    {
        // Get params
        $params = $this->getSchedulableParams($client);

        // Send request
        $client->getTransport()->sendRequest(
            $params['address'],
            $params['method'],
            $params['body']
        );
    }

    /**
     * Get schedulable params
     *
     * @param Client $client Phue Client
     *
     * @return array Key/value pairs of params
     */
    public function getSchedulableParams(Client $client)
    {
        return [
            'address' => "{$client->getUsername()}/groups/{$this->groupId}/action",
            'method'  => TransportInterface::METHOD_PUT,
            'body'    => (object) $this->params
        ];
    }
}
