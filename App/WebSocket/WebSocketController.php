<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/3
 * Time: 17:02
 */

namespace App\WebSocket;

use App\Utility\FormatResultErrors;
use EasySwoole\Core\Utility\Validate\Rule;
use App\Model\Project;
use App\Model\Task;
use App\Model\User;
use App\Utility\Redis;
use EasySwoole\Core\Socket\AbstractInterface\WebSocketController as BaseWebSocketController;
use EasySwoole\Core\Utility\Validate\Rules;
use EasySwoole\Core\Utility\Validate\Validate;

class WebSocketController extends BaseWebSocketController
{
    use Users, Projects, Tasks;

    function actionNotFound(?string $actionName)
    {
        $this->response()->write("action call {$actionName} not found");
    }

    function hello()
    {
        $this->response()->write('call hello with arg:'.$this->request()->getArg('content'));

    }

    /**
     * 公共返回方法，记录返回日志
     * @param $format_result
     * @param array $data
     * @return array
     */
    protected function getResponseData($format_result, $data = [])
    {
        if(empty($format_result) || !is_array($format_result)){
            $format_result = ['code'=> Status::CODE_INTERNAL_SERVER_ERROR, 'message' => 'unknow'];
        }

        return \array_merge(['data' => $data], $format_result);
    }

    protected function beforePush($fd)
    {

    }

    protected function apiResponse($data = array())
    {
        $this->response()->write(\json_encode([
            'data' => $data,
            'type' => 'user_register',
        ]));
    }

    public function user_register()
    {
        $request_data = $this->request()->getArg('content');
        $action = $this->request()->getArg('action');
        var_dump($action);
        $response_data = $this->register($request_data);

        $this->apiResponse($response_data);
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
                'data' => [
                    'projects' => $projects,
                ],
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
                $tasks = User::find($id_user)->tasks()->with('project')->get()->map(function ($item) use ($id_user){
                    unset($item['pivot']);
                    $data['id_task'] = $item->id_task;
                    $data['content'] = $item->content;
                    $data['priority'] = [
                        'type' => $item->emergency_rank,
                        'txt' => Task::getEmergencyTxt($item->emergency_rank),
                    ];
                    $data['project_name'] = $item->project->name;
                    $data['mine'] = ($id_user == $item->id_user_create ? true :false);
                    $data['completed'] = ($item->is_finished == 2 ? true :false);
                    return $data;
                })->toArray();
            }catch (\Exception $exception){
                var_dump($exception->getMessage());
            }

            $this->response()->write(\json_encode([
                'type' => 'tasklist_init',
                'data' => [
                    'tasks' => $tasks,
                ],
            ]));
        }
    }

    public function tasklist_project_init()
    {
        $fd = $this->client()->getFd();
        var_dump('tasklist_project_init'.$fd);
        $request_data = \json_decode($this->request()->getArg('content'), true);

        if($id_user = Redis::getInstance()->get('fd:'.$fd) && $request_data && !empty($request_data['id_project'])){
            //在project房间中中加入用户
            $tasks = [];

            try{
                $tasks = Project::where('id_project', $request_data['id_project'])->with('tasks')->get()->map(function ($item) use ($id_user){
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
            }catch (\Exception $exception){
                var_dump($exception->getMessage());
            }

            $this->response()->write(\json_encode([
                'type' => 'tasklist_project_init',
                'data' => [
                    'tasklist_project' => $tasks,
                ],
            ]));
        }
    }
}