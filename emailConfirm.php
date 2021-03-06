<?php
require_once 'firebaseLib.php';
$firebase = new Firebase("https://eventified.firebaseio.com/");

if(isset($_GET['tok'])) {
    $tok = trim($_GET['tok']);
    $usersJSON = $firebase->get("Eventifi/0/Users");
    $users = json_decode($usersJSON);
    $message = "Invalid token. Make sure you have clicked on the full link.";
    if(strlen($tok) < 39) $users = [];
    foreach($users as $userID=>$user) {
        if(isset($user->confirmToken) && trim($user->confirmToken) == $tok) {
            if(isset($_GET['unconfirm'])) {
                $user->emailConfirmed = false;
                die();
            }
            if(isset($user->emailConfirmed) && $user->emailConfirmed == true) {
                $message = "You have already confirmed this address.";
                break;
            } else {
                $firebase->update("Eventifi/0/Users/".$userID, array(
                    "emailConfirmed"=>true
                ));
                $message = "Your email address (".$user->email.") has been successfully confirmed.";
            }
        }
    }
}
if(isset($_GET['img'])) {
    error_reporting(0);
    $tok = trim($_GET['img']);
    $usersJSON = $firebase->get("Eventifi/0/Users");
    $users = json_decode($usersJSON);
    $message = "Invalid token. Make sure you have clicked on the full link.";
    foreach($users as $userID=>$user) {
        if(isset($user->confirmToken) && trim($user->confirmToken) == $tok) {
            if(isset($_GET['unconfirm'])) {
                $user->emailSeen = false;
            } else {
                $firebase->update("Eventifi/0/Users/".$userID, array(
                    "emailSeen"=>true
                ));
            }
        }
    }
    require_once "logo.png";
    header("Content-type: image/png");
    die();
}
?>
<div style="position:absolute;top:0;left:0;width:100%;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif">
    <div style="background:#222;width:100%;padding-bottom:5px">
        <img src="http://www.eventifi.co/logo.png" width=134 height=43 />
        <h2 style="display:inline;padding-left:50px;color:white">
            Email Confirmation
        </h2>
    </div>
    <br/>
    <div style="padding-left:10px">
        <?php echo $message; ?><br />
        <center><button onclick="location.href='.'" style='font-size:32px'>&nbsp;OK&nbsp;</button></center>
    </div>
</div>
