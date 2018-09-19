<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/3/3
 * Time: 下午6:21
 */

namespace App\HttpController\Api;

use App\Utility\FormatResultErrors;
use App\Utility\Redis;
use App\Utility\SysConst;
use EasySwoole\Core\Component\Pool\PoolManager;
use EasySwoole\Core\Http\Message\Status;
use EasySwoole\Core\Utility\Validate\Rule;
use EasySwoole\Core\Utility\Validate\Rules;

class User extends Base
{

    protected $_auth_rules = [
        'token' => []
    ];

    public function index()
    {
        $this->actionNotFound('index');
    }

    public function register()
    {
        if(($verfy_result = $this->verificationMethod('POST')) !== true){
            $this->returnJsonCROS($verfy_result);
        }
        $rule = new Rules();
        $rule->add('username','username字段错误')->withRule(Rule::REQUIRED)
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
            $username = $this->request()->getRequestParam('username');
            $code = $this->request()->getRequestParam('code');

            //获取缓存验证码
            $key = $this->request()->getRequestParam('verification_key');
            $hkey = $hkey = 'verify:'.$key;
            $verify_code = Redis::getInstance()->hGet($hkey,$username);

            if(!$verify_code){
                return $this->returnJsonCROS(FormatResultErrors::CODE_MAP['VERIFY.CODE.EXPIRED']);
            }
            if(!hash_equals($verify_code, $code)){
                return $this->returnJsonCROS(FormatResultErrors::CODE_MAP['VERIFY.CODE.EXPIRED']);
            }


            filter_var($username, FILTER_VALIDATE_EMAIL) ?
                $user_data['email'] = $username :
                $user_data['phone'] = $username ;

            try{
                $user = \App\Model\User::create($user_data);
            }catch (\Exception $e){
                return $this->returnJsonCROS(FormatResultErrors::CODE_MAP['USER.ALLREADY.EXITS']);
            }

            $token = \App\Model\User::setToken($user);
            return $this->returnJsonCROS(FormatResultErrors::CODE_MAP['SUCCESS'], [
                'auth_token' => $token,
            ]);

        }else{
            return $this->returnJsonCROS([
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
        return $this->returnJsonCROS(FormatResultErrors::CODE_MAP['SUCCESS'], $this->who->toArray());
    }
}