<?php
require_once 'firebaseLib.php';
$firebase = new Firebase("https://eventified.firebaseio.com/");

function authcmd($act, $params) {
    $params['firebase'] = 'eventified';
    return json_decode(file_get_contents("https://auth.firebase.com/auth/".$act."?&".http_build_query($params)));
}

$act = (isset($_POST['act']) ? $_POST['act'] : $_GET['act']);
if($act == "register") {
    $user = array(
        "email"=>$_POST['email'],
        "name"=>$_POST['name'],
        "password"=>sha1($_POST['password']),
        "eventsHosting"=>[],
        "eventsAttending"=>[]
    );
    print_r($user);
    // Add to the local user DB
    $push = $firebase->push("Eventifi/0/Users", $user);
    // Add to the remote auth DB
    $auth = authcmd("firebase/create", array(
        "email"=>$user['email'],
        "password"=>$user['password']
    ));
    print_r($push);
} elseif($act == "auth") {
    echo json_encode(authcmd("firebase", array(
        "email"=>$_POST['email'],
        "password"=>sha1($_POST['password'])
    )));
} elseif($act == "getusers") {
    $users = $firebase->get("Eventifi/0/Users");
    print_r($users);
} elseif($act == "sha") {
    die(sha1($_REQUEST['password']));
}
?>
