<?php
session_start();
define('MAILGUN_SECRET_KEY', $_ENV['MAILGUN_SECRET']);
if(isset($_GET['debug'])) {
error_reporting(E_ALL);
ini_set("display_errors", 1);
}
require_once 'firebaseLib.php';
$firebase = new Firebase("https://eventified.firebaseio.com/");

function authcmd($act, $params) {
    $params['firebase'] = 'eventified';
    return json_decode(file_get_contents("https://auth.firebase.com/auth/".$act."?&".http_build_query($params)));
}

function generateToken() {
    return sha1(time());
}

$emailTemplate = <<<EOF
<div style="position:absolute;top:0;left:0;width:100%;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif">
    <div style="background:#222;width:100%;padding-bottom:5px">
        <img src="http://www.eventifi.co/logo.png" width=134 height=43 />
        <h2 style="display:inline;padding-left:50px;color:white">
            {{title}}
        </h2>
    </div>
    <br/>
    <div style="padding-left:10px">
        {{content}}
    </div>
</div>
EOF;
function sendmail($to, $title, $msg, $from, $headers) {
$ch = curl_init();

curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, 'api:'.MAILGUN_SECRET_KEY);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v2/sandboxbe99f4f2f16142c18000a86a62a6f14b.mailgun.org/messages');
curl_setopt($ch, CURLOPT_POSTFIELDS, array('from' => $from, //'postmaster@sandboxbe99f4f2f16142c18000a86a62a6f14b.mailgun.org',
'to' => $to,
'subject' => $title,
'html' => $msg));
curl_setopt($ch, CURLOPT_HTTPHEADER, explode("\r\n",$headers));
$result = curl_exec($ch);
curl_close($ch); 
}
function sendEmail($email, $title, $content, $messageattrs) {
    global $emailTemplate;
    $message = str_replace("{{content}}", $content, $emailTemplate);
    $message = str_replace("{{title}}", $title, $message);
    $message = str_replace(array_keys($messageattrs), array_values($messageattrs), $message);
    sendmail($email, $title, $message, "email-confirm@noreply.eventifi.co", "");
}
function sendTempEmail($data) {
    $req = file_get_contents("http://eventifiapp.comeze.com/eventifi_email_send.php?".http_build_query($data));
}
function sendEmailConfirm($user) {
    sendEmail(
        $user['email'], 
        "Eventifi Email Confirmation", 
        "<p>Hello there, someone by the name of {{name}} created an account on Eventifi with this email address. To confirm this registration, click on the following link:<br/><center><a href='{{url}}' style='font-size:32px'>Confirm</a></center>",
        array(
            "{{name}}"=>$user['name'],
            "{{url}}"=>"http://www.eventifi.co/emailConfirm.php?tok=".$user['confirmToken']
        )
    );
}

function sendReset($user) {
    sendEmail(
        $user['email'],
        "Eventifi Password Reset",
        "<p>Hello there, we have recieved a request to reset the password for your account, {{email}}, on Eventifi. To confirm this reset and choose a new password, click on the following link:<br/><center><a href='{{url}}' style='font-size: 32px'>Confirm</a></center>",
        array(
            "{{email}}"=>$user['email'],
            "{{url}}"=>"http://www.eventifi.co/passwordReset.php?tok=".$user['resetToken']
        )
    );
}

function getUser($info) {
    global $firebase;
    $users = $firebase->get("Eventifi/0/Users");
    $users = json_decode($users);
    foreach($users as $uid=>$user) {
        foreach($info as $field=>$inf) {
            if($user->$field == $inf) {
                $user->userid = $uid;
                return $user;
            }
        }
    }
    return false;
}

$act = (isset($_POST['act']) ? $_POST['act'] : $_GET['act']);
if($act == "register") {
    $pwtoken = generateToken();
    $user = array(
        "email"=>$_POST['email'],
        "name"=>$_POST['name'],
        "password"=>sha1($_POST['password']),
        "eventsHosting"=>array(),
        "eventsAttending"=>array(),
        "confirmToken"=>$pwtoken,
        "emailConfirmed"=>false,
        "resetToken"=>"false"
    );
    // Do they exist already?
    $dup = getUser(array("email"=>$user['email']));
    if($dup != false) {
        die(json_encode(array("error"=>array("code"=>"ERROR", "message"=>"That user already exists."))));
    }
    //print_r($user);
    // Add to the local user DB
    $push = $firebase->push("Eventifi/0/Users", $user);
    // Add to the remote auth DB
    $auth = authcmd("firebase/create", array(
        "email"=>$user['email'],
        "password"=>$user['password']
    ));
    sendEmailConfirm($user);
    die(json_encode(array("success"=>array("code"=>"VERIFY", "message"=>"Registration successful. You need to verify your email address before you can log in. Click the link in the confirmation message that has been sent."))));
} elseif($act == "auth") {
    $user = getUser(array("email"=>$_POST['email']));
    if($user == false) {
        // They don't exist
        die(json_encode(array("error"=>array("code"=>"ERROR", "message"=>"Invalid username or password."))));
    }
    if(!isset($user->emailConfirmed) || !$user->emailConfirmed) {
        die(json_encode(array("error"=>array("code"=>"VERIFY", "message"=>"You need to verify your email address before you can log in. Click the link in the confirmation message that has been sent."))));
    }
    $auth = json_encode(authcmd("firebase", array(
        "email"=>$_POST['email'],
        "password"=>sha1($_POST['password'])
    )));
    die($auth);
} elseif($act == "resetpass") {
    $user = getUser(array("email"=>$_POST['email']));
    if($user == false) {
        // They don't exist, but don't let them know that
        die(json_encode(array("success"=>array("message"=>"Successfully sent an email.","email"=>$user->email))));
    }
    $tok = generateToken();
    $firebase->push("Eventifi/0/Users/".$user->userid, array(
        "resetToken" => $tok
    ));
    sendReset(array(
        "email"=>$user->email,
        "resetToken"=>$tok
    ));
    die(json_encode(array("success"=>array("message"=>"Successfully sent an email.","email"=>$user->email))));
} elseif($act == "sha") {
    die(sha1($_REQUEST['password']));
}
elseif($act == "get") die(print_r(getUser($_GET),1));
?>
