<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/3
 * Time: 17:02
 */

namespace App\WebSocket;


use App\Model\User;
use App\Utility\Redis;
use EasySwoole\Core\Socket\Response;
use EasySwoole\Core\Socket\AbstractInterface\WebSocketController as BaseWebSocketController;
use EasySwoole\Core\Swoole\ServerManager;
use EasySwoole\Core\Swoole\Task\TaskManager;

class WebSocketController extends BaseWebSocketController
{
    function actionNotFound(?string $actionName)
    {
        $this->response()->write("action call {$actionName} not found");
    }

    function hello()
    {
        $this->response()->write('call hello with arg:'.$this->request()->getArg('content'));

    }

    public function projectlist_init(){
        $fd = $this->client()->getFd();
        if($id_user = Redis::getInstance()->get('fd:'.$fd)){
            $projects = [];
            try{
                $projects = User::find($id_user)->projects()->get()->map(function ($item){
                    unset($item['pivot']);
                    return $item;
                })->toArray();
            }catch (\Exception $exception){
                var_dump($exception->getMessage());
            }
            $this->response()->write(\json_encode([
                'type' => 'projectlist_init',
                'data' => $projects,
            ]));
        }
    }

    public function tasklist_init(){
        $fd = $this->client()->getFd();
        var_dump('tasklist_init:'.$fd);
        if($id_user = Redis::getInstance()->get('fd:'.$fd)){
            //在project房间中中加入用户
            $tasks = [];
            try{
                $tasks = User::find($id_user)->tasks()->get()->map(function ($item){
                    unset($item['pivot']);
                    return $item;
                })->toArray();
            }catch (\Exception $exception){
                var_dump($exception->getMessage());
            }

            $this->response()->write(\json_encode([
                'type' => 'tasklist_init',
                'data' => $tasks,
            ]));
        }

    }
}