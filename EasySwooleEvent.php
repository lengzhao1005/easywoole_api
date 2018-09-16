<?php

namespace EasySwoole;

use App\Utility\SysConst;
use EasySwoole\Core\Swoole\EventHelper;
use Illuminate\Database\Capsule\Manager as Capsule;
use \EasySwoole\Core\AbstractInterface\EventInterface;
use \EasySwoole\Core\Swoole\ServerManager;
use \EasySwoole\Core\Swoole\EventRegister;
use \EasySwoole\Core\Http\Request;
use \EasySwoole\Core\Http\Response;

Class EasySwooleEvent implements EventInterface {

    public static function frameInitialize(): void
    {
        date_default_timezone_set('Asia/Shanghai');

        // 初始化数据库
        $dbConf = Config::getInstance()->getConf('database');
        $capsule = new Capsule;
        // 创建链接
        $capsule->addConnection($dbConf);
        // 设置全局静态可访问
        $capsule->setAsGlobal();
        // 启动Eloquent
        $capsule->bootEloquent();
    }


    public static function mainServerCreate(ServerManager $server,EventRegister $register): void
    {
        EventHelper::registerDefaultOnMessage($register,\App\Parser::class);

        $register->add($register::onClose, function ($ser, $fd) {
            var_dump('close_event:'.$fd);
        });

        $register->add($register::onOpen, function ($ser, $req) {
            var_dump($req->fd);
        });

        // 自定义WS握手处理 可以实现在握手的时候 鉴定用户身份
        // @see https://wiki.swoole.com/wiki/page/409.html
        // ------------------------------------------------------------------------------------------
        $register->add($register::onHandShake, function (\swoole_http_request $request, \swoole_http_response $response) {
            if (isset($request->cookie[SysConst::COOKIE_USER_SESSION_NAME])) {
                $token = $request->cookie['token'];
                if ($token == '123') {
                    // 如果取得 token 并且验证通过 则进入 ws rfc 规范中约定的验证过程
                    if (!isset($request->header['sec-websocket-key'])) {
                        // 需要 Sec-WebSocket-Key 如果没有拒绝握手
                        var_dump('shake fai1 3');
                        $response->end();
                        return false;
                    }
                    if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $request->header['sec-websocket-key'])
                        || 16 !== strlen(base64_decode($request->header['sec-websocket-key']))
                    ) {
                        //不接受握手
                        var_dump('shake fai1 4');
                        $response->end();
                        return false;
                    }

                    $key = base64_encode(sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
                    $headers = array(
                        'Upgrade'               => 'websocket',
                        'Connection'            => 'Upgrade',
                        'Sec-WebSocket-Accept'  => $key,
                        'Sec-WebSocket-Version' => '13',
                        'KeepAlive'             => 'off',
                    );
                    foreach ($headers as $key => $val) {
                        $response->header($key, $val);
                    }
                    //接受握手  发送验证后的header   还需要101状态码以切换状态
                    $response->status(101);
                    var_dump('shake success at fd :' . $request->fd);
                    $response->end();
                } else {
                    // 令牌不正确的情况 不接受握手
                    var_dump('shake fail 2');
                    $response->end();
                    return false;
                }
            } else {
                // 没有携带令牌的情况 不接受握手
                var_dump('shake fai1 1');
                $response->end();
                return false;
            }
        });
    }

    public static function onRequest(Request $request,Response $response): void
    {
        // TODO: Implement onRequest() method.
    }

    public static function afterAction(Request $request,Response $response): void
    {
        // TODO: Implement afterAction() method.
    }
}