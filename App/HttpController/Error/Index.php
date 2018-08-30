<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/3/6
 * Time: 下午9:50
 */

namespace App\HttpController\Error;


use EasySwoole\Core\Http\AbstractInterface\Controller;

class Index extends Controller
{

    function index()
    {
        // TODO: Implement index() method.
        //error  并不会被响应到客户端中。
        echo $a;
        $this->response()->write('error index');
    }

    function fatal()
    {
        //未重构本控制器异常处理的时候
        $test = new XXXXXXX();
        $this->response()->write('error fatal');
    }

    protected function onException(\Throwable $throwable, $actionName): void
    {
        //若重载实现了onException 方法，那么控制器内发生任何的异常，都会被该方法拦截，该方法决定了如何向客户端响应
        $this->response()->write($throwable->getMessage());
    }
}