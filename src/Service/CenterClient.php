<?php

declare(strict_types=1);

namespace TreasureBag\Service;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\Utils\Str;
use TreasureBag\Annotation\Secure;
use TreasureBag\Annotation\Subscribe;
use TreasureBag\Exception\InvalidCenterConnectionException;

class CenterClient
{
    protected DispatcherFactory $dispatcherFactory;

    protected GuzzleHttpClient $client;

    protected ConfigInterface $configInterface;

    protected array $config = [
        'service_center_host' => 'http://localhost',
        'service' => [
            'name' => '',
            'description' => '',
            'path' => [],
            'publish' => [['topic' => '', 'description' => '']]
        ],
        'service_hostname' => 'localhost',
        'service_port' => 9501,
    ];

    protected array $routeData = [];

    protected array $securityRule = [];

    protected array $subscribeList = [];

    public function __construct(DispatcherFactory $dispatcherFactory, ClientFactory $clientFactory, ConfigInterface $config)
    {
        $this->dispatcherFactory = $dispatcherFactory;
        $this->configInterface = $config;

        $this->config = array_replace_recursive($this->config, $this->configInterface->get('treasureBag') ?? []);

        $this->client = $clientFactory->create([
            'base_uri' => $this->config['service_center_host']
        ]);

        $this->initRouteData();
        $this->initSecurityRule();
        $this->initSubscribeList();
    }

    public function register()
    {
        try {
            $gateWay = [];

            foreach ($this->config['service']['path'] as $path) {
                $gateWay[] = [
                    'path' => $path,
                    'security' => [
                        'ignore_path' => $this->securityRule[$path]
                    ]
                ];
            }

            foreach ($this->config['service']['publish'] as $key => $publish) {
                if ($publish['topic'] == '') {
                    unset($this->config['service']['publish'][$key]);
                }
            }

            $postData = [
                'key_name' => $this->config['service']['name'],
                'description' => $this->config['service']['description'],
                'hostname' => $this->config['service_hostname'],
                'http_port' => $this->config['service_port'],
                'gateway' => $gateWay,
                'event' => [
                    'publish' => $this->config['service']['publish'],
                    'subscribe' => $this->subscribeList
                ]
            ];

            $response = $this->client->post('/service/register', [
                'json' => $postData
            ]);

            $responseContents = json_decode($response->getBody()->getContents(), true);

            if ($responseContents['code'] != 0) {
                throw new InvalidCenterConnectionException($responseContents['msg']);
            }

            $this->setGlobalConfig($responseContents['data']);
        } catch (GuzzleException $e) {
            throw new InvalidCenterConnectionException($e->getMessage());
        }
    }

    protected function setGlobalConfig(array $config = [])
    {
        $config = array_merge($this->configInterface->get('treasureBag'), $config);

        $this->configInterface->set('treasureBag', $config);
    }

    protected function initSecurityRule()
    {
        $collector = AnnotationCollector::list();

        foreach ($collector as $className => $metadata) {
            if (isset($metadata['_c']) && $this->hasSecurityAnnotation($metadata['_c'])) {
                $routePath = $this->getControllerRoutePath($this->routeData, $className, $metadata['_c']);

                if ($routePath == '') {
                    continue;
                }

                $routePath = $routePath . '/**';

                $securityAnnotation = $metadata['_c'][Secure::class];

                if ($securityAnnotation->path == '' || !in_array($securityAnnotation->path, $this->config['service']['path'])) {
                    $this->securityRule[$this->config['service']['path'][0]][] = $routePath;
                } else {
                    $this->securityRule[$securityAnnotation->path][] = $routePath;
                }
            } else {
                foreach ($metadata['_m'] ?? [] as $methodName => $_metadata) {
                    if (!$this->hasSecurityAnnotation($_metadata)) {
                        continue;
                    }

                    $routePath = $this->getMethodRoutePath($this->routeData, $className, $methodName);

                    if ($routePath == '') {
                        continue;
                    }

                    $securityAnnotation = $_metadata[Secure::class];

                    if ($securityAnnotation->path == '' || !in_array($securityAnnotation->path, $this->config['service']['path'])) {
                        $this->securityRule[$this->config['service']['path'][0]][] = $routePath;
                    } else {
                        $this->securityRule[$securityAnnotation->path][] = $routePath;
                    }
                }
            }
        }
    }

    protected function initSubscribeList()
    {
        $collector = AnnotationCollector::list();

        foreach ($collector as $className => $metadata) {
            foreach ($metadata['_m'] ?? [] as $methodName => $_metadata) {
                if (!$this->hasSubscribeAnnotation($_metadata)) {
                    continue;
                }

                $methodRoute = $this->getMethodRoute($this->routeData, $className, $methodName);

                $routePath = '';

                foreach ($methodRoute as $item) {
                    if ($item['request_method'] == 'POST') {
                        $routePath = $item['path'];

                        break;
                    }
                }

                if ($routePath == '') {
                    continue;
                }

                $subscribeAnnotation = $_metadata[Subscribe::class];

                foreach ($subscribeAnnotation->topic as $item) {
                    $this->subscribeList[] = [
                        'topic' => $item,
                        'receive_uri' => $routePath
                    ];
                }
            }
        }
    }

    protected function initRouteData()
    {
        $sourceData = $this->dispatcherFactory->getRouter('http')->getData()[0];

        $data = [];
        foreach ($sourceData as $requestMethod => $item) {
            foreach ($item as $path => $value) {
                if (is_object($value->callback)) {
                    continue;
                }

                if (is_string($value->callback)) {
                    if (strstr($value->callback, '::') !== false) {
                        $value->callback = explode('::', $value->callback);
                    } elseif (strstr($value->callback, '@') !== false) {
                        $value->callback = explode('@', $value->callback);
                    }
                }

                $data[$requestMethod][] = [
                    'controller' => $value->callback['0'],
                    'method' => $value->callback['1'],
                    'path' => $path
                ];
            }
        }

        $result = [];
        foreach ($data as $requestMethod => $item) {
            foreach ($item as $value) {
                $result[$value['controller']][$value['method']][] = [
                    'request_method' => $requestMethod,
                    'path' => $value['path']
                ];
            }
        }

        $this->routeData = $result;
    }

    protected function hasSecurityAnnotation(array $item): bool
    {
        return isset($item[Secure::class]);
    }

    protected function hasSubscribeAnnotation(array $item): bool
    {
        return isset($item[Subscribe::class]);
    }

    protected function getControllerRoutePath(array $routeData, string $className, array $item): string
    {
        if (!isset($routeData[$className])) {
            return '';
        }

        $routePath = '';

        if (isset($item[AutoController::class]) || isset($item[Controller::class])) {
            $annotation = $item[AutoController::class] ?? $item[Controller::class];

            $routePath = $annotation->prefix;
        }

        if (empty($routePath)) {
            $handledNamespace = Str::replaceFirst('Controller', '', Str::after($className, '\\Controller\\'));
            $handledNamespace = str_replace('\\', '/', $handledNamespace);
            $routePath = Str::snake($handledNamespace);
            $routePath = str_replace('/_', '/', $routePath);
        }

        if ($routePath[0] !== '/') {
            $routePath = '/' . $routePath;
        }

        return $routePath;
    }

    protected function getMethodRoute(array $routeData, string $className, string $methodName): array
    {
        if (!isset($routeData[$className]) || !isset($routeData[$className][$methodName])) {
            return [];
        }

        return $routeData[$className][$methodName];
    }

    protected function getMethodRoutePath(array $routeData, string $className, string $methodName): string
    {
        $methodRoute = $this->getMethodRoute($routeData, $className, $methodName);

        if (empty($methodRoute)) {
            return '';
        }

        return $methodRoute[0]['path'];
    }
}