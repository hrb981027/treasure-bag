<?php

declare(strict_types=1);

namespace TreasureBag\Service;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory;
use Psr\Http\Message\ResponseInterface;

class RouterClient
{
    protected GuzzleHttpClient $client;

    protected array $config = [
        'service_router_host' => 'http://localhost'
    ];

    public function __construct(ClientFactory $clientFactory, ConfigInterface $config)
    {
        $this->config = array_replace_recursive($this->config, $config->get('treasureBag') ?? []);

        $this->client = $clientFactory->create([
            'base_uri' => $this->config['service_router_host']
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function get(array $service, string $url, array $options = []): ResponseInterface
    {
        return $this->request($service, 'GET', $url, $options);
    }

    /**
     * @throws GuzzleException
     */
    public function post(array $service, string $url, array $options = []): ResponseInterface
    {
        return $this->request($service, 'POST', $url, $options);
    }

    /**
     * @throws GuzzleException
     */
    public function request(array $service, string $method, string $url, array $options = []): ResponseInterface
    {
        if (isset($service['name']) && !empty($service['name'])) {
            $url = '/' . $service['name'] . $url;
        }

        return $this->client->request($method, $url, $options);
    }
}
