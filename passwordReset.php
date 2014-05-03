<?php
require_once 'firebaseLib.php';
$firebase = new Firebase("https://eventified.firebaseio.com/");
$message = "An error occurred.";
function authcmd($act, $params) {
    $params['firebase'] = 'eventified';
    return json_decode(file_get_contents("https://auth.firebase.com/auth/".$act."?&".http_build_query($params)));
}
if(isset($_POST['tok'])) {
    $tok = trim($_POST['tok']);
    $usersJSON = $firebase->get("Eventifi/0/Users");
    $users = json_decode($usersJSON);
    $message = "Invalid token. Make sure you have clicked on the full link.";
    if(strlen($tok) < 39) $users = [];
    foreach($users as $userID=>$user) {
//        echo $user->resetToken." ".$tok."<br>";
        if(isset($user->resetToken) && trim($user->resetToken) == $tok) {
                if($_POST['password1'] != $_POST['password2']) {
                    $message = "The passwords did not match. Go back and try again.";
                    break;
                }
                $pass = $_POST['password1'];
                $firebase->update("Eventifi/0/Users/".$userID, array(
                    "resetToken"=>"false",
                    "password"=>sha1($pass)
                ));

                // update auth db
                authcmd("firebase/update", array(
                    "email"=>$user->email,
                    "oldPassword"=>$user->password,
                    "newPassword"=>sha1($pass)
                ));
                $message = "Your password was successfully changed.";
                
        }
    }
} else if(isset($_GET['tok'])) {
    $tok = trim($_GET['tok']);
    $usersJSON = $firebase->get("Eventifi/0/Users");
    $users = json_decode($usersJSON);
    $message = "Invalid token. Make sure you have clicked on the full link.";
    $usr = null;
    if(strlen($tok) < 39) $users = [];
    foreach($users as $userID=>$user) {
        if(isset($user->resetToken) && trim($user->resetToken) == $tok) {
                $message = "";
                $usr = $user;
                $usremail = $user->email;
                break;
                /*$firebase->update("Eventifi/0/Users/".$userID, array(
                    "resetToken"=>
                ));
                $message = "Your email address (".$user->email.") has been successfully confirmed.";
                */
        }
    }
} else die();
if(isset($message) && strlen($message) > 1) {
        ?>
<div style="position:absolute;top:0;left:0;width:100%;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif">
    <div style="background:#222;width:100%;padding-bottom:5px">
        <img src="http://www.eventifi.co/logo.png" width=134 height=43 />
        <h2 style="display:inline;padding-left:50px;color:white">
            Password Reset
        </h2>
    </div>
    <br/>
    <div style="padding-left:10px">
        <?php echo $message; ?><br />
        <center><button onclick="location.href='.'" style='font-size:32px'>&nbsp;OK&nbsp;</button></center>
    </div>
</div>
    <?php die();
    } ?>
<div style="position:absolute;top:0;left:0;width:100%;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif">
    <div style="background:#222;width:100%;padding-bottom:5px">
        <img src="http://www.eventifi.co/logo.png" width=134 height=43 />
        <h2 style="display:inline;padding-left:50px;color:white">
            Password Reset
        </h2>
    </div>
    <br/>
    <div style="padding-left:10px">
    <form action="passwordReset.php" method="post">
        <input type="hidden" name="tok" value="<?php echo trim($_GET['tok']); ?>" />
        Enter a new password for the account <?php echo $usremail; ?>:<br />
        <table>
        <tr><th>Password:</th><td><input name="password1" type="password" /></td></tr>
        <tr><th>Again:</th><td><input name="password2" type="password" /></td></tr>
        <tr><th colspan=2><input type="submit" value="Change Password" /></th></tr>
        </table>
    </form>
    </div>
</div>
