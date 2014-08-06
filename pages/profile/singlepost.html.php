<?php
if(!isset($hpid, $draw))
    die('$hpid and $draw required');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Messages;
use NERDZ\Core\Comments;

ob_start(array('NERDZ\\Core\\Core','minifyHtml'));

$core = new Messages();
$comments = new Comments();

if(empty($hpid) || !($o = $core->getMessage($hpid)))
    die($core->lang('ERROR'));

if(!($from = $core->getUsername($o->from)))
    $from = '';
if(!($to = $core->getUsername($o->to)))
    $to =  '';

$singlepostvals = [];
$singlepostvals['revisions_n'] = $core->getRevisionsNumber($hpid);
$singlepostvals['thumbs_n'] = $core->getThumbs($hpid);
$singlepostvals['uthumb_n'] = $core->getUserThumb($hpid);
$singlepostvals['pid_n'] = $o->pid;
$singlepostvals['from4link_n'] = \NERDZ\Core\Core::userLink($from);
$singlepostvals['to4link_n'] = \NERDZ\Core\Core::userLink($to);
$singlepostvals['fromid_n'] = $o->from;
$singlepostvals['toid_n'] = $o->to;
$singlepostvals['from_n'] = $from;
$singlepostvals['to_n'] = $to;
$singlepostvals['datetime_n'] = $core->getDateTime($o->time);

$singlepostvals['canremovepost_b'] = $core->canRemovePost((array)$o);
$singlepostvals['caneditpost_b'] = $core->canEditPost((array)$o);
$singlepostvals['canshowlock_b'] = $core->canShowLockForPost((array)$o);
$singlepostvals['lock_b'] = $core->hasLockedPost((array)$o);
$singlepostvals['cmp_n'] = $o->time;

$singlepostvals['canshowlurk_b'] = $core->isLogged() ? !$singlepostvals['canshowlock_b'] : false;
$singlepostvals['lurk_b'] = $core->hasLurkedPost((array)$o);

$singlepostvals['canshowbookmark_b'] = $core->isLogged();
$singlepostvals['bookmark_b'] = $core->hasBookmarkedPost((array)$o);

$blisted = in_array($o->from,$core->getBlacklist());

$singlepostvals['message_n'] = $blisted ? 'Blacklist' : $core->parseNewsMessage($core->bbcode($o->message,false,'u',$singlepostvals['pid_n'],$singlepostvals['toid_n']));
$singlepostvals['postcomments_n'] = $blisted ? '0' : $comments->countComments($o->hpid);
$singlepostvals['hpid_n'] = $o->hpid;
$singlepostvals['news_b'] = isset($o->news) ? $o->news : false;

$core->getTPL()->assign($singlepostvals);

if($draw)
    $core->getTPL()->draw('profile/post');
else
    $singlepost = $core->getTPL()->draw('profile/post',true);

?>
