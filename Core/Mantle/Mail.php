<?php
namespace Clive\Core\Mantle;

class Mail{
   public static $welcomeTemplate = <<<MAIL
   <h1>Welcome to Clive @{username}, </h1>
   <p>In order for you to continue please <a href="{host}/login" target="_blank" rel="noopener noreferrer">log in</a> with
   </p>
   <br>
   <p>Username : {username}</p>
   <p>Password : {pass}</p>
   <br>
   <br>
   <p>And please don't forget to reset your password!</p>
   <br>
   <br>
   <p>Kind Regards,</p>
   <p>The Clive Team,</p>
   <p>www.clive.com</p>
MAIL;

    public static $from = "admin@clive.com";
    public static $to;
    public static $subject;


    public static function message(){
        //
    }

    public static function send($msg){ 

        $headers = 'From: Admin <'.self::$from.'>' . PHP_EOL .'Reply-To: Admin <'.self::$from.'>'. PHP_EOL;
        $headers .= "MIME-Version: 1.0". PHP_EOL ."Content-Type: text/html; charset=ISO-8859-1".PHP_EOL;
        if(mail(self::$to,self::$subject,$msg, $headers)) {
            Logger::log("INFO: Mail about ".self::$subject ." was  sent to ".self::$to);
            echo "The email message was sent.";
        } else {
            Logger::log("ERROR: Could not send the email");
            echo "The email message was not sent.";
        }
    }
}

// Mail::$subject = "A test";
// Mail::$to = "newuser@clive.com";
// Mail::send("Welcome to Clive");

