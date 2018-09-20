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
    function actionNotFound(?string $actionName)
    {
        $this->response()->write("action call {$actionName} not found");
    }

    function hello()
    {
        $this->response()->write('call hello with arg:'.$this->request()->getArg('content'));

    }

    protected function beforePush($fd)
    {

    }

    protected function apiResponse($data = array())
    {
        $this->response()->write();
    }

    public function user_register()
    {
        $request_data = $this->request()->getArg('content');
        var_dump($this->request()->getArg('action'));
//        $request_data = \json_decode($this->request()->getArg('action'), true);
var_dump($request_data);
        $rule = new Rules();
        $rule->add('email','email字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,3)
            ->withRule(Rule::MAX_LEN,60);
        $rule->add('phone','phone字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,3)
            ->withRule(Rule::MAX_LEN,60);
        $rule->add('password','password字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,6)
            ->withRule(Rule::MAX_LEN,30);
        $rule->add('password_confirm','password_confirm字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,6)
            ->withRule(Rule::MAX_LEN,30);
        /*$rule->add('code','code字段错误')->withRule(Rule::REQUIRED);
        $rule->add('verification_key','code字段错误')->withRule(Rule::REQUIRED);*/
        $validate = new Validate();
        $v = $validate->validate($request_data, $rule);
        if(!$v->hasError()){
            $user_data['password'] = $request_data['password'];
            $user_data['email'] = $request_data['email'];
            $user_data['phone'] = $request_data['phone'];
            //$code = $this->request()->getRequestParam('code');
            $confirm_password = $request_data['password_confirm'];

            if($user_data['password'] !== $confirm_password){
                $respon_data = [FormatResultErrors::CODE_MAP['PASSWORD.NOT.SAME']];
                return $this->response()->write(\json_encode([
                    'type' => 'user_register',
                    'data' => $respon_data,
                ]));
            }

            if(!preg_match("/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/",$user_data['email'])){
                $respon_data = [FormatResultErrors::CODE_MAP['EMAIL.INVALID']];
                return $this->response()->write(\json_encode([
                    'type' => 'user_register',
                    'data' => $respon_data,
                ]));
            }

            if(!preg_match("/^1[34578]\d{9}$/",$user_data['phone'])){
                $respon_data = [FormatResultErrors::CODE_MAP['PHONE.INVALID']];
                return $this->response()->write(\json_encode([
                    'type' => 'user_register',
                    'data' => $respon_data,
                ]));
            }
            //获取缓存验证码
            /* $key = $this->request()->getRequestParam('verification_key');
             $hkey = $hkey = 'verify:'.$key;
             $verify_code = Redis::getInstance()->hGet($hkey,$username);

             if(!$verify_code){
                 return $this->returnJson(FormatResultErrors::CODE_MAP['VERIFY.CODE.EXPIRED']);
             }
             if(!hash_equals($verify_code, $code)){
                 return $this->returnJson(FormatResultErrors::CODE_MAP['VERIFY.CODE.EXPIRED']);
             }*/

            $user = \App\Model\User::where('email', $user_data['email'])->first();

            if(!empty($user)){
                $respon_data = [FormatResultErrors::CODE_MAP['USER.EMAIL.EXITS']];
                return $this->response()->write(\json_encode([
                    'type' => 'user_register',
                    'data' => $respon_data,
                ]));
            }

            $user = \App\Model\User::where('phone', $user_data['phone'])->first();

            if(!empty($user)){
                $respon_data = [FormatResultErrors::CODE_MAP['USER.PHONE.EXITS']];
                return $this->response()->write(\json_encode([
                    'type' => 'user_register',
                    'data' => $respon_data,
                ]));
            }

            try{
                $user = \App\Model\User::create($user_data);
            }catch (\Exception $e){
                $respon_data = [FormatResultErrors::CODE_MAP['USER.ALLREADY.EXITS']];
                return $this->response()->write(\json_encode([
                    'type' => 'user_register',
                    'data' => $respon_data,
                ]));
            }

            $token = \App\Model\User::setToken($user);
            $respon_data = [FormatResultErrors::CODE_MAP['SUCCESS'], [
                'auth_token' => $token,
            ]];
            return $this->response()->write(\json_encode([
                'type' => 'user_register',
                'data' => $respon_data,
            ]));
        }else{
            $respon_data = [
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ];
            return $this->response()->write(\json_encode([
                'type' => 'user_register',
                'data' => $respon_data,
            ]));
        }
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