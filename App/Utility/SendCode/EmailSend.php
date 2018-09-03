<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/8/31
 * Time: 14:26
 */

namespace App\Utility\SendCode;


use EasySwoole\Core\AbstractInterface\Singleton;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class EmailSend implements SendInterface
{
    use Singleton;

    public function sendCode($to, $code)
    {
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = 'smtp.qq.com';
            $mail->SMTPAuth = true;
            //smtp登录的账号 这里填入字符串格式的qq号即可
            $mail->Username = '1968912619@qq.com';
            //smtp登录的密码 使用生成的授权码（通过qq邮箱活得的授权码）
            $mail->Password = 'xifwrvtjsmhxchag';
            //设置使用ssl加密方式登录鉴权
            $mail->SMTPSecure = 'ssl';
            //设置ssl连接smtp服务器的远程服务器端口号，以前的默认是25，但是现在新的好像已经不可用了 可选465或587
            $mail->Port = 465;

            //Recipients
            $mail->setFrom('1968912619@qq.com', 'LZ');
            //设置收件人邮箱地址 该方法有两个参数 第一个参数为收件人邮箱地址
            // 第二参数为给该地址设置的昵称 不同的邮箱系统会自动进行处理变动 这里第二个参数的意义不大
            $mail->addAddress($to, '');
            //Content
            $mail->isHTML(true);
            $mail->Subject = '兴业管理工具';
            $mail->Body    =
<<<EOF
    <p>您的验证码是：<strong>$code</strong></p>

    <p>该验证码五分钟内有效</p>
EOF;
            return $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
            return false;
        }
    }

    public function sendMessage($to, $message)
    {
        return true;
    }
}