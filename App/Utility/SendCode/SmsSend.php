<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/8/31
 * Time: 14:26
 */

namespace App\Utility\SendCode;


use EasySwoole\Core\AbstractInterface\Singleton;

class SmsSend implements SendInterface
{
    use Singleton;

    public function sendCode($to, $message)
    {

    }

    public function sendMessage($to, $message)
    {
        return true;
    }
}