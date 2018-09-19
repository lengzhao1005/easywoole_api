<?php

namespace App\HttpController\Api;


use EasySwoole\Core\Http\AbstractInterface\Controller;
use EasySwoole\Core\Http\Message\Status;

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