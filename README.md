# TreasureBag

百宝箱，内容包含常用的辅助函数、类库、服务中心 SDK

## 安装

```bash
composer require hrb981027/treasure-bag
```

## 辅助函数、类库

### Parental 库

Fork 的 [calebporzio/parental](https://github.com/calebporzio/parental) 包，并改为 `Hyperf` 框架专用版本

### 辅助函数

- `cliOutput` _控制台格式化输出 json，系统需安装 jq 命令_
- `generateUUID` _生成 UUID_
- `arrayPick` _保留一维数组指定键名_
- `camelize` _文本下划线转小驼峰_
- `unCamelize` _文本小驼峰转下划线_
- `arrayEval` _解析数组中的变量_
- `stringEval` _解析文本中的变量_
- `isBase64` _判断是否是base64编码_
- `isUtf8` _判断是否是utf8编码_

## 服务中心 SDK

### 配置

配置文件位于`config/autoload/treasureBag.php`，如文件不存在可以使用如下命令快速生成配置文件

```shell
php bin/hyperf.php vendor:publish hrb981027/treasure-bag
```

| 配置项                      | 类型   | 默认值             | 备注                 |
| --------------------------- | ------ | ------------------ | -------------------- |
| service_center_host         | string | 'http://localhost' | 服务中心地址         |
| service.name                | string | ''                 | 当前服务名称         |
| service.description         | string | ''                 | 当前服务描述         |
| service.path                | array  | []                 | 当前服务网关匹配路径 |
| service.publish.topic       | string | ''                 | 当前服务发布事件主题 |
| service.publish.description | string | ''                 | 当前服务发布事件描述 |
| service_hostname            | string | 'localhost'        | 当前服务主机名       |
| service_port                | int    | 9501               | 当前服务端口         |

```php
<?php

declare(strict_types=1);

return [
    'service_center_host' => env('TREASURE_BAG_SERVICE_CENTER_HOST', 'http://localhost'),
    'service' => ['name' => '', 'description' => '', 'path' => [], 'publish' => [['topic' => '', 'description' => '']]],
    'service_hostname' => env('TREASURE_BAG_SERVICE_HOSTNAME', 'localhost'),
    'service_port' => (int)env('TREASURE_BAG_SERVICE_PORT', 9501),
];
```

### 使用

#### 服务中心

##### 配置

在配置文件中`server`一项，可使用预设的服务，示例如下

```php
<?php

declare(strict_types=1);

return [
    'service_center_host' => env('TREASURE_BAG_SERVICE_CENTER_HOST', 'http://localhost'),
    'service' => \Hrb981027\TreasureBag\Service\PresetService\PodsService::MEMBER_SERVICE,
    'service_hostname' => env('TREASURE_BAG_SERVICE_HOSTNAME', 'localhost'),
    'service_port' => env('TREASURE_BAG_SERVICE_PORT', 9501),
];
```

##### 鉴权放行路径

目前只能通过`注解`的形式标注无需鉴权路径，带有该注解时，类或方法必须要有对应路由（config/routes.php 配置同样生效），否则无效。示例如下

- 类

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hrb981027\TreasureBag\Annotation\Secure;

/**
 * @Secure()
 * @Controller()
 */
class ApiController extends AbstractController
{
    /**
     * @RequestMapping(path="test", methods={"GET"})
     */
    public function test()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }
}
```

如果注解标注在控制器类上，对应的鉴权放行路径为：`/api/**`，如果控制器注解带有 prefix 参数则也生效

- 方法

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hrb981027\TreasureBag\Annotation\Secure;

/**
 * @Controller()
 */
class ApiController extends AbstractController
{
    /**
     * @Secure()
     * @RequestMapping(path="test", methods={"GET"})
     */
    public function test()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }
}
```

如果注解标注在控制器方法上，对应的鉴权放行路径为：`/api/test`，如果类和方法同时带有`@Secure`注解，则**只有类注解生效**

如果服务有多个网关匹配路径，则可以通过`@Secure(path="")`参数设置该放行路径属于哪个匹配路径，示例如下

```php
return [
    ...
    'service' => [
        ...
        'path' => [
            '/test1', '/test2'
        ]
        ...
    ],
    ...
];

/**
 * @Secure(path="/test2")
 */
```

> 如果 path 参数为空，则默认为配置文件中 path[0]

##### 注册服务

建议在框架启动时进行注册服务，示例如下

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\OnStart;
use Hrb981027\TreasureBag\Service\CenterClient;

/**
 * @Listener()
 */
class OnStartListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            OnStart::class,
        ];
    }

    public function process(object $event)
    {
        if ($event instanceof OnStart) {
           make(CenterClient::class)->register();
        }
    }
}
```

#### 服务发现

##### 配置

服务路由器的地址由服务注册完成后自动设置，所以无需任何手动配置

##### 请求

目标服务可使用预设的服务，示例如下

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hrb981027\TreasureBag\Service\PresetService\PodsService;
use Hrb981027\TreasureBag\Service\RouterClient;

/**
 * @Controller()
 */
class ApiController extends AbstractController
{
    /**
     * @Inject()
     * @var RouterClient
     */
    protected RouterClient $routerClient;

    /**
     * @RequestMapping(path="test", methods={"GET"})
     */
    public function test()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        $this->routerClient->get(PodsService::MEMBER_SERVICE, '/hello-world', [
            'query' => [
                'hello' => 'world'
            ]
        ]);

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }
}
```

#### 发布事件

##### 配置

事件总线的地址由服务注册完成后自动设置，所以无需任何手动配置

##### 发布

事件名称可使用预设的枚举类，示例如下

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hrb981027\TreasureBag\PresetEvent\PodsEvent;
use Hrb981027\TreasureBag\Service\EventClient;

/**
 * @Controller()
 */
class ApiController extends AbstractController
{
    /**
     * @Inject()
     * @var EventClient
     */
    protected EventClient $eventClient;

    /**
     * @RequestMapping(path="test", methods={"GET"})
     */
    public function test()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        $this->eventClient->publish(PodsEvent::MEMBER_CREATED, [
            'hello' => 'world'
        ]);

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }
}
```

##### 订阅

目前只能通过`注解`的形式标注订阅事件的接收 URL，带有该注解时，方法必须要有对应路由且请求方式必须含有`POST`（config/routes.php 配置同样生效），否则无效。通过设置 topic 参数设置需要订阅的事件`（可多个）`，事件名称可使用预设的枚举类，示例如下

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hrb981027\TreasureBag\Annotation\Subscribe;
use Hrb981027\TreasureBag\PresetEvent\PodsEvent;

/**
 * @Controller()
 */
class ApiController extends AbstractController
{
    /**
     * @Subscribe(topic={PodsEvent::MEMBER_CREATED})
     * @RequestMapping(path="test", methods={"POST"})
     */
    public function test()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }
}
```

## 枚举类

`Hrb981027\TreasureBag\Lib\Enum\Enum` 类提供了 `toArray` 和 `inArray` 方法，如需使用该方法，可继承此类

## 标准响应

响应代码值枚举类放置于 `Hrb981027\TreasureBag\Lib\Enum\ResponseCode`

标准响应体类放置于 `Hrb981027\TreasureBag\Lib\ResponseContent\StandardResponseContent`，示例如下

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Hrb981027\TreasureBag\Lib\Enum\ResponseCode;
use Hrb981027\TreasureBag\Lib\ResponseContent\StandardResponseContent;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

/**
 * @Controller()
 */
class ApiController extends AbstractController
{
    /**
     * @RequestMapping(path="test", methods={"GET"})
     */
    public function test()
    {
        $standardResponseContent = new StandardResponseContent();

        $standardResponseContent->setCode(ResponseCode::SUCCESS);
        $standardResponseContent->setMessage('成功');
        $standardResponseContent->setData([
            'hello' => 'world'
        ]);

        return $this->response->json($standardResponseContent->toArray());
    }
}
```

## 标准异常处理

配置文件位于 `config/autoload/exceptions.php` 将 `\Hrb981027\TreasureBag\Exception\Handler\StandardExceptionHandler::class` 配置在对应的 `server` 下即可，示例如下

```php
<?php

declare(strict_types=1);

return [
    'handler' => [
        'http' => [
            \Hrb981027\TreasureBag\Exception\Handler\StandardExceptionHandler::class,
            Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler::class,
            App\Exception\Handler\AppExceptionHandler::class,
        ],
    ],
];
```

## 参数类

继承 `Hrb981027\TreasureBag\Lib\Param\AbstractParam` 类可实现：构造函数数组递归赋值、自动校验属性类型和必填性、转数组，继承该类的同时也必须携带相关 `注解`，赋值时数组键名和属性名对应关系为`snake风格 —> 小驼峰风格`，转数组时为 `小驼峰风格 —> snake风格`

### 普通用法

继承 `AbstractParam` 类并携带相关 `注解`，示例如下

```php
<?php

declare(strict_types=1);

namespace App\Param\Api\Test;

use Hrb981027\TreasureBag\Annotation\Param;
use Hrb981027\TreasureBag\Annotation\ParamProperty;
use Hrb981027\TreasureBag\Lib\Param\AbstractParam;

/**
 * @Param()
 */
class Data extends AbstractParam
{
    /**
     * @ParamProperty()
     */
    public int $data1;

    /**
     * @ParamProperty()
     */
    public string $data2;
}
```

使用如下

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Param\Api\Test\Data as TestData;
use Hrb981027\TreasureBag\Lib\Enum\ResponseCode;
use Hrb981027\TreasureBag\Lib\ResponseContent\StandardResponseContent;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Psr\Http\Message\ResponseInterface;

/**
 * @Controller()
 */
class ApiController extends AbstractController
{
    /**
     * @RequestMapping(path="test", methods={"POST"})
     */
    public function test(): ResponseInterface
    {
        $data = new TestData([
            'data1' => 1,
            'data2' => "1"
        ]);

        $standardResponseContent = new StandardResponseContent();

        $standardResponseContent->setCode(ResponseCode::SUCCESS);
        $standardResponseContent->setMessage('成功');
        $standardResponseContent->setData($data->toArray());

        return $this->response->json($standardResponseContent->toArray());
    }
}
```

### 数组内类型定义

如果需要定义一个整型的集合（数组），通过 `@var int[]` 定义，示例如下

```php
<?php

declare(strict_types=1);

namespace App\Param\Api\Test;

use Hrb981027\TreasureBag\Annotation\Param;
use Hrb981027\TreasureBag\Annotation\ParamProperty;
use Hrb981027\TreasureBag\Lib\Param\AbstractParam;

/**
 * @Param()
 */
class Data extends AbstractParam
{
    /**
     * @ParamProperty()
     * @var int[]
     */
    public array $data1;

    /**
     * @ParamProperty()
     */
    public string $data2;
}
```

### 修改赋值或转数组时数组键名对应属性名的默认处理函数

赋值时数组键名和属性名对应关系为`snake风格 —> 小驼峰风格`，转数组时为 `小驼峰风格 —> snake风格`，可以通过 `@Param` 的 `inHandle` 和 `outHandle` 参数来修改默认的处理函数

```php
<?php

declare(strict_types=1);

namespace App\Param\Api\Test;

use Hrb981027\TreasureBag\Annotation\Param;
use Hrb981027\TreasureBag\Annotation\ParamProperty;
use Hrb981027\TreasureBag\Lib\Param\AbstractParam;

/**
 * @Param(inHandle="camelize")
 */
class Data extends AbstractParam
{
    /**
     * @ParamProperty()
     */
    public array $data1;

    /**
     * @ParamProperty()
     */
    public string $data2;
}
```

### 单个属性赋值或转数组时，键名单独对应

可能会出现个别奇葩的属性在赋值或者转数组时，键名的转换并不是和其他属性的处理模式一样，这时可以通过 `@ParamProperty` 的 `in` 和 `out` 参数来单独定义该属性对应数组的键名，此处只能传入固定值，示例如下
```php
<?php

declare(strict_types=1);

namespace App\Param\Api\Test;

use Hrb981027\TreasureBag\Annotation\Param;
use Hrb981027\TreasureBag\Annotation\ParamProperty;
use Hrb981027\TreasureBag\Lib\Param\AbstractParam;

/**
 * @Param()
 */
class Data extends AbstractParam
{
    /**
     * @ParamProperty(in="test1", out="test2")
     */
    public int $data1;

    /**
     * @ParamProperty()
     */
    public string $data2;
}
```

使用如下

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Param\Api\Test\Data as TestData;
use Hrb981027\TreasureBag\Lib\Enum\ResponseCode;
use Hrb981027\TreasureBag\Lib\ResponseContent\StandardResponseContent;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Psr\Http\Message\ResponseInterface;

/**
 * @Controller()
 */
class ApiController extends AbstractController
{
    /**
     * @RequestMapping(path="test", methods={"POST"})
     */
    public function test(): ResponseInterface
    {
        $data = new TestData([
            'test1' => 1,
            'data2' => "1"
        ]);

        $standardResponseContent = new StandardResponseContent();

        $standardResponseContent->setCode(ResponseCode::SUCCESS);
        $standardResponseContent->setMessage('成功');
        $standardResponseContent->setData($data->toArray());

        return $this->response->json($standardResponseContent->toArray());
    }
}
```

### 对参数进行必填和不能为空进行校验

通过设置 `@ParamProperty` 的 `required` 和 `filled` 为 `true` 来开启对该参数的校验，开启 `required` 后该参数必填（可为空），开启 `filled` 后该参数必填且不能为空（使用 `empty` 函数校验）
```php
<?php

declare(strict_types=1);

namespace App\Param\Api\Test;

use Hrb981027\TreasureBag\Annotation\Param;
use Hrb981027\TreasureBag\Annotation\ParamProperty;
use Hrb981027\TreasureBag\Lib\Param\AbstractParam;

/**
 * @Param()
 */
class Data extends AbstractParam
{
    /**
     * @ParamProperty()
     */
    public array $data1;

    /**
     * @ParamProperty(required=true, filled=true)
     */
    public string $data2;
}
```

### 属性类型为另一个参数类

属性类型不仅可以为 php 基础数据类型，也可以是另一个参数类（也必须继承 `AbstractParam`，且携带对应的 `注解`），数组内的类型定义必须为参数类的全命名空间，使用 `use` 无效，示例如下

```php
<?php

declare(strict_types=1);

namespace App\Param\Api\Test;

use App\Param\User\Add\Def;
use Hrb981027\TreasureBag\Annotation\Param;
use Hrb981027\TreasureBag\Annotation\ParamProperty;
use Hrb981027\TreasureBag\Lib\Param\AbstractParam;

/**
 * @Param()
 */
class Data extends AbstractParam
{
    /**
     * @ParamProperty()
     * @var \App\Param\User\Add\Def[]
     */
    public array $data1;

    /**
     * @ParamProperty()
     */
    public Def $data2;
}
```

```php
<?php

declare(strict_types=1);

namespace App\Param\User\Add;

use Hrb981027\TreasureBag\Annotation\Param;
use Hrb981027\TreasureBag\Annotation\ParamProperty;
use Hrb981027\TreasureBag\Lib\Param\AbstractParam;

/**
 * @Param()
 */
class Def extends AbstractParam
{
    /**
     * @ParamProperty()
     */
    public float $def1;
}
```

使用如下

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Param\Api\Test\Data as TestData;
use Hrb981027\TreasureBag\Lib\Enum\ResponseCode;
use Hrb981027\TreasureBag\Lib\ResponseContent\StandardResponseContent;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Psr\Http\Message\ResponseInterface;

/**
 * @Controller()
 */
class ApiController extends AbstractController
{
    /**
     * @RequestMapping(path="test", methods={"POST"})
     */
    public function test(): ResponseInterface
    {
        $data = new TestData([
            'data1' => [
                [
                    'def1' => 2.1
                ],
                [
                    'def1' => 6.6
                ]
            ],
            'data2' => [
                'def1' => 1.5
            ]
        ]);

        $standardResponseContent = new StandardResponseContent();

        $standardResponseContent->setCode(ResponseCode::SUCCESS);
        $standardResponseContent->setMessage('成功');
        $standardResponseContent->setData($data->toArray());

        return $this->response->json($standardResponseContent->toArray());
    }
}
```

### 个别属性不想被外部赋值或转数组时输出

通过设置 `@ParamProperty` 的 `allowIn` 和 `allowOut` 参数来开关是否允许输入输出，示例如下
```php
<?php

declare(strict_types=1);

namespace App\Param\Api\Test;

use Hrb981027\TreasureBag\Annotation\Param;
use Hrb981027\TreasureBag\Annotation\ParamProperty;
use Hrb981027\TreasureBag\Lib\Param\AbstractParam;

/**
 * @Param()
 */
class Data extends AbstractParam
{
    /**
     * @ParamProperty()
     */
    public array $data1;

    /**
     * @ParamProperty(allowIn=false)
     */
    public int $data2 = 66;

    /**
     * @ParamProperty(allowOut=false)
     */
    public string $data3;
}
```
