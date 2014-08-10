<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
use NERDZ\Core\Utils;
use NERDZ\Core\User;
$user = new User();

if(!$user->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': referer'));
    
if(!$user->csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0,'edit'))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': token'));
    
if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));
    
$user['interests']  = isset($_POST['interests'])  ? trim($_POST['interests'])               : '';
$user['biography']  = isset($_POST['biography'])  ? trim($_POST['biography'])               : '';
$user['quotes']     = isset($_POST['quotes'])     ? trim($_POST['quotes'])                  : '';
$user['website']    = isset($_POST['website'])    ? strip_tags(trim($_POST['website']))     : '';
$user['jabber']     = isset($_POST['jabber'])     ? trim($_POST['jabber'])                  : '';
$user['yahoo']      = isset($_POST['yahoo'])      ? trim($_POST['yahoo'])                   : '';
$user['facebook']   = isset($_POST['facebook'])   ? trim($_POST['facebook'])                : '';
$user['twitter']    = isset($_POST['twitter'])    ? trim($_POST['twitter'])                 : '';
$user['steam']      = isset($_POST['steam'])      ? trim($_POST['steam'])                   : '';
$user['skype']      = isset($_POST['skype'])      ? trim($_POST['skype'])                   : '';
$user['github']     = isset($_POST['github'])     ? trim($_POST['github'])                  : '';
$user['userscript'] = isset($_POST['userscript']) ? strip_tags(trim($_POST['userscript']))  : '';
$user['dateformat'] = isset($_POST['dateformat']) ? trim($_POST['dateformat'])              : '';
$closed             = isset($_POST['closed']);
$flag = true;

if(!empty($user['website']) && !Utils::isValidURL($user['website']))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WEBSITE').': '.$user->lang('INVALID_URL')));
    
if(!empty($user['userscript']) && !Utils::isValidURL($user['userscript']))
    die(NERDZ\Core\Utils::jsonResponse('error','Userscript: '.$user->lang('INVALID_URL')));

if(!empty($user['github']) && !preg_match('#^https?://(www\.)?github\.com/[a-z0-9]+$#i',$user['github']))
    die(NERDZ\Core\Utils::jsonResponse('error','GitHub: '.$user->lang('INVALID_URL')));

if(false == ($obj = $user->getObject($_SESSION['id'])))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    
if(!empty($user['jabber']) && (false == filter_var($user['jabber'],FILTER_VALIDATE_EMAIL)))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('JABBER').': '.$user->lang('MAIL_NOT_VALID')));
    
if(empty($user['dateformat']))
    $user['dateformat'] = 'd/m/Y, H:i';

if(!empty($user['facebook']) &&
        ( !preg_match('#^https?://(([a-z]{2}\-[a-z]{2})|www)\.facebook\.com/people/[^/]+/([a-z0-9_\-]+)#i',$user['facebook']) &&
          !preg_match('#^https?://(([a-z]{2}\-[a-z]{2})|www)\.facebook\.com/profile\.php\?id\=([0-9]+)#i',$user['facebook']) &&
          !preg_match('#^https?://(([a-z]{2}\-[a-z]{2})|www)\.facebook\.com/([a-z0-9_\-\.]+)#i',$user['facebook'])
        )
  )
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': Facebook URL'));


if(!empty($user['twitter']) && !preg_match('#^https?://twitter.com/([a-z0-9_]+)#i',$user['twitter']))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': Twitter URL'));

if(!empty($user['steam']) && strlen($user['steam']) > 35)
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': Steam'));
    
foreach($user as &$value)
    $value = htmlspecialchars($value,ENT_QUOTES,'UTF-8');

$par = [
    ':interests' => $user['interests'],
     ':biography' => $user['biography'],
     ':quotes'  => $user['quotes'],
     ':website' => $user['website'],
     ':dateformat' => $user['dateformat'],
     ':github' => $user['github'],
     ':jabber' => $user['jabber'],
     ':yahoo' => $user['yahoo'],
     ':userscript' => $user['userscript'],
     ':facebook' => $user['facebook'],
     ':twitter' => $user['twitter'],
     ':steam' => $user['steam'],
     ':skype' => $user['skype'],
     ':counter' => $obj->counter
];
    
if(
    Db::NO_ERRNO != Db::query(
        [
            'UPDATE profiles SET 
            "interests"   = :interests,
            "biography"   = :biography,
            "quotes"      = :quotes,
            "website"     = :website,
            "dateformat"  = :dateformat,
            "github"      = :github,
            "jabber"      = :jabber,
            "yahoo"       = :yahoo,
            "userscript"  = :userscript,
            "facebook"    = :facebook,
            "twitter"     = :twitter,
            "steam"       = :steam,
            "skype"       = :skype
            WHERE "counter" = :counter',
           $par
        ],Db::FETCH_ERRNO)
 )
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

if($closed)
{
    if(!$user->hasClosedProfile($_SESSION['id']))
        if(Db::NO_ERRNO != Db::query(
                    [
                        'UPDATE "profiles" SET "closed" = :closed WHERE "counter" = :counter',
                        [
                            ':closed'  => 'true',
                            ':counter' => $_SESSION['id']
                        ]
                    ],Db::FETCH_ERRNO)
          )
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
}
else {
    if(Db::NO_ERRNO != Db::query(
                [
                    'UPDATE "profiles" SET "closed" = :closed WHERE "counter" = :counter',
                    [
                        ':closed'  => 'false',
                        ':counter' => $_SESSION['id']
                    ]
                ],Db::FETCH_ERRNO))
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
}

$_SESSION['dateformat'] = $user['dateformat'];

if(isset($_POST['whitelist']))
{
    $oldlist = $user->getWhitelist($_SESSION['id']);

    $m = array_filter(array_unique(explode("\n",$_POST['whitelist'])));
    $newlist = [];
    foreach($m as $v)
    {
        $uid = $user->getId(trim($v));
        if(is_numeric($uid))
        {
            if(Db::NO_ERRNO != Db::query(
                    [
                        'INSERT INTO "whitelist"("from","to")
                        SELECT :id, :uid
                        WHERE NOT EXISTS (SELECT 1 FROM "whitelist" WHERE "from" = :id AND "to" = :uid)',
                        [
                            ':id'  => $_SESSION['id'],
                            ':uid' => $uid
                        ]
                    ],Db::FETCH_ERRNO))
                die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').'1'));
            $newlist[] = $uid;
        }
        else
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': Invalid user - '.$v));
    }
    $toremove = [];
    foreach($oldlist as $val)
        if(!in_array($val,$newlist))
            $toremove[] = $val;

    foreach($toremove as $val)
        if(Db::NO_ERRNO != Db::query(
                [
                    'DELETE FROM "whitelist" WHERE "from" = :id AND "to" = :val',
                    [
                        ':id'  => $_SESSION['id'],
                        ':val' => $val
                    ]
                ],Db::FETCH_ERRNO))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').'4'));
}
        
die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
?>
