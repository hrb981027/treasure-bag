<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Service;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory;
use Hrb981027\TreasureBag\Exception\InvalidEventBusConnectionException;

class EventClient
{
    protected GuzzleHttpClient $client;

    protected ConfigInterface $configInterface;

    protected array $config = [
        'event_bus_url' => 'http://localhost'
    ];

    public function __construct(ClientFactory $clientFactory, ConfigInterface $config)
    {
        $this->configInterface = $config;

        $this->config = array_replace_recursive($this->config, $this->configInterface->get('treasureBag') ?? []);

        $this->client = $clientFactory->create([
            'base_uri' => $this->config['event_bus_url']
        ]);
    }

    public function publish(string $topic, array $body, bool $async = false)
    {
        try {
            $response = $this->client->post('/event/publish', [
                'query' => [
                    'topic' => $topic,
                    'async' => $async ? 'true' : 'false'
                ],
                'json' => $body
            ]);

            $responseContents = json_decode($response->getBody()->getContents(), true);

            if ($responseContents['code'] != 0) {
                throw new InvalidEventBusConnectionException($responseContents['msg']);
            }
        } catch (GuzzleException $e) {
            throw new InvalidEventBusConnectionException($e->getMessage());
        }
    }
}