<?php

namespace Booni3\DynamicWms;

use Booni3\DynamicWms\Api\Deliveries;
use Booni3\DynamicWms\Api\Fulfilments;
use Booni3\DynamicWms\Api\Orders;
use GuzzleHttp\Client as GuzzleClient;

class DynamicWms
{
    const BASE_URI = 'https://v2.dynamicwms.app/api/v1/';

    /** @var GuzzleClient */
    protected $client;

    /** @var array */
    protected $config;

    /** @var string */
    protected $bearer;

    public function __construct(array $config, GuzzleClient $client = null)
    {
        $this->client = $client ?: $this->makeClient();
        $this->config = $config;
        $this->bearer = $config['bearer'];
    }

    public static function make(array $config, GuzzleClient $client = null): self
    {
        return new static ($config, $client);
    }

    private function makeClient(): GuzzleClient
    {
        return new GuzzleClient([
            'base_uri' => self::BASE_URI,
            'timeout' => $this->config['timeout'] ?? 15
        ]);
    }

    public function orders(): Orders
    {
        return new Orders($this->client, $this->bearer);
    }

    public function fulfilments(): Fulfilments
    {
        return new Fulfilments($this->client, $this->bearer);
    }

    public function deliveries(): Deliveries
    {
        return new Deliveries($this->client, $this->bearer);
    }
}
