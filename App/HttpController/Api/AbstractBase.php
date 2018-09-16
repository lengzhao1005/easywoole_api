<?php

namespace App\HttpController\Api;

use \App\Model\User\User as UserModel;
use App\Model\User\Bean;
use App\Utility\FormatResultErrors;
use App\Utility\Helper;
use App\Utility\Redis;
use App\Utility\SysConst;
use EasySwoole\Config;
use EasySwoole\Core\Component\Logger;
use EasySwoole\Core\Http\AbstractInterface\Controller;
use EasySwoole\Core\Http\Message\Status;
use EasySwoole\Core\Swoole\ServerManager;
use EasySwoole\Core\Swoole\Task\TaskManager;

abstract class AbstractBase extends Controller
{

    /**
     * 实现index方法
     */
    function index()
    {
        // TODO: Implement index() method.
        $this->actionNotFound('index');
    }

    /**
     * @param $action
     */
    protected function actionNotFound($action): void
    {
        $this->writeJson(Status::CODE_NOT_FOUND);
    }

    /**
     * 记录请求日志
     * @param $action
     * @return bool|null
     */
    protected function onRequest($action): ?bool
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

        //请求频率限制
        if($this->requestLimit(md5($key))){
            return true;
        }
        return false;
    }

    /**
     * 请求频率限制
     * @param $key
     * @param string $limit_type
     * @return bool
     */
    protected function requestLimit($key, $limit_type = 'rate_limits')
    {
        $config = Config::getInstance()->getConf($limit_type);
        if(!empty($config) && $config['debug'] === false){
            //获取redis单例
            $redis = Redis::getInstance();
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
                $redis->incr($key);
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

    /**
     * 拦截系统异常
     * @param \Throwable $throwable
     * @param $actionName
     */
    protected function onException(\Throwable $throwable, $actionName): void
    {
        var_dump('error:'.$throwable->getMessage());
        //若重载实现了onException 方法，那么控制器内发生任何的异常，都会被该方法拦截，该方法决定了如何向客户端响应
        $this->response()->write(\json_encode([
            'code' => Status::CODE_INTERNAL_SERVER_ERROR,
            'data'=>[],
            'message' => '系统异常'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}