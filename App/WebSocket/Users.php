<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/20
 * Time: 17:12
 */

namespace App\WebSocket;


use App\Model\ProjectUser;
use App\Model\User;
use App\Utility\FormatResultErrors;
use App\Utility\Redis;
use EasySwoole\Core\Swoole\Task\TaskManager;
use EasySwoole\Core\Utility\Validate\Rules;
use EasySwoole\Core\Utility\Validate\Rule;
use EasySwoole\Core\Utility\Validate\Validate;

trait Users
{
    protected $_auth_rules = [
        'token' => []
    ];

    public function register($data = [])
    {
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
        $v = $validate->validate($data, $rule);
        if(!$v->hasError()){
            $user_data['password'] = $data['password'];
            $user_data['email'] = $data['email'];
            $user_data['phone'] = $data['phone'];
            //$code = $this->request()->getRequestParam('code');

            if($user_data['password'] !== $data['password_confirm']){
                return $this->getResponseData(FormatResultErrors::CODE_MAP['PASSWORD.NOT.SAME']);
            }

            if(!preg_match("/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/",$user_data['email'])){
                return $this->getResponseData(FormatResultErrors::CODE_MAP['EMAIL.INVALID']);
            }

            if(!preg_match("/^1[34578]\d{9}$/",$user_data['phone'])){
                return $this->getResponseData(FormatResultErrors::CODE_MAP['PHONE.INVALID']);
            }
            //获取缓存验证码
            /* $key = $this->request()->getRequestParam('verification_key');
             $hkey = $hkey = 'verify:'.$key;
             $verify_code = Redis::getInstance()->hGet($hkey,$username);
 
             if(!$verify_code){
                 return $this->getResponseData(FormatResultErrors::CODE_MAP['VERIFY.CODE.EXPIRED']);
             }
             if(!hash_equals($verify_code, $code)){
                 return $this->getResponseData(FormatResultErrors::CODE_MAP['VERIFY.CODE.EXPIRED']);
             }*/

            $user = \App\Model\User::where('email', $user_data['email'])->first();

            if(!empty($user)){
                return $this->getResponseData(FormatResultErrors::CODE_MAP['USER.EMAIL.EXITS']);
            }

            $user = \App\Model\User::where('phone', $user_data['phone'])->find();

            if(!empty($user)){
                return $this->getResponseData(FormatResultErrors::CODE_MAP['USER.PHONE.EXITS']);
            }

            try{
                $user = \App\Model\User::create($user_data);
            }catch (\Exception $e){
                return $this->getResponseData(FormatResultErrors::CODE_MAP['USER.ALLREADY.EXITS']);
            }

            $token = \App\Model\User::setToken($user);
            return $this->getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'], [
                'auth_token' => $token,
            ]);

        }else{
            return $this->getResponseData([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }

    /**
     * 登陆
     * @param array $data
     * @param $fd
     */
    public function login($data = array(), $fd)
    {
        //验证字段是否正合法
        $rule = new Rules();
        $rule->add('username','username字段错误')->withRule(Rule::REQUIRED);
        $rule->add('password','password字段错误')->withRule(Rule::REQUIRED);
        $validate = new Validate();
        $v = $validate->validate($data, $rule);

        if(!$v->hasError()){//合法
            $credenttails['password'] = \App\Model\User::getMD5Password($data['password']);

            //判断用户名是邮箱还是手机号
            filter_var($data['username'], FILTER_VALIDATE_EMAIL) ?
                $credenttails['email'] = $data['username'] :
                $credenttails['phone'] = $data['username'] ;

            //查找用户是否存在
            if(!empty($credenttails['email'])){
                $user = \App\Model\User::where('email', $credenttails['email'])
                    ->where('password', $credenttails['password'])
                    ->first();
            }else{
                $user = \App\Model\User::where('phone', $credenttails['phone'])
                    ->where('password', $credenttails['password'])
                    ->first();
            }

            //存在则获取用户token
            if(!empty($user)){
                $token = \App\Model\User::setToken($user);
                $id_user = $user->id_user;

                //异步任务将fd与用户ID绑定
                TaskManager::async(function () use ($fd, $id_user, $token){
                    Redis::getInstance()->set(User::SW_FD_PREFIX.$fd, $id_user);
                    Redis::getInstance()->setex(User::SW_FD_TOKEN_PREFIX.$fd, User::EXPIRED_SEC, $token);
                    //在project房间中中加入用户
                    $id_projects = Redis::getInstance()->hGetAll(ProjectUser::USERPROJECTGREP.':'.$id_user);
                    if(!empty($id_projects) && is_array($id_projects)){
                        foreach($id_projects as $id_project){
                            Redis::getInstance()->hset(ProjectUser::PROJECTROOM.':'.$id_project, $id_user, $fd);
                        }
                    }
                });

                $this->getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'], [
                    'auth_token' => $token,
                ]);
            }else{
                $this->getResponseData(FormatResultErrors::CODE_MAP['AUTH.FAIL']);
            }

        }else{//非法
            $this->getResponseData([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }
    /**
     * 获取登录用户信息
     */
    function info()
    {
        //return $this->getResponseData(FormatResultErrors::CODE_MAP['SUCCESS'], $this->who->toArray());
    }
}