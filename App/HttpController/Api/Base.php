<?php

namespace App\HttpController\Api;

use App\Utility\FormatResultErrors;
use App\Utility\Helper;
use App\Utility\Redis;
use App\Utility\SysConst;
use EasySwoole\Config;
use EasySwoole\Core\Component\Logger;
use \App\Model\User;
use EasySwoole\Core\Swoole\ServerManager;
use EasySwoole\Core\Swoole\Task\TaskManager;

class Base extends AbstractBase
{
    protected $who;

    protected $_auth_rules = [
        'token' => []
    ];

    public function getIsAuthRuleMode($mode, $action)
    {
        if (isset($this->_auth_rules[$mode])) {
            return in_array($action, $this->_auth_rules[$mode][$action]);
        }
        return false;
    }

    public function RunAuthToken($action)
    {
        if ($this->getIsAuthRuleMode('token', $action)) {
            //参数中传token
            $token = $this->request()->getRequestParam('token');

            //请求头中传token
            /*if(empty($token)){
                $token = empty($this->request()->getHeader('token')) ?'':$this->request()->getHeader('token')['0'];
            }*/

            //请求头authorization方式传token
            if (empty($token)) {
                $token = empty($this->request()->getHeader('authorization')) ? '' : substr($this->request()->getHeader('authorization')['0'], 7);
            }

            //验证token
            if (empty($token)) {
                $this->returnJsonCROS(FormatResultErrors::CODE_MAP['TOKEN.INVALID']);
                return false;
            }

            $user = User::authToken($token);

            if (!$user) {
                $this->returnJsonCROS(FormatResultErrors::CODE_MAP['TOKEN.INVALID']);
                return false;
            }
            //自动刷新token过期时间
            User::resetTokenExpiredTime($token);
            $this->response()->setCookie(SysConst::COOKIE_USER_SESSION_NAME, $token, time() + User::EXPIRED_SEC);
            $this->who = $user;
        }

        return true;
    }

    /**
     * @param $action
     * @return bool|null
     */
    public function onRequest($action): ?bool
    {
        $key = $this->runWriteLog();

        $auth_flag = $this->RunAuthToken($action);

        $limit_flag = $this->runLimitRequest($key);

        return $auth_flag && $limit_flag;

    }

    /**
     * 记录请求日志
     * @param $action
     * @return bool|null
     */
    protected function runWriteLog()
    {

        $trac_no = Helper::create_uuid();
        $request = $this->request();
        $request->withAttribute('trac_no', $trac_no);
        //获取用户IP地址
        $ip = ServerManager::getInstance()->getServer()->connection_info($request->getSwooleRequest()->fd);
        //拼接一个简单的日志
        $key = $ip['remote_ip'] . ' | ' . $request->getUri() .' | '. $request->getHeader('user-agent')[0];
        $logStr = '('. $trac_no .') | '. $key . ' | '.\json_encode($this->request()->getRequestParam(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;

        TaskManager::async(function () use ($logStr){
            logger::getInstance()->log($logStr,'runlog');
        });

        return md5($key);
    }

    protected function returnJsonCROS($format_result, $data = [])
    {
        if(empty($format_result) || !is_array($format_result)){
            $format_result = ['code'=> Status::CODE_INTERNAL_SERVER_ERROR, 'message' => 'unknow'];
        }

        $request = $this->request();
        $callback = $request->getAttribute('callback');

        $response_data = \json_encode(
            \array_merge(['data' => $data], $format_result),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );


        $trac_no = $request->getAttribute('trac_no');
        //拼接一个简单的日志
        $logStr = '('. $trac_no .') | ' . $callback . '(), response_data: '.$response_data.PHP_EOL;
        TaskManager::async(function () use ($logStr){
            logger::getInstance()->log($logStr,'runlog');
        });

        $ret = $callback . '('. $response_data .')';

        $this->response()->write($ret);
    }


    /**
     * 请求频率限制
     * @param $key
     * @param string $limit_type
     * @return bool
     */
    protected function runLimitRequest($key, $limit_type = 'rate_limits')
    {
        $config = Config::getInstance()->getConf($limit_type);
        if(!empty($config) && $config['debug'] === false){
            //获取redis单例
            $redis = Redis::getInstance()->getRedis();
            $check = $redis->exists($key);
            if($check){
                //自增
                $redis->incr($key);
                $count = $redis->get($key);
                if($count > $config['access']['limit']){
                    $this->response()->write(\json_encode([
                        'code' => Status::CODE_GONE,
                        'data'=>[],
                        'message' => '请求频率超限'
                    ], JSON_UNESCAPED_UNICODE));
                    return false;
                }
            }else{
                $redis->set($key,1);
                //限制时间为60秒
                $redis->expire($key,$config['access']['expires']);
            }
        }
        return true;
    }

    /**
     * @param string $method
     * @return mixed
     */
    protected function verificationMethod($method)
    {
        if(strtoupper($this->request()->getMethod()) !== strtoupper($method)){
            return FormatResultErrors::CODE_MAP['METHOD.NOTALLOW'];
        }
        return true;
    }

    /**
     * 公共返回方法，记录返回日志
     * @param $format_result
     * @param array $data
     * @param string $trac_no
     */
    protected function returnJson($format_result, $data = [])
    {
        if(empty($format_result) || !is_array($format_result)){
            $format_result = ['code'=> Status::CODE_INTERNAL_SERVER_ERROR, 'message' => 'unknow'];
        }

        $response_data = \json_encode(
            \array_merge(['data' => $data], $format_result),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $request = $this->request();
        $trac_no = $request->getAttribute('trac_no');
        //拼接一个简单的日志
        $logStr = '('. $trac_no .') | '.$response_data.PHP_EOL;
        TaskManager::async(function () use ($logStr){
            logger::getInstance()->log($logStr,'runlog');
        });

        $this->response()->write($response_data);
    }
}