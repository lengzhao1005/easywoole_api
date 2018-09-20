<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/3
 * Time: 17:02
 */

namespace App\WebSocket;

use App\Model\ProjectUser;
use App\Utility\FormatResultErrors;
use EasySwoole\Core\Http\Message\Status;
use EasySwoole\Core\Swoole\Task\TaskManager;
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

    protected $_auth_rules = [
        'token' => ['projectlist_init', 'tasklist_init', 'project_init', 'tasklist_project_init', 'project_modify', 'project_save']
    ];

    public $who;

    public function actionNotFound(?string $actionName)
    {
        $this->response()->write("action call {$actionName} not found");
    }

    /**
     * 公共返回方法，记录返回日志
     * @param $format_result
     * @param array $data
     * @return array
     */
    protected function _getResponseData($format_result, $data = [])
    {
        if (empty($format_result) || !is_array($format_result)) {
            $format_result = ['code' => Status::CODE_INTERNAL_SERVER_ERROR, 'message' => 'unknow'];
        }

        return \array_merge(['data' => $data], $format_result);
    }

    protected function _policeFail()
    {
        return $this->_getResponseData(FormatResultErrors::CODE_MAP['NO.ACCESS']);
    }
    /**
     * 请求认证
     * @return bool
     */
    protected function _beforeAction()
    {
        if(!in_array($this->request()->getAction(), $this->_auth_rules['token'])){
            return true;
        }

        $auth_token = $this->request()->getArg('auth_token');
        var_dump('auth_token'.$auth_token);
        if(!empty($auth_token) && $id_user = Redis::getInstance()->get($auth_token)){
            $user = User::find($id_user);
            if(!empty($user)){
                $this->who = $user;
                var_dump('+++++++++id_user'.$this->who->id_user);
                User::resetTokenExpiredTime($auth_token);

                //$this->_room();
                return true;
            }
        }

        return false;
    }

    /**
     * 认证失败
     */
    protected function _auth_fail()
    {
        $response_data = $this->_getResponseData(FormatResultErrors::CODE_MAP['TOKEN.INVALID']);
        $this->_apiResponse($response_data, 'auth_fail');
    }

    protected function _apiResponse($data = array(), $type = '')
    {
        var_dump(empty($type)? $this->request()->getAction() : $type);


        $data['type'] = empty($type)? $this->request()->getAction() : $type ;
        $this->response()->write(\json_encode($data));
    }

    /**
     * 房间人员处理
     */
    protected function _room()
    {
        $fd = $this->client()->getFd();
        $id_user = $this->who->id_user;
        //异步任务将fd与用户ID绑定
        TaskManager::async(function () use ($fd, $id_user){
            Redis::getInstance()->set(User::SW_FD_PREFIX.$fd, $id_user);
            //在project房间中中加入用户
            $id_projects = Redis::getInstance()->hGetAll(ProjectUser::USERPROJECTGREP.':'.$id_user);
            if(!empty($id_projects) && is_array($id_projects)){

                foreach($id_projects as $id_project){
                    Redis::getInstance()->hset(ProjectUser::PROJECTROOM.':'.$id_project, $id_user, $fd);
                }
            }
        });
    }

    /**
     * 用户注册
     */
    public function user_register()
    {
        var_dump($this->request()->getAction());
        $request_data = $this->request()->getArg('content');
        var_dump($request_data);
        $response_data = $this->register($request_data);
        var_dump($response_data);
        $this->_apiResponse($response_data);
    }

    /**
     * 用户登录
     */
    public function user_login()
    {
        $request_data = $this->request()->getArg('content');
        var_dump($request_data);
        $fd = $this->client()->getFd();
        var_dump($fd);

        $response_data = $this->login($request_data, $fd);
        var_dump($response_data);
        $this->_apiResponse($response_data);
    }

    /**
     * 项目列表
     */
    public function projectlist_init(){
        if($this->_beforeAction()){
            $id_user = $this->who->id_user;
            $projects = $this->who->projects()->get()->map(function ($item) use($id_user){
                unset($item['pivot']);
                $item->mine = ($item->id_user_create === $id_user);
                return $item;
            })->toArray();

            $response_data = $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'], ['projects' => $projects]);
            var_dump($response_data);
            $this->_apiResponse($response_data);

        }else{
            $this->_auth_fail();
        }
    }

    /**
     * 任务列表
     */
    public function tasklist_init(){

        if($this->_beforeAction()){
            $id_user = $this->who->id_user;
            $tasks = $this->who->tasks()->with('project')->get()->map(function ($item) use ($id_user){
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

            $response_data = $this->_getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'], ['tasks' => $tasks]);

            $this->_apiResponse($response_data);

        }else{
            $this->_auth_fail();
        }
    }

    /**
     * 项目详情
     */
    public function project_init()
    {
        if($this->_beforeAction()){

            $request_data = $this->request()->getArg('content');
            var_dump('--------id_user'. $this->who->id_user);
            $response_data = $this->showProject($request_data);

            $this->_apiResponse($response_data);
        }else{
            $this->_auth_fail();
        }
    }

    /**
     * 项目详情
     */
    public function tasklist_project_init()
    {
        if($this->_beforeAction()){
var_dump('tasklist_project_init');
            $request_data = $this->request()->getArg('content')??[];
            var_dump('--------id_user'. $this->who->id_user);
            $response_data = $this->showTasksByIdProject($request_data);

            $this->_apiResponse($response_data);
        }else{
            $this->_auth_fail();
        }
    }

    /**
     * 更新/创建项目
     */
    public function project_save()
    {
        if($this->_beforeAction()){

            $request_data = $this->request()->getArg('content');
            var_dump($request_data);
            $response_data = $this->saveProject($request_data);
            var_dump($response_data);
            $this->_apiResponse($response_data);
        }else{
            $this->_auth_fail();
        }
    }

    /**
     * 删除项目
     */
    public function project_delete()
    {
        if($this->_beforeAction()){

            $request_data = $this->request()->getArg('content');
            var_dump($request_data);
            $response_data = $this->destoryProject($request_data);
            var_dump($response_data);
            $this->_apiResponse($response_data);
        }else{
            $this->_auth_fail();
        }
    }


}