<?php


namespace Booni3\DynamicWms\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class ApiClient
{
    /** @var Client  */
    private $client;

    /** @var string */
    private $bearer;

    /** @var string */
    private $server;

    public function __construct(Client $client, string $bearer = null)
    {
        $this->client = $client;
        $this->bearer = $bearer;
    }

    public function get($url = null, array $parameters = []): array
    {
        return $this->parse(function() use($url, $parameters){
            return $this->client->get($this->server.$url, [
                'form_params' => $parameters,
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => $this->bearer
                ]
            ]);
        });
    }

    public function post($url = null, array $parameters = []): array
    {
        return $this->parse(function() use($url, $parameters){
            return $this->client->post($this->server.$url, [
                'form_params' => $parameters,
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => $this->bearer ?? ''
                ]
            ]);
        });
    }

    private function parse(callable $callback)
    {
        $response = call_user_func($callback);

        $json = json_decode((string) $response->getBody(), true);

        if(json_last_error() !== JSON_ERROR_NONE){
            throw new BadResponseException((string) $response->getBody());
        }

        return $json;
    }

}
