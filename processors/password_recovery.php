<?php

header('Content-Type: application/json');
session_start();
function isValidRequest()
{
    $request_method = $_SERVER['REQUEST_METHOD'];
    $expected_request_method = 'POST';
    if ($request_method != $expected_request_method) {
        return false;

    }


    exit("Request failed");

}






require_once '../security/config.php';
require_once '../security/database.php'; // Required for Necessary Database Connections.
require_once '../phpmailer/PHPMailerAutoload.php';


class PasswordRecovery extends DatabaseConnection
{

    private $email , $verification_token , $date , $email_not_found_error = "e-mail address not found", $user_id , $data,
    $user_profile , $error , $email_not_sent_error = "couldn't send recovery email, please check your network connection"
    , $email_sent_error = "password recovery email sent to " , $connection_error = "connection failed , try checking back later";
    function __construct()
    {

        parent::__construct();

    }


    function __destruct()
    {
        parent::__destruct(); // TODO: Change the autogenerated stub
    }

    private function  isReady() : bool {


        if(isset($_POST["data"]))
            return true;
        return false;



    }


    public function setDetails() : bool{
        $this->data = json_decode($_POST["data"] , true);
        $this->email = $this->data["email"];
        //$this->user_profile = $this->get_user_profile()[0];
        //$this->user_id = $this->user_profile["user_id"];
        $time = time();

        $this->date = date('d-m-Y' , $time);
        $this->verification_token = $this->set_verification_token();
        return true;
    }


    public function  email_exists_in_password_recovery_table() : bool {

        if($this->record_exists_in_table($this->password_recovery_table_name , "email_address" , $this->email ))
            return true;
        return false;
}

    public function is_valid_email_address() : bool  {
        $email = $this->email;
        if($this->record_exists_in_table($this->users_table_name , "email_address" , $email ))
            return true;
        return false;
    }

    public function  delete_existing_email_address_from_password_recovery () : bool  {

        if($this->delete_record($this->password_recovery_table_name , "email_address" , $this->email))
            return true;
        return false;
    }
    public function set_verification_token() : string {

        global  $email_verification_code_length /*Check config.php */;
        $verification_token = bin2hex(openssl_random_pseudo_bytes($email_verification_code_length));
        if($this->record_exists_in_table($this->password_recovery_table_name , "token" , $verification_token))
            $this->set_verification_token();
        return $verification_token;

    }
    public function get_user_profile(): array
    {
         $email = $this->email;
        $password_recovery_table = $this->password_recovery_table_name;
        $users_table_name = $this->users_table_name;
        $sql = "SELECT * FROM {$users_table_name} WHERE  email_address = '{$email}'";
        $result = $this->conn->prepare($sql);
        $result->execute();
        $set_type_record = $result->setFetchMode(PDO::FETCH_ASSOC);
        $record = $result->fetchAll();
        return $record;
    }


    public function  insert_into_password_recovery_table () : bool  {
         $email = $this->email;
         $token = $this->verification_token;
         $user_id = $this->user_id;
         $date = $this->date;
         $password_recovery_table_name = $this->password_recovery_table_name;
         $sql = "INSERT INTO {$password_recovery_table_name}(user_id , email_address , date_created , token) VALUES ('{$user_id}' , '{$email}' , '{$date}' , '{$token}')";
         try {


            $this->conn->exec($sql);
            return true;
        }

        catch (PDOException $exception) {

            echo $exception->getMessage();
            return false;
        }



    }


    public function send_account_recovery_email () {

         $mail = new PHPMailer;
         global  $home_page_site_url , $countries , $selected_country  , $image_folder , $home_page_site_name;

         $site_name = strtolower($home_page_site_url);
         $address = $countries[$selected_country]['head_office'];
         $username = ucfirst($this->user_profile["username"]);
         $firstname = ucfirst($this->user_profile["first_name"]);
         $lastname = ucfirst($this->user_profile["last_name"]);
//$mail->SMTPDebug = 3;
        $email = $this->email;
        $home_page_site_name = ucfirst($home_page_site_name);
        $logo_image = $image_folder."fav.png";

        $verification_token = $this->verification_token;
        $time = time();
        $date = date('d-m-Y' , $time);


        // include  '../emails/basic_emails/verification_email.php';
        // include  '../emails/html_emails/verification_email.php';
        $time_string = date('h:i:s a' , $time);


        $html_email_body = <<<HTML_EMAIL_BODY

<!DOCTYPE html>
<html lang = 'en-us'>
<head>
<style type='text/css'>
#mail-logo-image {
width : 16px;
height : 16px;
position:relative;
left : 93%;

}

#email-text , #not-user-message{
font-family: 'Helvetica Neue Light',Helvetica,Arial,sans-serif;
font-size: 16px;
padding: 0px;
margin: 0px;
font-weight: normal;
line-height: 22px;
color : #222;
margin-top : 20px;
}


body {

background-color: #f5f8fa;
margin: 0;
padding: 0;

}
#final-step {

color : #222;
font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
font-size:24px;
padding:0px;
margin:0px;
font-weight:bold;
line-height:32px;
}

#date {

  display : none;
}


@media only screen and (max-width: 600px) {

#main-mail-container-div {

width : 80%;
}


}

#container{
background-color : #e1e8ed;
}

#main-mail-container-div {

margin-left : auto;
margin-right: auto;
background-color : #fff;
width : 420px;
padding : 15px;
}
#confirmation-link{

font-size: 16px;
font-family: 'HelveticaNeue','Helvetica Neue',Helvetica,Arial,sans-serif;
color: #ffffff;
text-decoration: none;
border-radius: 4px;
padding: 11px 30px;
border: 1px solid #1da1f2;
display: inline-block;
font-weight: bold;
background-color: rgb(29, 161, 242);
margin-top : 20px;
}

#not-user-message {

font-size: 12px;


}

#site-address {
color: #8899a6;

font-family: 'Helvetica Neue Light',Helvetica,Arial,sans-serif;

font-size: 12px;

font-weight: normal;

line-height: 16px;

text-align: center;
width : 180px;
margin : auto;
}

</style>
</head>

<body>
<div id ='container'>
<div id = 'main-mail-container-div'>

<!--<a href = "http://$home_page_site_url" title='$home_page_site_name'><img src="/{$logo_image}" alt='' id = 'mail-logo-image' /></a>-->
<p id = 'final-step'>Password recovery...</p>
<p id = 'email-text'>

Hi - $firstname $lastname<br /> you recently requested for a password reset for your $home_page_site_name account,
with the email address ($email). Please click the link below to reset you password.

</p>

<a href = "https://$home_page_site_url/resetPassword/{$verification_token}" title='Password reset link' id = 'confirmation-link'>Reset password</a>

<p id = 'not-user-message'>

This email was automatically sent to you by $site_name.Please do not reply to this email. If you have any question, do not hesistate to <a href="https://$site_name/contact">contact us.</a>  Thank you.
</p>
<p id = 'site-address'>
$home_page_site_name International ﻿Company.
$address
</p>
<br />
<span id = "date">$time_string</span>;

</div>


</div>

</body>

</html>



HTML_EMAIL_BODY;
        $home_page_site_url = strtolower($home_page_site_url);
        $basic_email_body = <<<BASIC_EMAIL_BODY

<!DOCTYPE html>
<html lang='en-us' dir='ltr'>
<body>

Dear $firstname $lastname,<br /><br />

You recently requested for a password reset,please click the link below to reset your <a href="https://$home_page_site_url">$site_name</a> account.<br /><br />
 Click on the link below to reset your account password.
<br />
<br/>
<strong>Password reset  Link</strong><br>
<a href ="https://$home_page_site_url/resetPassword/{$verification_token}" title = 'Reset account password'>https://$home_page_site_url/resetPassword/$verification_token</a>

<br /><br />

This email was automatically sent to you by $home_page_site_name.Please do not reply to this email.
If you have any question, do not hesistate to <a href="https://$home_page_site_url/contact">contact us</a>.<br />
Thank you.<br /><br />



The $site_name Team <br /> <br />
<span style="display: none;">$time_string</span>
</body>
</html>

BASIC_EMAIL_BODY;


        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = SITE_CONFIGURATIONS["PRIMARY_EMAIL_SERVER"];  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;
//$mail->SMTPDebug = 3;                              // Enable SMTP authentication
        $mail->Username = SITE_CONFIGURATIONS["PRIMARY_EMAIL"];                 // SMTP username
        $mail->Password = SITE_CONFIGURATIONS["PRIMARY_EMAIL_PASSWORD"];                           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;                                    // TCP port to connect to
        $site_author = SITE_CONFIGURATIONS['SITE_AUTHOR'];
        $primary_email = SITE_CONFIGURATIONS['PRIMARY_EMAIL'];
        $primary_email_server = SITE_CONFIGURATIONS['PRIMARY_EMAIL_SERVER'];
        $primary_email_password = SITE_CONFIGURATIONS['PRIMARY_EMAIL_PASSWORD'];

        try {
            $mail->setFrom($primary_email_server, "{$site_author} From {$site_name}");
        } catch (phpmailerException $e) {
            //  echo $e->getMessage();
            return false;
        }
        $fullname = $firstname." ".$lastname;

        $mail->addAddress($email, $fullname);     // Add a recipient
//$mail->addAddress('ellen@example.com');               // Name is optional
        $mail->addReplyTo($primary_email, $site_name);

// $mail->addCC('cc@example.com');
//$mail->addBCC('bcc@example.com');

//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = "Reset your $site_name password, $fullname";
        $mail->Body = $html_email_body;
        $mail->AltBody = $basic_email_body;
        if(!$mail->send()) {
           //echo 'Mailer Error: ' . $mail->ErrorInfo;
            return false;
        } else {
            return  true;
        }

    }

    public function Processor()  {

        if($this->isReady()) {

            $this->setDetails();
            if($this->is_valid_email_address()) {
                $this->user_profile = $this->get_user_profile()[0];
                $this->user_id = $this->user_profile["user_id"];

                if($this->email_exists_in_password_recovery_table()){
                $this->delete_existing_email_address_from_password_recovery();
            }

            if($this->insert_into_password_recovery_table()){

                          if($this->send_account_recovery_email()){

                              $this->error = Array("success" => 1 , "error" => "{$this->email_sent_error}<strong>{$this->email}</strong>");
								$this->error = json_encode($this->error);
								return $this->error;
                          }


                          else {


                              $this->error = Array("success" => 0 , "error" => $this->connection_error);
                              $this->error = json_encode($this->error);
                              return $this->error;
                          }
            }

            else {
                $this->error = Array("success" => 0 , "error" => $this->email_not_sent_error);
                $this->error = json_encode($this->error);
                return $this->error;
            }



        }
        else {

            $this->error = Array("success" => 0 , "error" => $this->email_not_found_error);
            $this->error = json_encode($this->error);
            return $this->error;
        }
    }

        }






}

$password_recovery = new PasswordRecovery();
echo $password_recovery->Processor();
?>
