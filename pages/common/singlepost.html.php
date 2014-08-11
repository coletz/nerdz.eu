<?php
if(!isset($hpid))
    die('$hpid required');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

use NERDZ\Core\Messages;

$prj = isset($prj);
$messages = new Messages();

if( empty($hpid) || !($o = $messages->getMessage($hpid, $prj)) )
    die($user->lang('ERROR'));

$user->getTPL()->assign($messages->getPost($o, ['project' => $prj ]));
    
if(isset($draw))
    $user->getTPL()->draw(($prj ? 'project' : 'profile').'/post');
else
    return $user->getTPL()->draw(($prj ? 'project' : 'profile').'/post', true);
?>