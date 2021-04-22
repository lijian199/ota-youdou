<?php

namespace Cncn\Youdou\Api;


use Cncn\Ctrip\Common\HttpClient;
use Cncn\Ctrip\Entity\EntityInterface;
use http\Client;

class CreateOrder
{

    public static function test(){
        print_r('AAAAA');exit;
        $client = new HttpClient();
        $result=$client->post('http://www.baidu.com',[]);
        var_dump($result);die;
    }

    /**
     * @param array $entity
     * @param callable|null $callback
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function notify($entity = array(), callable $callback = null)
    {

        if (!($entity instanceof EntityInterface)) {
            return false;
        }
        $body = array(
            'sequenceId' => $entity->sequenceId,
            'otaOrderId' => $entity->otaOrderId,
            'supplierOrderId' => $entity->supplierOrderId,
            'confirmResultCode' => $entity->confirmResultCode,
            'confirmResultMessage' => $entity->confirmResultMessage,
            'voucherSender' => $entity->voucherSender,
            'vouchers' => $entity->vouchers,
            'items' => $entity->items,
        );
        $this->getLogger()->log("新单通知参数", $body);
        $bodyStr = $this->encrypt($body);
        $header = $this->initNotifyHeader('CreateOrderConfirm', $bodyStr);
        $this->getLogger()->log("新单通知参数-加密", $bodyStr);
        $requestParam = array(
            'header' => array(
                'accountId' => $header->getAccountId(),
                'serviceName' => $header->getServiceName(),
                'requestTime' => $header->getRequestTime(),
                'version' => $header->getVersion(),
                'sign' => $header->getSign(),
            ),
            'body' => $bodyStr
        );
        try {
            $client = new HttpClient($this->config);
            $res = $client->post($this->config['base_uri'], $requestParam);
            $jsonObj = json_decode($res->getBody()->getContents(), true);
            return $jsonObj;
        } catch (\Exception $ex) {
            return false;
        }

    }


}