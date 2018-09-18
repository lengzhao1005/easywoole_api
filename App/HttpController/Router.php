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
        $routeCollector->post('/api/verificationCodes', '/api/verificationCodes/index');
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

        //创建子项目
        $routeCollector->post('/api/sub_projects', '/api/subproject/store');
        //修改子项目
        $routeCollector->patch('/api/sub_projects/{id_project}', '/api/subproject/update');
        //删除子项目
        $routeCollector->delete('/api/sub_projects/{id_project}', '/api/subproject/destory');
        //获取项目下的用户
        $routeCollector->get('/api/project/{id_project}/users', '/api/project/getUsersByIdProject');
        //获取加入项目的code
        $routeCollector->get('/api/project/{id_project}/joincode', '/api/project/getJoinProjectCode');
        //加入项目
        //$routeCollector->post();

        //创建任务
        $routeCollector->post('/api/tasks','/api/task/store');
    }

}