<?php

namespace Cncn\Youdou\tests;

use CloudS\Hu\Api\Http\Api;
use CloudS\Hu\Api\Http\Client;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * 携程接口测试
 *
 * Class CtripApiTest
 * @package Cncn\Meituan\tests
 */
class YoudouApiTest extends TestCase
{
    protected $config;

    /** @var \Cncn\Youdou\Client */
    protected $service;

    protected $method = 'POST';

    protected $cipher = 'AES-128-CBC';

    protected $requestTime;

    protected $version = '1.0';

    protected $PLU = 'ota_ctrip_jq_673';

    protected $sequenceId = '201909091113089dc547f7db584463b001';

    protected $otaOrderId = 'CTRIP-CLOUDS-TEST-001';

    protected $quantity;

    protected $traveller = [
        [
            "name" => "张三",
            "mobile" => "13779991180",
            "cardType" => "1",
            "cardNo" => "513436200009091365",
        ],
        [
            "name" => "李四",
            "mobile" => "13779991181",
            "cardType" => "1",
            "cardNo" => "513436200010111300",
        ],
        [
            "name" => "王五",
            "mobile" => "13779991182",
            "cardType" => "1",
            "cardNo" => "513436200010117243",
        ],
        [
            "name" => "赵六",
            "mobile" => "13779991183",
            "cardType" => "1",
            "cardNo" => "513436200010119767",
        ],
        [
            "name" => "陈七",
            "mobile" => "13779991184",
            "cardType" => "1",
            "cardNo" => "513436200010113525",
        ]
    ];

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();
        $this->config = [
            'api' => 'http://172.16.0.180:964',
            'aes_key' => 'a82f5d3cc44a03c7',
            'aes_iv' => '49be7811b44b50df',
            'sign_key' => 'e0898cbcbe19b7b124c6e1b1632d47a9',
            'account_id' => '9060fc94f50b79b8',
            'max_retries' => 3,
            'retry_interval' => 1000,
            'log_path' => __DIR__ . '/logs/',
            'base_uri' => 'https://ttdstp.ctrip.com/api/order/notice.do',
        ];
        $this->requestTime = date('Y-m-d H:i:s');
        $this->otaOrderId = $this->otaOrderId . date('Ymd');
        $this->sequenceId = $this->sequenceId . date('Ymd');
        $this->service = new \Cncn\Ctrip\Client($this->config);
        $this->quantity = count($this->traveller);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testOrderCreate()
    {
        $body = [
            "otaOrderId" => $this->otaOrderId,
            "sequenceId" => $this->sequenceId,
            "confirmType" => 2,
            "items" => [
                [
                    "passengers" => $this->traveller,
                    "cost" => 5,
                    "quantity" => $this->quantity,
                    "useEndDate" => date('Y-m-d', strtotime('+2 day')),
                    "useStartDate" => date('Y-m-d', strtotime('+1 day')),
                    "itemId" => "ctriptest-1082149-0",
                    "price" => 1,
                    "PLU" => $this->PLU,
                ]
            ],
            "contacts" => [
                [
                    "name" => "胡云南",
                    "mobile" => "13885552215",
                ]
            ]
        ];

        $params = [
            "body" => $body,
            "header" => [
                "accountId" => $this->config['account_id'],
                "requestTime" => $this->requestTime,
                "version" => $this->version,
                "serviceName" => "CreateOrder"
            ]
        ];
        $result = $this->request($params);
        echo "\r\n==========" . __METHOD__ . "=========\r\n";
        $result = $this->handleResponse($result);
        $this->assertTrue($result['header']['resultCode'] == "0000");
        return $result;
    }

    /**
     * 订单查询
     *
     * @depends testOrderCreate
     * @param $orderCreate
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testOrderQuery($orderCreate)
    {
        $body = [
            "supplierOrderId" => $orderCreate['body']['supplierOrderId'],
            "otaOrderId" => $orderCreate['body']['otaOrderId'],
            "sequenceId" => $this->sequenceId
        ];
        $params = [
            "body" => $body,
            "header" => [
                "accountId" => $this->config['account_id'],
                "requestTime" => $this->requestTime,
                "version" => $this->version,
                "serviceName" => "QueryOrder"
            ]
        ];
        $result = $this->request($params);
        echo "\r\n==========" . __METHOD__ . "=========\r\n";
        $result = $this->handleResponse($result);
        $this->assertTrue($result['header']['resultCode'] == "0000");
    }

    /**
     * 订单退款
     *
     * @depends testOrderCreate
     * @param $orderCreate
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testCancelOrder($orderCreate)
    {
        $body = [
            "supplierOrderId" => $orderCreate['body']['supplierOrderId'],
            "otaOrderId" => $orderCreate['body']['otaOrderId'],
            "sequenceId" => $this->sequenceId,
            "confirmType" => 2,
            "items" => [
                [
                    "itemId" => "ctriptest-1082158-0",
                    "lastConfirmTime" => date('Y-m-d H:i:s'),
                    "quantity" => $this->quantity,
                    "PLU" => $this->PLU
                ]
            ]
        ];
        $params = [
            "body" => $body,
            "header" => [
                "accountId" => $this->config['account_id'],
                "requestTime" => $this->requestTime,
                "version" => $this->version,
                "serviceName" => "CancelOrder"
            ]
        ];
        $result = $this->request($params);
        echo "\r\n==========" . __METHOD__ . "=========\r\n";
        $result = $this->handleResponse($result);
        $this->assertTrue($result['header']['resultCode'] == "0000");
    }

    /**
     * 接口请求
     *
     * @param $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function request($params)
    {
        $api = new Api();
        $body = $params['body'];
        $params['body'] = $this->encrypt($body);
        $params['header']['sign'] = $this->getSign($params);
        $api->setMethod($this->method)
            ->addParams($params);
        // 日志对象
        $logPath = './tests/logs/';
        if (!realpath($logPath)) {
            mkdir($logPath);
        }
        $stream = fopen($logPath . date('Y-m-d') . '.log', 'a+');
        $streamHandler = new StreamHandler($stream, Logger::DEBUG);
        $streamHandler->setFormatter(new LineFormatter(null, null, true, true));
        $logger = new Logger('api-http');
        $logger->pushHandler($streamHandler);
        $client = new Client($api, [
            'base_uri' => $this->config['api'] . '/ota/ctrip',
            'headers' => [],
            'timeout' => 30,
            'connect_timeout' => 3,
            'max_retries' => 3,
            'retry_interval' => 1000
        ], $logger);
        return $client->request();
    }

    /**
     * 响应结果处理
     *
     * @param $result
     * @return mixed
     */
    private function handleResponse($result)
    {
        if (isset($result['body'])) { // 解密
            $result['body'] = $this->decrypt($result['body']);
        }
        print_r($result);
        return $result;
    }

    /**
     * 获取签名
     *
     * @param $params
     * @return string
     */
    private function getSign($params)
    {
        $str = "{$this->config['account_id']}{$params['header']['serviceName']}{$params['header']['requestTime']}{$params['body']}{$this->version}{$this->config['sign_key']}";
        return strtolower(md5($str));
    }

    /**
     * 加密数据
     *
     * @param $data
     * @return string
     */
    private function encrypt($data)
    {
        $json = json_encode($data);
        $value = \openssl_encrypt(
            $json,
            $this->cipher,
            $this->config['aes_key'],
            1,
            $this->config['aes_iv']
        );
        $str = '';
        for ($i = 0; $i < strlen($value); $i++) {
            $tmpValue = ord($value[$i]);
            $ch = ($tmpValue >> 4 & 0xf) + ord('a');
            $str .= chr($ch);
            $ch = ($tmpValue & 0xf) + ord('a');
            $str .= chr($ch);
        }
        return $str;
    }

    /**
     * 解密数据
     *
     * @param $hex
     * @return mixed
     */
    private function decrypt($hex)
    {
        $str = '';
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $tmpValue = (((ord($hex[$i]) - ord('a')) & 0xf) << 4) + ((ord($hex[$i + 1]) - ord('a')) & 0xf);
            $str .= chr($tmpValue);
        }
        $decrypted = \openssl_decrypt(
            $str, $this->cipher, $this->config['aes_key'], 1, $this->config['aes_iv']
        );
        return @json_decode($decrypted, true);
    }
}