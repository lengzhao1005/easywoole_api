<?php

namespace App\HttpController\Api;

use \App\Model\User\User as UserModel;
use App\Model\User\Bean;
use App\Utility\SysConst;
use EasySwoole\Core\Http\AbstractInterface\Controller;
use EasySwoole\Core\Http\Message\Status;
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
        $this->request()->withAttribute('requestTime', microtime(true));
        var_dump($this->request()->getUri()->getPath());

        var_dump($this->request()->getRequestParam());

        TaskManager::async(function (){
            sleep(2);
            var_dump('this is async task');
        });
        return true;
    }

    /**
     * 公共返回方法
     * @param $format_result
     * @param array $data
     * @param string $trac_no
     */
    protected function returnJson($format_result, $data = [], $trac_no='')
    {
        if(empty($format_result) || !is_array($format_result)){
            $format_result = ['code'=> Status::CODE_INTERNAL_SERVER_ERROR, 'message' => 'unknow'];
        }

        $response_data = \json_encode(
            \array_merge(['date' => $data], $format_result),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $this->response()->write($response_data);
    }

    /**
     * 拦截系统异常
     * @param \Throwable $throwable
     * @param $actionName
     */
    protected function onException(\Throwable $throwable, $actionName): void
    {
        //若重载实现了onException 方法，那么控制器内发生任何的异常，都会被该方法拦截，该方法决定了如何向客户端响应
        $this->response()->write(\json_encode([
            'code' => Status::CODE_INTERNAL_SERVER_ERROR,
            'data'=>[],
            'message' => '系统异常'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}