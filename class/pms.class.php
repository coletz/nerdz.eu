<?php
namespace NERDZ\Core;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';
use PDO;

final class Pms extends Messages
{

    public function __construct()
    {
        parent::__construct();
    }

    public function send($to,$message)
    {
        $retVal = parent::query(array('INSERT INTO "pms" ("from","to","message") VALUES (:id,:to,:message)',array(':id' => $_SESSION['id'],':to' => $to,':message' => $message)),Db::FETCH_ERRSTR);

        $wentWell = $retVal == Db::NO_ERRSTR;

        if($wentWell && parent::wantsPush($to) && PUSHED_ENABLED) {
        
            require_once $_SERVER['DOCUMENT_ROOT'].'/class/pushed-php-client/pushed.class.php';

            try {
            
                $pushed = Pushed::connectIp(PUSHED_PORT,PUSHED_IP6);

                $msg = json_encode(
                                   [ 
                                     'messageFrom' => html_entity_decode($this->getUsername(), ENT_QUOTES, 'UTF-8'), 
                                     'messageFromId' => $this->getUserId(),
                                     'messageBody' => substr(html_entity_decode($message, ENT_QUOTES, 'UTF-8'), 0, 2000)
                                   ]
                ); //truncate to 2000 chars because of possibile service limitations

                $pushed->push($to, $msg);

            } catch (PushedException $e) {}
        }

        return $retVal;
    }

    public function getList()
    {
        if(!($rs = parent::query(
            [
                'SELECT DISTINCT EXTRACT(EPOCH FROM MAX(times)) as lasttime, otherid as "from", to_read
                 FROM (
                     (SELECT MAX("time") AS times, "from" as otherid, to_read FROM pms WHERE "to" = :id AND to_read GROUP BY "from", to_read)
                     UNION
                     (SELECT MAX("time") AS times, "to" as otherid, FALSE AS to_read FROM pms WHERE "from" = :id GROUP BY "to", to_read)
                 ) AS tmp GROUP BY otherid, to_read ORDER BY to_read DESC, "lasttime" ASC',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_STMT)))
                return false;
    
        $res = $froms = [];
        $c = 0;
        while(($o = $rs->fetch(PDO::FETCH_OBJ)))
        {
            if(!in_array($o->from, $froms)) {
                $from = $this->getUsername($o->from);
                $res[$c]['from4link_n'] = \NERDZ\Core\Core::userLink($from);
                $res[$c]['from_n'] = $from;
                $res[$c]['datetime_n'] = parent::getDateTime($o->lasttime);
                $res[$c]['timestamp_n'] = $o->lasttime;
                $res[$c]['fromid_n'] = $o->from;
                $res[$c]['toid_n'] = $_SESSION['id'];
                $res[$c]['toread_n'] = $o->to_read;

                $froms[] = $o->from;
                ++$c;
            }
        }

        return $res;
    }
        
    public function read($fromid,$toid,$time,$pmid)
    {
        $ret = [];
            
        if(
                !is_numeric($fromid) || !is_numeric($toid) || !is_numeric ($pmid) || !in_array($_SESSION['id'],array($fromid,$toid)) ||
                !($res = parent::query(array('SELECT "message","to_read" FROM "pms" WHERE "from" = :from AND "to" = :to AND "pmid" = :pmid',array(':from' => $fromid, ':to' => $toid, ':pmid' => $pmid)),Db::FETCH_STMT))
          )
            return false;

        if(($o = $res->fetch(PDO::FETCH_OBJ)))
        {
            $from = $this->getUsername($fromid);
            $ret['from4link_n'] = \NERDZ\Core\Core::userLink($from);
            $ret['from_n'] = $from;
            $ret['datetime_n'] = parent::getDateTime($time);
            $ret['fromid_n'] = $fromid;
            $ret['toid_n'] = $toid;
            $ret['message_n'] = parent::bbcode($o->message);
            $ret['read_b'] = $o->to_read;
            $ret['pmid_n'] = $pmid;
            $ret['timestamp_n'] = $time;
        }
        
        return $ret;
    }
        
    public function deleteConversation($from, $to)
    {
        return is_numeric($from) && is_numeric($to) && in_array($_SESSION['id'],array($from,$to)) && 
            Db::NO_ERRNO == parent::query(
                    [
                        'DELETE FROM "pms" WHERE ("from" = :from AND "to" = :to) OR ("from" = :to AND "to" = :from)',
                        [
                            ':from' => $from,
                            ':to'   => $to
                        ]
                    ],Db::FETCH_ERRNO);
    }
    
    public function readConversation($from, $to, $afterPmId = null, $num = null, $start = 0)
    {
        $ret = [];
        
        if(!is_numeric($from) || !is_numeric($to) || (is_numeric ($num) && is_numeric ($start) && ($start < 0 || $start > 200 || $num < 0 || $num > 10)))
            return $ret;
        $__enableLimit = is_numeric ($num) && is_numeric ($start);

        $query = $__enableLimit ?
                        'SELECT q.from, q.to, q.time, q.pmid FROM (SELECT "from", "to", EXTRACT(EPOCH FROM "time") AS time, "pmid" FROM "pms" WHERE (("from" = ? AND "to" = ?) OR ("from" = ? AND "to" = ?)) ORDER BY "pmid" DESC LIMIT ? OFFSET ?) AS q ORDER BY q.pmid ASC' :
                        'SELECT "from", "to", EXTRACT(EPOCH FROM "time") AS time, "pmid" FROM "pms" WHERE '.($afterPmId ? '"pmid" > ? AND ' : '').' (("from" = ? AND "to" = ?) OR ("from" = ? AND "to" = ?)) ORDER BY "pmid" ASC';
        if (!($res = parent::query (array ($query, ($__enableLimit ? array ($from, $to, $to, $from, $num, $start * $num) : ( $afterPmId ? array ($afterPmId, $from, $to, $to, $from) : array ($from, $to, $to, $from)))), Db::FETCH_STMT)))
            return $ret;

        $ret = $res->fetchAll(PDO::FETCH_FUNC,array($this,'read'));

        //se l'ultimo l'ho inviato io, mostro il nuovo commento se non ne sono stati aggiunti di nuovi dall'altro prima
        if($afterPmId && empty($ret))
        {
            if(!($res = parent::query(
                    array('SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time,"pmid" FROM "pms" WHERE "pmid" = ? AND (("from" = ? AND "to" = ?) OR ("from" = ? AND "to" = ?)) ORDER BY "pmid" ASC',array($afterPmId,$from, $to,$to,$from)
                          ),Db::FETCH_STMT)))
                return $ret;
            $ret = $res->fetchAll(PDO::FETCH_FUNC,array($this,'read'));
        }

        parent::query(
                [
                    'UPDATE "pms" SET "to_read" = FALSE WHERE "from" = :from AND "to" = :id',
                    [
                        ':from' => $from,
                        ':id'   => $_SESSION['id']
                    ]
                ],Db::NO_RETURN);
        
        return $ret;
    }

    public function countPms ($from, $to)
    {
        if (!is_numeric ($from) || !is_numeric ($to) || !($res = parent::query (
                        [
                            'SELECT COUNT("pmid") AS pc FROM "pms" WHERE (("from" = :from AND "to" = :to) OR ("from" = :to AND "to" = :from))',
                            [
                                ':from' => $from,
                                ':to'   => $to
                            ]
                        ], Db::FETCH_OBJ)))
            return 0;
        return $res->pc;
    }    

    public function getLastMessageForConversation($otherId) {       
        $ret = false;

        if(is_numeric($otherId)) {
            $res = parent::query(
                [
                    'WITH thisconv AS (SELECT "from",time,message,to_read FROM pms WHERE("from" = :me AND "to" = :other) OR  ("from" = :other AND  "to" = :me)) SELECT "from" as last_sender,message,to_read FROM thisconv WHERE time = (SELECT MAX(time) FROM thisconv)',
                    [
                        ':me' => $_SESSION['id'],
                        ':other' => $otherId,
                    ]                    
                ], Db::FETCH_OBJ                
            );
            
            if (isset($res->message))
                $ret = $res;

        }

        return $ret;
    }
}
?>
