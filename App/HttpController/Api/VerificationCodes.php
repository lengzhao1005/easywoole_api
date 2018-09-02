<?php

namespace App\HttpController\Api;

use App\Utility\FormatResultErrors;
use App\Utility\Redis;
use EasySwoole\Config;
use EasySwoole\Core\Component\Pool\PoolManager;
use EasySwoole\Core\Utility\Random;

class VerificationCodes extends AbstractBase
{
    //code保存时间/s
    const EXPIRED_SEC = 300;

    public function index()
    {
        if($verfy_result = $this->verificationMethod('POST') !== true){
            $this->returnJson($verfy_result);
        }

        $username = $this->request()->getRequestParam('username');

        filter_var($username, FILTER_VALIDATE_EMAIL) ?
            $email = $username :
            $phone = $username ;

        if(empty($email) && empty($phone)){
            return $this->returnJson(FormatResultErrors::CODE_MAP['USERNAME.NOTNULL']);
        }

        if (Config::getInstance()->getConf('ENV') === 'dev') {
            $code = '1234';
        } else {
            $code = '1234';
           /* // 生成4位随机数，左侧补0
            $code = str_pad(\mt_rand(1,9999),4,0,STREAM_BUFFER_LINE);

            try {
                $result = $easySms->send($phone, [
                    'content'  =>  "【Lbbs社区】您的验证码是{$code}。如非本人操作，请忽略本短信"
                ]);
            } catch (\GuzzleHttp\Exception\ClientException $exception) {
                $response = $exception->getResponse();
                $result = json_decode($response->getBody()->getContents(), true);
                return $this->response->errorInternal($result['msg'] ?? '短信发送异常');
            }*/
        }

        $key = 'verficationCode_'.Random::randStr(15);
        $hkey = 'verify:'.$key;
        $expiredAt = time() + self::EXPIRED_SEC;

        $redis = Redis::getInstance();
        $res1 = $redis->hSet($hkey, $username, $code);
        $res2 = $redis->exprieAt($hkey, $expiredAt);

        if(!$res1 || !$res2){
            return $this->returnJson(FormatResultErrors::CODE_MAP['SYS.ERR']);
        }

        return $this->returnJson(FormatResultErrors::CODE_MAP['SUCCESS'], [
            'verification_key'=>$key,
            'expired_at' => date('Y-m-d H:i:s', $expiredAt),
        ]);
    }
}