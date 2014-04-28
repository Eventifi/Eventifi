<?php
require_once 'firebaseLib.php';
$firebase = new Firebase("https://eventified.firebaseio.com/");

$act = (isset($_POST['act']) ? $_POST['act'] : $_GET['act']);
if($act == "register") {
    $user = array(
        "email"=>$_POST['email'],
        "name"=>$_POST['name'],
        "password"=>$_POST['password'],
        "eventsHosting"=>[],
        "eventsAttending"=>[]
    );
    print_r($user);
    $push = $firebase->push("Eventifi/0/Users", $user);
    print_r($push);
} elseif($act == "sha") {
    die(sha1($_REQUEST['password']));
}
?>
