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
        $routeCollector->get('/api/users','/api/user/register');
        //第三方登录
        $routeCollector->get('/api/socials/{social_type}/authorizations','/api/authorizations/sociallogin');
        //登陆
        $routeCollector->get('/api/authorizations', '/api/authorizations/login');


    }

}