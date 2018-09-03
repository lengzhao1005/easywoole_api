<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/3/3
 * Time: 下午6:21
 */

namespace App\HttpController\Api;

use App\Model\User\Bean;
use App\Utility\FormatResultErrors;
use App\Utility\Redis;
use App\Utility\SysConst;
use EasySwoole\Core\Component\Pool\PoolManager;
use EasySwoole\Core\Http\Message\Status;
use \App\Model\User\User as UserModel;
use EasySwoole\Core\Utility\Validate\Rule;
use EasySwoole\Core\Utility\Validate\Rules;

class User extends AbstractBase
{
/*    //onRequest返回false的时候，为拦截请求，不再往下执行方法
    protected $who;
    protected function onRequest($action): ?bool
    {
        $token = $this->request()->getCookieParams(SysConst::COOKIE_USER_SESSION_NAME);
        $bean = new Bean([
            'session'=>$token
        ]);
        $model = new UserModel();
        $bean = $model->sessionExist($bean);
        if($bean){
            $this->who = $bean;
            return true;
        }else{
            $this->writeJson(Status::CODE_UNAUTHORIZED,null,'权限验证失败');
            return false;
        }
    }*/
    public function index()
    {
        $this->actionNotFound('index');
    }

    function register()
    {
        if($verfy_result = $this->verificationMethod('POST') !== true){
            $this->returnJson($verfy_result);
        }
        $rule = new Rules();
        $rule->add('name','name字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,3)
            ->withRule(Rule::MAX_LEN,60);
        $rule->add('password','password字段错误')->withRule(Rule::REQUIRED)
            ->withRule(Rule::MIN_LEN,6)
            ->withRule(Rule::MAX_LEN,30);
        $rule->add('code','code字段错误')->withRule(Rule::REQUIRED);
        $rule->add('verification_key','code字段错误')->withRule(Rule::REQUIRED);

        $v = $this->validateParams($rule);
        if(!$v->hasError()){
            $user_data['password'] = $this->request()->getRequestParam('password');
            $username = $this->request()->getRequestParam('name');
            $code = $this->request()->getRequestParam('code');

            //获取缓存验证码
            $key = $this->request()->getRequestParam('verification_key');
            $hkey = $hkey = 'verify:'.$key;
            $verify_code = Redis::getInstance()->hGet($hkey,$username);

            if(!$verify_code){
                return $this->returnJson(FormatResultErrors::CODE_MAP['VERIFY.CODE.EXPIRED']);
            }
            if(!hash_equals($verify_code, $code)){
                return $this->returnJson(FormatResultErrors::CODE_MAP['VERIFY.CODE.EXPIRED']);
            }


            filter_var($username, FILTER_VALIDATE_EMAIL) ?
                $user_data['email'] = $username :
                $user_data['phone'] = $username ;

            try{
                $user = \App\Model\User::create($user_data);
            }catch (\Exception $e){
                return $this->returnJson(FormatResultErrors::CODE_MAP['USER.ALLREADY.EXITS']);
            }

            $token = \App\Model\User::setToken($user);
            return $this->returnJson(FormatResultErrors::CODE_MAP['SUCCESS'], [
                'token' => $token,
            ]);

        }else{
            $this->returnJson([
                'code' => FormatResultErrors::CODE_MAP['FIELD.INVALID']['code'],
                'message' => $v->getErrorList()->first()->getMessage(),
            ]);
        }
    }
    /*
     * 测试url路径/api/user/info/index.html
     * 测试前请执行登录
     */
    function info()
    {
        $this->writeJson(Status::CODE_OK,$this->who->toArray());
    }
}