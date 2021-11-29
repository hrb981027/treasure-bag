<?php

declare(strict_types=1);

if (!function_exists('cliOutput')) {
    /**
     * 控制台格式化输出json，系统需安装 jq 命令
     * @param $data
     */
    function cliOutput($data)
    {
        if (is_object($data)) {
            $data = $data->toArray();
        } else if (!is_array($data)) {
            $data = [
                'result' => $data
            ];
        }

        $data = json_encode($data, JSON_UNESCAPED_UNICODE);

        system("echo '" . $data . "' | jq .");
    }
}

if (!function_exists('generateUUID')) {
    /**
     * 生成 UUID
     * @return string
     */
    function generateUUID(): string
    {
        $chars = md5(uniqid((string)mt_rand(), true));

        return substr($chars, 0, 8) . '-'
            . substr($chars, 8, 4) . '-'
            . substr($chars, 12, 4) . '-'
            . substr($chars, 16, 4) . '-'
            . substr($chars, 20, 12);
    }
}

if (!function_exists('arrayPick')) {
    /**
     * 保留一维数组指定键名
     * @param array $array
     * @param array $keys
     * @return array
     */
    function arrayPick(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }
}

if (!function_exists('camelize')) {
    /**
     * 文本下划线转小驼峰
     * @param string $uncamelizedWords
     * @return string
     */
    function camelize(string $uncamelizedWords): string
    {
        $separator = '_';

        $uncamelizedWords = $separator . str_replace($separator, " ", strtolower($uncamelizedWords));

        return ltrim(str_replace(" ", "", ucwords($uncamelizedWords)), $separator);
    }
}

if (!function_exists('unCamelize')) {
    /**
     * 文本小驼峰转下划线
     * @param string $camelCaps
     * @return string
     */
    function unCamelize(string $camelCaps): string
    {
        $separator = '_';

        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }
}

if (!function_exists('arrayEval')) {
    /**
     * 解析数组中的变量
     * @param array $array
     * @param array $var
     * @return array
     */
    function arrayEval(array $array, array $var): array
    {
        foreach ($var as $key => $value) {
            $$key = $value;
        }

        $result = [];

        foreach ($array as $key => $value) {
            $key = is_string($key) ? addslashes($key) : $key;
            eval('$key = "' . $key . '";');

            if (is_array($value)) {
                $value = arrayEval($value, $var);
            } else {
                $value = is_string($value) ? addslashes($value) : $value;
                eval('$value = "' . $value . '";');
            }

            $result[$key] = $value;
        }

        return $result;
    }
}

if (!function_exists('stringEval')) {
    /**
     * 解析文本中的变量
     * @param string $string
     * @param array $var
     * @return string
     */
    function stringEval(string $string, array $var): string
    {
        foreach ($var as $key => $value) {
            $$key = $value;
        }

        eval('$string = "' . addslashes($string) . '";');

        return $string;
    }
}

if (!function_exists('isBase64')) {
    /**
     * 判断是否是base64编码
     * @param string|int $content
     * @return bool
     */
    function isBase64(string|int $content): bool
    {
        if (@PReg_match('/^[0-9]*$/', $content) || @preg_match('/^[a-zA-Z]*$/', $content)) {
            return false;
        } elseif (isUtf8(base64_decode($content)) && base64_decode($content) != '') {
            return true;
        }
        return false;
    }
}

if (!function_exists('isUtf8')) {
    /**
     * 判断是否是utf8编码
     * @param string|int $content
     * @return bool
     */
    function isUtf8(string|int $content): bool
    {
        $len = strlen($content);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($content[$i]);
            if ($c > 128) {
                if (($c > 247)) {
                    return false;
                } elseif ($c > 239) {
                    $bytes = 4;
                } elseif ($c > 223) {
                    $bytes = 3;
                } elseif ($c > 191) {
                    $bytes = 2;
                } else {
                    return false;
                }
                if (($i + $bytes) > $len) {
                    return false;
                }
                while ($bytes > 1) {
                    $i++;
                    $b = ord($content[$i]);
                    if ($b < 128 || $b > 191) {
                        return false;
                    }
                    $bytes--;
                }
            }
        }
        return true;
    }
}

if (!function_exists('imgUrlToBase64')) {
    /**
     * 图片URL转Base64
     * @param string $url
     * @return string
     */
    function imgUrlToBase64(string $url): string
    {
        $imgInfo = getimagesize($url);
        return 'data:' . $imgInfo['mime'] . ';base64,' . base64_encode(file_get_contents($url));
    }
}
