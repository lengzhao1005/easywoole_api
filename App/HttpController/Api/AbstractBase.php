<?php

namespace App\HttpController\Api;

use \App\Model\User\User as UserModel;
use App\Model\User\Bean;
use App\Utility\FormatResultErrors;
use App\Utility\Helper;
use App\Utility\SysConst;
use EasySwoole\Core\Component\Logger;
use EasySwoole\Core\Http\AbstractInterface\Controller;
use EasySwoole\Core\Http\Message\Status;
use EasySwoole\Core\Swoole\ServerManager;
use EasySwoole\Core\Swoole\Task\TaskManager;

abstract class AbstractBase extends Controller
{


    function index()
    {
        // TODO: Implement index() method.
        $this->actionNotFound('index');
    }

    protected function actionNotFound($action): void
    {
        $this->writeJson(Status::CODE_NOT_FOUND);
    }

    protected function onRequest($action): ?bool
    {
        /*$token = $this->request()->getCookieParams(SysConst::COOKIE_USER_SESSION_NAME);
        $bean = new Bean([
            'session'=>$token
        ]);
        $model = new UserModel();
        $bean = $model->sessionExist($bean);
        if($bean){
            $this->who = $bean;
            return true;
        }else{
            $this->writeJson(Status::CODE_UNAUTHORIZED,null,'权限验证失败');
            return false;
        }*/
        $trac_no = Helper::create_uuid();
        $request = $this->request();
        $request->withAttribute('trac_no', $trac_no);
        //获取用户IP地址
        $ip = ServerManager::getInstance()->getServer()->connection_info($request->getSwooleRequest()->fd);
        //拼接一个简单的日志
        $logStr = '('. $trac_no .') | '.$ip['remote_ip'] . ' | ' . $request->getUri() .' | '. $request->getHeader('user-agent')[0] . ' | '.\json_encode($this->request()->getRequestParam(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;

        TaskManager::async(function () use ($logStr){
            logger::getInstance()->log($logStr,'runlog');
        });
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
     * 公共返回方法
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