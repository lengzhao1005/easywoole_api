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
        //获取用户下所用的项目
        $routeCollector->get('/api/projects', '/api/project/getProjectsByUid');
        //获取项目详情
        $routeCollector->get('/api/projects/{id_project}/tasks', '/api/project/getProjectsByUid');

        //创建子项目
        $routeCollector->post('/api/sub_projects', '/api/subProject/store');
        //修改子项目
        $routeCollector->patch('/api/sub_projects/{id_project}', '/api/subProject/update');
        //删除子项目
        $routeCollector->delete('/api/sub_projects/{id_project}', '/api/subProject/destory');

        //获取项目下的用户
        $routeCollector->get('/api/project/{id_project}/users', '/api/project/getUsersByIdProject');
        //获取加入项目的code
        $routeCollector->get('/api/project/{id_project}/joincode', '/api/project/getJoinProjectCode');
        //加入项目
        //$routeCollector->post();

        //创建任务
        $routeCollector->post('/api/tasks','/api/task/store');
        //获取用户下所用的项目
        $routeCollector->get('/api/tasks', '/api/project/getTasksByUid');
        
        //批量设置房间成员
        $routeCollector->get('/batch-set-room-member', '/api/project/setMember');


        //==============================================跨域
        // 用户注册
        $routeCollector->get('/api/{cors}/users/register','/api/user/register');
        //发送验证码
        $routeCollector->get('/api/{cors}/verificationCodes', '/api/verificationCodes/index');
        //第三方登录
        $routeCollector->get('/api/{cors}/socials/{social_type}/authorizations    ','/api/authorizations/sociallogin');
        //登陆
        $routeCollector->get('/api/{cors}/users/login', '/api/authorizations/login');

        //创建项目
        $routeCollector->get('/api/{cors}/projects/create', '/api/project/store');
        //修改项目
        $routeCollector->get('/api/{cors}/projects/{id_project}/edit', '/api/project/update');
        //删除项目
        $routeCollector->get('/api/{cors}/projects/{id_project}/delete', '/api/project/destory');
        //获取用户下所用的项目
        $routeCollector->get('/api/{cors}/projects', '/api/project/getProjectsByUid');
        //获取项目详情
        $routeCollector->get('/api/{cors}/projects/{id_project}/tasks', '/api/project/getProjectsByUid');

        //创建子项目
        $routeCollector->get('/api/{cors}/sub_projects/create', '/api/subProject/store');
        //修改子项目
        $routeCollector->get('/api/{cors}/sub_projects/{id_project}/edit', '/api/subProject/update');
        //删除子项目
        $routeCollector->get('/api/{cors}/sub_projects/{id_project}/delete', '/api/subProject/destory');

        //获取项目下的用户
        $routeCollector->get('/api/{cors}/project/{id_project}/users', '/api/project/getUsersByIdProject');
        //获取加入项目的code
        $routeCollector->get('/api/{cors}/project/{id_project}/joincode', '/api/project/getJoinProjectCode');
        //加入项目
        //$routeCollector->post();

        //创建任务
        $routeCollector->get('/api/{cors}/tasks/create','/api/task/store');
        //获取用户下所用的项目
        $routeCollector->get('/api/{cors}/tasks', '/api/project/getTasksByUid');
    }

}