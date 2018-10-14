<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2017/12/30
 * Time: 下午10:59
 */

return [
    'SERVER_NAME'=>"EasySwoole",
    'ENV' => 'dev',
    'MAIN_SERVER'=>[
        'HOST'=>'0.0.0.0',
        'PORT'=>9501,
        'SERVER_TYPE'=>\EasySwoole\Core\Swoole\ServerManager::TYPE_WEB_SOCKET_SERVER,
        'SOCK_TYPE'=>SWOOLE_TCP,//该配置项当为SERVER_TYPE值为TYPE_SERVER时有效
        'RUN_MODEL'=>SWOOLE_PROCESS,
        'SETTING'=>[
            'task_worker_num' => 8, //异步任务进程
            'task_max_request'=>10,
            'max_request'=>5000,//强烈建议设置此配置项
            'worker_num'=>8
        ],
    ],
    'DEBUG'=>true,
    'TEMP_DIR'=>EASYSWOOLE_ROOT.'/Temp',
    'LOG_DIR'=>EASYSWOOLE_ROOT.'/Log',
    'EASY_CACHE'=>[
        'PROCESS_NUM'=>3,//若不希望开启，则设置为0
        'PERSISTENT_TIME'=>5//如果需要定时数据落地，请设置对应的时间周期，单位为秒
    ],
    'CLUSTER'=>[
        'enable'=>false,
        'token'=>null,
        'broadcastAddress'=>['255.255.255.255:9556'],
        'listenAddress'=>'0.0.0.0',
        'listenPort'=>'9556',
        'broadcastTTL'=>5,
        'nodeTimeout'=>10,
        'nodeName'=>'easySwoole',
        'nodeId'=>null
    ],
    'MYSQL'=>[
        'HOST'=>'127.0.0.1',
        'USER'=>'homestead',
        'PASSWORD'=>'secret',
        'DB_NAME'=>'test'
    ],
    'database' => [
        'driver'    => 'mysql',
        'host'      => '',
        'database'  => '',
        'username'  => '',
        'password'  => '',
        'charset'   => 'utf8',
        'collation' => 'utf8_general_ci',
        'prefix'    => ''
    ],
    'REDIS' => [
        'host' => '127.0.0.1', // redis主机地址
        'port' => 6379, // 端口
        'serialize' => false, // 是否序列化php变量
        'dbName' => 0, // db名
        'auth' => '', // 密码
        'pool' => [
            'min' => 5, // 最小连接数
            'max' => 100 // 最大连接数
        ],
        'errorHandler' => function(){
            return null;
        } // 如果Redis重连失败，会判断errorHandler是否callable，如果是，则会调用，否则会抛出异常，请自行try
    ],
    //连接池
    'POOL_MANAGER' => [
        /*        'App\Utility\MysqlPool2' => [
                    'min' => 5,
                    'max' => 100,
                    'type' => 1
                ],*/
        /*        'App\Utility\RedisPool' => [
                    'min' => 5,
                    'max' => 100,
                    'type' => 1
                ]*/
    ],
    //base url
    //'BASE_URL' => '192.168.10.10:9501/',

    /**
     * 接口频率限制
     */
    'rate_limits'=>[
        'debug' => false,
        //访问频率限制 秒/次数
        'access'=>[
            'expires'=> 60,
            'limit'  => 60,
        ],
    ],
    // 登录相关，次数/分钟
    'auth_limits' => [
        'debug' => false,
        //访问频率限制 秒/次数
        'access'=>[
            'expires'=> 60,
            'limit'  => 30,
        ],
    ],

];