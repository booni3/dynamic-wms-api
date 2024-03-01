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

    public function __construct(Client $client, string $bearer = null)
    {
        $this->client = $client;
        $this->bearer = $bearer;
    }

    public function get($url = null, array $parameters = []): array
    {
        return $this->parse(function() use($url, $parameters){
            return $this->client->get($url, [
                'query' => $parameters,
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$this->bearer
                ]
            ]);
        });
    }

    public function post($url = null, array $parameters = []): array
    {
        return $this->parse(function() use($url, $parameters){
            return $this->client->post($url, [
                'form_params' => $parameters,
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$this->bearer
                ]
            ]);
        });
    }

    public function put($url = null, array $parameters = []): array
    {
        return $this->parse(function() use($url, $parameters){
            return $this->client->put($url, [
                'form_params' => $parameters,
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$this->bearer
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
