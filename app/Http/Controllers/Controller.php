<?php

namespace App\Http\Controllers;

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

use DB;

abstract class Controller
{
    protected $token_secret = "social-network-token-secret";
    protected $admin_token_secret = "social-network-admin-token-secret";
    // protected $user_session_key = "social-network-user-session";
    // protected $admin_session_key = "social-network-admin-session";

    protected function relative_time($seconds)
    {
        // Determine the relative time string
        if ($seconds < 60) {
            return $seconds . ' seconds';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return $minutes . ' minutes';
        } elseif ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            return $hours . ' hours';
        } elseif ($seconds < 604800) {
            $days = floor($seconds / 86400);
            return $days . ' days';
        } elseif ($seconds < 2419200) {
            $weeks = floor($seconds / 604800);
            return $weeks . ' weeks';
        } elseif ($seconds < 29030400) { // Approximate number of seconds in a month
            $months = floor($seconds / 2419200);
            return $months . ' months';
        } else {
            $years = floor($seconds / 29030400);
            return $years . ' years';
        }
    }

    protected function capitalize($str)
    {
        $parts = explode(" ", $str);
        foreach ($parts as $key => $value)
        {
            $parts[$key] = ucfirst($value);
        }
        return implode(" ", $parts);
    }

    protected function admin_auth()
    {
        if (!auth()->check())
        {
            return response()->json([
                "status" => "error",
                "message" => "Not logged-in."
            ])->throwResponse();
        }

        if (auth()->user()->type != "super_admin")
        {
            return response()->json([
                "status" => "error",
                "message" => "Un-authorized."
            ])->throwResponse();
        }
    }

    protected function send_mail($to, $to_name, $subject, $body)
    {
        //Create an instance; passing `true` enables exceptions
        $mail = new PHPMailer(true);

        $settings = DB::table("settings")->get();
        if (count($settings) <= 0)
        {
            return "SMTP configurations not set.";
        }

        $settings_obj = new \stdClass();
        foreach ($settings as $setting)
        {
            $settings_obj->{$setting->key} = $setting->value;
        }

        try
        {
            //Server settings
            $mail->SMTPDebug = 0; // SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = $settings_obj->smtp_host;                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = $settings_obj->smtp_username;                     //SMTP username
            $mail->Password   = $settings_obj->smtp_password;                               //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port       = $settings_obj->smtp_port;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom($settings_obj->smtp_from, $settings_obj->smtp_from_name);
            $mail->addAddress($to, $to_name);     //Add a recipient

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $body;

            $mail->send();
            return "";
            // echo 'Message has been sent';
        }
        catch (Exception $e)
        {
            return $mail->ErrorInfo;
            // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
