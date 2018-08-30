<?php

namespace  App\Utility;

class Helper
{
    public static function create_uuid($withSep = false)
    {
        $str = md5(uniqid(mt_rand(), true));
        $uuid  = substr($str,0,8) . ($withSep? '-' : '');
        $uuid .= substr($str,8,4) . ($withSep? '-' : '');
        $uuid .= substr($str,12,4) . ($withSep? '-' : '');
        $uuid .= substr($str,16,4) . ($withSep? '-' : '');
        $uuid .= substr($str,20,12);
        return $uuid;
    }
}