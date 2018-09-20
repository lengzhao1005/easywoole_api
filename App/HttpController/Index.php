<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/3/3
 * Time: 下午6:14
 */

namespace App\HttpController;


use App\Model\Project;
use App\Model\Task;
use App\Model\User;
use EasySwoole\Core\Http\AbstractInterface\Controller;
use EasySwoole\Core\Http\Message\Status;
use EasySwoole\Core\Swoole\ServerManager;

class Index extends Controller
{

    //测试路径 /index.html
    function index()
    {
        $id_user= 1;

        $tasks = Project::where('id_project', 13)->with('tasks')->get()->map(function ($item) use ($id_user){
            unset($item['pivot']);
            $data['id_project'] = $item->id_project;
            $data['project_name'] = $item->name;
            $data['tasks'] = [];
            foreach ($item->tasks as $k=>$task){
                $data['tasks'][$k]['id_task'] = $task->id_task;
                $data['tasks'][$k]['content'] = $task->content;
                $data['tasks'][$k]['mine'] = ($id_user == $task->id_user_create ? true :false);
                $data['tasks'][$k]['completed'] = ($task->is_finished == 2 ? true :false);
                $data['tasks'][$k]['priority'] = [
                    'type' => $task->emergency_rank,
                    'txt' => Task::getEmergencyTxt($task->emergency_rank),
                ];
            }
            return $data;
        })->toArray();
        var_dump($tasks);
        // TODO: Implement index() method.
        $this->response()->write('hello world'.json_encode($tasks));
    }
    //测试路径 /test/index.html
    function test()
    {
        $ip = ServerManager::getInstance()->getServer()->connection_info($this->request()->getSwooleRequest()->fd);
        var_dump($ip);
        $ip2 = $this->request()->getHeaders();
        var_dump($ip2);
        $this->response()->write('index controller test');
    }

    /*
     * protected 方法对外不可见
     *  测试路径 /hide/index.html
     */
    protected function hide()
    {
        var_dump('this is hide method');
    }

    protected function actionNotFound($action): void
    {
        $this->response()->withStatus(Status::CODE_NOT_FOUND);
        $this->response()->write("{$action} is not exist");
    }

    function a()
    {
        $this->response()->write('index controller router');
    }

    function a2()
    {
        $this->response()->write('index controller router2');
    }

    function test2(){
        $this->response()->write('this is controller test2 and your id is '.$this->request()->getRequestParam('id'));
    }
}