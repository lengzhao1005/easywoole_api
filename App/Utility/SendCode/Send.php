<?php

namespace App\Utility\SendCode;

use EasySwoole\Core\AbstractInterface\Singleton;

class Send
{
    use Singleton;

    private $handle;
    protected $to;
    protected $content;

    public function __construct(SendInterface $handle)
    {
        $this->handle = $handle;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setTo($to)
    {
        $this->to = $to;
    }

    public function getTo()
    {
        return $this->content;
    }

    public function sendCode($to, $code)
    {
        return $this->handle->sendCode($to, $code);
    }

    public function sendMessage($to, $message)
    {
        return $this->handle->sendMessage($to, $message);
    }
}