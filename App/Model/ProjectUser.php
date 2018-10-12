<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/9/7
 * Time: 17:34
 */

namespace App\Model;

use EasySwoole\Core\Swoole\ServerManager;
use EasySwoole\Core\Swoole\Task\TaskManager;
use App\Utility\Redis;

class ProjectUser extends LaravelBaseModel
{
    protected $table = 'project_user';
    protected $primaryKey = 'id_project_user';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
    //
    const USERPROJECTGREP = 'user_project_grep';
    //
    const PROJECTROOM = 'project_room';
    /**
     * 设置项目下的用户id队列
     * @param string $id_project
     * @param string $id_user
     */
    public static function setUserProjectList($id_project = '', $id_user = '')
    {
        if(empty($id_project) || empty($id_user)){
            return ;
        }

        $redis = Redis::getInstance();

        $redis->hSet(self::USERPROJECTGREP.':'.$id_user,$id_project,$id_project);
    }

    /**
     * 批量设置项目下的用户id队列
     */
    public static function setBatchUserProjectList()
    {
        self::select('id_user', 'id_project')->chunk(100, function($project_users){
            $redis = Redis::getInstance();
            foreach ($project_users as $project_user){
                $redis->hSet(self::USERPROJECTGREP.':'.$project_user->id_user,$project_user->id_project,$project_user->id_project);
            }
        });
    }


    public static function pushMsg($id_project='', $type='', $data = [])
    {
        //异步推送任务ws
        TaskManager::async(function () use ($id_project, $type, $data){
            echo "push message is calla at".date('Y-m-d H:i').' --type is'. $type . '--id_project is'. $id_project . PHP_EOL;

            $fds = Redis::getInstance()->hGetAll(ProjectUser::PROJECTROOM.':'.$id_project);
            if(!empty($fds) && is_array($fds)){
                foreach($fds as $fd){
                    $info = ServerManager::getInstance()->getServer()->connection_info($fd);
                    if(is_array($info)){
                        ServerManager::getInstance()->getServer()->push($fd, \json_encode([
                            'type' => $type,
                            'data' => $data,
                        ]));
                    }else{
                        echo "fd {$fd} not exist";
                    }
                }
            }
        });
    }
}