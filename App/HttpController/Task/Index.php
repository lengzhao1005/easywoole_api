<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/3/6
 * Time: 上午11:29
 */

namespace App\HttpController\Task;

use EasySwoole\Core\Http\Message\Status;
use EasySwoole\Core\Http\AbstractInterface\Controller;
use EasySwoole\Core\Swoole\Task\TaskManager;

class Index extends Controller
{

    function index()
    {
        // TODO: Implement index() method.
        $this->response()->write('async task add');
    }

    function async()
    {
        TaskManager::async(function (){
            sleep(2);
            var_dump('this is async task');
        });
        $this->response()->write('async task add');
    }

    protected function actionNotFound($action): void
    {
        $this->writeJson(Status::CODE_NOT_FOUND);
    }
}