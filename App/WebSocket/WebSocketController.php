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
use EasySwoole\Core\Swoole\ServerManager;
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
        'token' => [
            'projectlist_init',
            'tasklist_init',
            'project_init',
            'tasklist_project_init',
            'project_modify',
            'project_save',
            'task_init',
            'task_save',
            'member_project_fetchall'
        ]
    ];

    protected $_join_rules = [
        'project_init',
        'tasklist_project_init',
        'projectlist_init',
    ];

    const TYPE_AUTH_FAIL  = 'auth_fail';

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
        var_dump('api is call at '. date('Y-m-d H:i:s') .' --action: '.$this->request()->getAction());
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
        $id_user = Redis::getInstance()->get($auth_token);
        echo 'auth_token is '.$auth_token. ' --id_user:'.$id_user.PHP_EOL;

        if(!empty($auth_token) && $id_user){
            $user = User::find($id_user);
            if(!empty($user)){
                $this->who = $user;
                User::resetTokenExpiredTime($auth_token);

                $this->_joinRoom();
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
        $this->_apiResponse($response_data, self::TYPE_AUTH_FAIL);
    }

    protected function _apiResponse($data = array(), $type = '')
    {
        $data['type'] = empty($type)? $this->request()->getAction() : $type ;
        $this->response()->write(\json_encode($data));
    }

    /**
     * 房间人员处理
     */
    protected function _joinRoom()
    {
        var_dump('is join room --'.$this->request()->getAction());
        if(!in_array($this->request()->getAction(), $this->_join_rules)){
            return ;
        }

        $fd = $this->client()->getFd();
        $auth_token = $this->request()->getArg('auth_token');
        $id_user = $this->who->id_user;
        //异步任务将fd与用户ID绑定
        TaskManager::async(function () use ($fd, $id_user, $auth_token){
            Redis::getInstance()->set(User::SW_FD_PREFIX.$fd, $id_user);
            //在project房间中中加入用户
            $id_projects = Redis::getInstance()->hGetAll(ProjectUser::USERPROJECTGREP.':'.$id_user);
            if(!empty($id_projects) && is_array($id_projects)){

                foreach($id_projects as $id_project){
                    echo 'join room --id_project:'. $id_project.' --id_user:' .$id_user.PHP_EOL;
                    Redis::getInstance()->hset(ProjectUser::PROJECTROOM.':'.$id_project, $id_user.'_'.$fd, $fd.'_'.$auth_token);
                }
            }
        });
    }

    /**
     * 推送异步websocket
     * @param string $id_project
     * @param string $type
     * @param array $data
     */
    protected function pushMsg($id_project='', $type='', $send_data = [])
    {
        //异步推送任务ws
        TaskManager::async(function () use ($id_project, $type, $send_data){
            echo "push message is calla at".date('Y-m-d H:i').' --type is'. $type . '--id_project is'. $id_project . PHP_EOL;

            $fd_tokens = Redis::getInstance()->hGetAll(ProjectUser::PROJECTROOM.':'.$id_project);

            if(!empty($fd_tokens) && is_array($fd_tokens)){
                foreach($fd_tokens as $fd_token){

                    $fd_token_arr = explode('_', $fd_token);

                    $fd = $fd_token_arr[0];
                    $auth_token = $fd_token_arr[1];

                    $id_user = Redis::getInstance()->get($auth_token);
                    if(!$id_user){
                        $data = [
                            'type' => self::TYPE_AUTH_FAIL,
                            'code' => '403',
                            'message' => 'auth fail',
                            $this->_getResponseData(FormatResultErrors::CODE_MAP['TOKEN.INVALID']),
                        ];
                    }else{
                        if(!empty($send_data['id_user']) && isset($send_data['mine'])){
                            $send_data['mine'] = ($send_data['id_user'] == $id_user);
                            unset($send_data['id_user']);
                        }

                        $data = [
                            'type' => $type,
                            'code' => '100',
                            'message' => 'success',
                            'data' => $send_data,
                        ];
                    }

                    $info = ServerManager::getInstance()->getServer()->connection_info($fd);
                    if(is_array($info)){
                        ServerManager::getInstance()->getServer()->push($fd, \json_encode($data));
                    }else{
                        echo "fd {$fd} not exist";
                    }
                }
            }
        });
    }

    /**
     * 用户注册
     */
    public function user_register()
    {
        $request_data = $this->request()->getArg('content');
        $response_data = $this->register($request_data);
        $this->_apiResponse($response_data);
    }

    /**
     * 用户登录
     */
    public function user_login()
    {
        $request_data = $this->request()->getArg('content');
        $fd = $this->client()->getFd();

        $response_data = $this->login($request_data, $fd);
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
                $data['name'] = $item->title;
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
     * 项目（名称和描述）详情
     */
    public function project_init()
    {
        if($this->_beforeAction()){

            $request_data = $this->request()->getArg('content');
            $response_data = $this->showProject($request_data);

            $this->_apiResponse($response_data);
        }else{
            $this->_auth_fail();
        }
    }

    /**
     * 项目下的任务详情
     */
    public function tasklist_project_init()
    {
        if($this->_beforeAction()){
            $request_data = $this->request()->getArg('content')??[];
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
            $response_data = $this->saveProject($request_data);
            $this->_apiResponse($response_data);
        }else{
            $this->_auth_fail();
        }
    }

    /**
     * 任务详情
     */
    public function task_init()
    {
        if(!$this->_beforeAction()){
            return $this->_auth_fail();
        }

        $request_data = $this->request()->getArg('content');
        $response_data = $this->showTask($request_data);
        return $this->_apiResponse($response_data);
    }

    /**
     * 保存/修改任务
     */
    public function task_save()
    {
        if($this->_beforeAction()){
            $request_data = $this->request()->getArg('content');
            $response_data = $this->saveTask($request_data);
            $this->_apiResponse($response_data);
        }else{
            $this->_auth_fail();
        }
    }

    public function member_project_fetchall()
    {
        if($this->_beforeAction()){

            $request_data = $this->request()->getArg('content');
            $response_data = $this->getUsersByIdProject($request_data);
            $this->_apiResponse($response_data);
        }else{
            $this->_auth_fail();
        }
    }
}