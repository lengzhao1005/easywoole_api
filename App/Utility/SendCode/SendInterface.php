<?php

namespace App\Utility\SendCode;

interface SendInterface
{
    public function sendCode($to, $code);

    public function sendMessage($to, $message);
}