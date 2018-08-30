<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/3/6
 * Time: 下午5:06
 */

namespace App\HttpController\Curl;


use EasySwoole\Core\Http\AbstractInterface\Controller;
use EasySwoole\Core\Swoole\Coroutine\Client\Http;
use EasySwoole\Core\Utility\Curl\Request;
use EasySwoole\Core\Utility\Random;

class Index extends Controller
{

    function index()
    {
        // TODO: Implement index() method.
        $post = $this->request()->getParsedBody();
        $get = $this->request()->getQueryParams();
        $this->response()->write('success'.$this->request()->getQueryParam('id'));
    }

    function test()
    {
        $req = new Request('http://127.0.0.1:9501/curl/index.html?a=1&b=2');
        $req->setUserOpt([
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>[
                'post1'=>time(),
                'post2'=>Random::randStr(5)
            ]
        ]);
        $content = $req->exec()->getBody();
        var_dump($content);
        $this->response()->write('exec success');
    }



    function sleep()
    {
        $time = intval($this->request()->getRequestParam('time'));

        usleep($time*100000);

        $this->response()->write("sleep {$time}");
    }


    function concurrent()
    {
        //以下流程网络IO的时间就接近于 MAX(q1网络IO时间, q2网络IO时间)。
        $micro = microtime(true);
        $q1 = new Http('http://127.0.0.1:9501/curl/sleep/index.html?time=1');
        $c1 = $q1->exec(true);

        $q2 = new Http('http://127.0.0.1:9501/curl/sleep/index.html?time=4');
        $c2 = $q2->exec(true);

        $c1->recv();
        $c1->close();
        $c2->recv();
        $c2->close();

        var_dump($c1->body);
        var_dump($c2->body);

        $time = round(microtime(true) - $micro,3);
        $this->response()->write($time);

    }

    function concurrent2()
    {
        //以下流程网络IO的时间就接近于 MAX(q1网络IO时间, q2网络IO时间)。
        $micro = microtime(true);

        $ret = [];
        for($i=0;$i<1000;$i++){
            $ret[$i] = (new Http('http://127.0.0.1:9501/curl/index.html?id='.$i))->exec(true);
        }

        for($i=0;$i<1000;$i++){
            $ret[$i]->recv();
            $ret[$i]->close();
            $ret[$i] = $ret[$i]->body;
        }
        var_dump($ret);

        $time = round(microtime(true) - $micro,3);
        $this->response()->write($time);

    }

    function noConcurrent()
    {
        //传统阻塞
        $micro = microtime(true);
        $req = new Request('http://127.0.0.1:9501/curl/sleep/index.html?time=1');
        var_dump($req->exec()->getBody());

        $req2 = new Request('http://127.0.0.1:9501/curl/sleep/index.html?time=4');
        var_dump($req2->exec()->getBody());

        $time = round(microtime(true) - $micro,3);
        $this->response()->write($time);
    }

    function rate()
    {
        $from = 'CNY';
        $tos = [
            '美元' => 'USD',
            '日元' => 'JPY',
            '英镑' => 'GBP',
            '港元' => 'HKD',
            '加元' => 'CAD',
            '欧元' => 'EUR',
            '韩元' => 'KRW',
            '澳元' => 'AUD',
            '瑞士法郎' => 'CHF',
            '新加坡元' => 'SGD',
            '新台币' => 'TWD'
        ];

        $exchange_rate = [];

        foreach ($tos as $k=>$to){
            $url = "https://sp0.baidu.com/8aQDcjqpAAV3otqbppnN2DJv/api.php?query=1CNY".\urlencode('等于多少')."{$to}&co=&resource_id=4278&t=1535599748413&cardId=4278&ie=utf8&oe=gbk&format=json&_=1535599214594";
            $data = \file_get_contents($url);
            $data = \json_decode($data, true);
            $tplData=$data['Result']['0']['DisplayData']['resultData']['tplData'];

            preg_match("/(?<=\=)\d+\.?\d*(?=.+?)/",$tplData['content1'], $converted1);
            preg_match("/(?<=\=)\d+\.?\d*(?=.+?)/",$tplData['content2'], $converted2);
            $exchange_rate['exchange_rate'][$to] = [
                'content1' => $tplData['content1'],
                'content2' => $tplData['content2'],
                'money1_num' => $converted1[0],
                'money2_num' => $converted2[0],
            ];
        }
        $exchange_rate['text'] = $tplData['text'];
var_dump($exchange_rate);
        $this->response()->write(\json_encode($exchange_rate, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

}