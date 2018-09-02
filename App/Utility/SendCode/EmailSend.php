<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/8/31
 * Time: 14:26
 */

namespace App\Utility\SendCode;


use EasySwoole\Core\AbstractInterface\Singleton;

class EmailSend implements SendInterface
{
    use Singleton;

    public function sendCode($to, $code)
    {

    }

    public function sendMessage($to, $message)
    {
        return true;
    }
}