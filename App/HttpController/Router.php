<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/3/6
 * Time: 上午11:41
 */

namespace App\HttpController;


use EasySwoole\Core\Http\Request;
use EasySwoole\Core\Http\Response;
use FastRoute\RouteCollector;

class Router extends \EasySwoole\Core\Http\AbstractInterface\Router
{

    function register(RouteCollector $routeCollector)
    {
        // 用户注册
        $routeCollector->post('/api/users','/api/user/register');
        //发送验证码
        $routeCollector->post('/api/verificationCodes', '/api/verificationcodes/index');
        //第三方登录
        $routeCollector->post('/api/socials/{social_type}/authorizations    ','/api/authorizations/sociallogin');
        //登陆
        $routeCollector->post('/api/authorizations', '/api/authorizations/login');

        //创建项目
        $routeCollector->post('/api/projects', '/api/project/store');
        //修改项目
        $routeCollector->patch('/api/projects/{id_project}', '/api/project/update');
        //删除项目
        $routeCollector->delete('/api/projects/{id_project}', '/api/project/destory');
    }

}