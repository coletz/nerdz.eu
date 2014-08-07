<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
use NERDZ\Core\Core;

$core = new Core();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

if(!$core->refererControl())
    die($core->jsonResponse('error',$core->lang('ERROR').': referer'));

$hpid  = isset($_POST['hpid'])  && is_numeric($_POST['hpid'])  ? $_POST['hpid']  : false;

if(!$hpid)
    die($core->jsonResponse('error',$core->lang('ERROR')));

$from = $_SESSION['id'];
$table = (isset($prj) ? 'groups_' : '').'bookmarks';

switch(isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '')
{
    case 'add':
        if(Db::NO_ERRNO != Db::query(
                [
                    'INSERT INTO "'.$table.'"("from","hpid")
                     SELECT :from, :hpid
                     WHERE NOT EXISTS (SELECT 1 FROM "'.$table.'" WHERE "from" = :from AND "hpid" = :hpid)',
                    [
                        ':from' => $from,
                        ':hpid' => $hpid
                    ]
                ],Db::FETCH_ERRNO))
            die($core->jsonResponse('error',$core->lang('ERROR')));    
    break;
    case 'del':
        if(Db::NO_ERRNO != Db::query(
                [
                    'DELETE FROM "'.$table.'" WHERE "from" = :from AND "hpid" = :hpid',
                    [
                        ':from' => $from, 
                        ':hpid' => $hpid
                    ]
                ],Db::FETCH_ERRNO))
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    default:
        die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
}
die($core->jsonResponse('ok','OK'));
?>
