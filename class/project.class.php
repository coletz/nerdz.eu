<?php
namespace NERDZ\Core;
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use PDO;

class project
{
    private $id;

    public function __construct($id = null)
    {
        if($id = null) {
            $this->id = $id;
        }
    }

    private static function checkId(&$id)
    {
        if(empty($id)) {
            if(empty($this->id)) {
                die(__NAMESPACE__.__CLASS__.' invalid project ID');
            }
            else $id = $this->id;
        }
    }

    public function getObject($id = null)
    {
        static::checkId($id);
        return Db::query(
            [
                'SELECT * FROM "groups" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ);
    }

    public function getMembersAndOwnerFromHpid($hpid)
    {
        if(!($info = Db::query(array('SELECT "to" FROM "groups_posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),Db::FETCH_OBJ)))
            return false;

        $members   = $this->getMembers($info->to);
        $members[] = $this->getOwner($info->to);

        return $members;
    }

    public function getId($name = null)
    {
        static::checkId($id);
        if(!($o = Db::query(
            [
                'SELECT "counter" FROM "groups" WHERE LOWER("name") = LOWER(:name)',
                    [
                        ':name' => htmlspecialchars($name,ENT_QUOTES,'UTF-8')
                    ]
            ],Db::FETCH_OBJ)))
            return 0;
        return $o->counter;
    }

    public function getOwner($id = null)
    {
        static::checkId($id);
        if(!($o = Db::query(
            [
                'SELECT "owner" FROM "groups" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return 0;
        return $o->owner;
    }

    public function isOpen($id = null)
    {
        static::checkId($id);
        if(!($o = Db::query(
            [
                'SELECT "open" FROM "groups" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return false;

        return $o->open;
    }
   
    public function getMembers($id = null)
    {
        static::checkId($id);
        if(!($stmt = Db::query(
            [
                'SELECT "from" FROM "groups_members" WHERE "to" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_STMT)))
            return [];

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFollowers($id = null)
    {
        static::checkId($id);
        if(!($stmt = Db::query(
            [
                'SELECT "from" FROM "groups_followers" WHERE "to" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_STMT)))
            return [];

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function getName($id = null)
    {
        static::checkId($id);
        if(!($o = Db::query(
            [
                'SELECT "name" FROM "groups" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return '';

        return $o->name;
    }
}

if(isset($_GET['gid']) && !is_numeric($_GET['gid']) && is_string($_GET['gid']))
    $_GET['gid'] = (new Project(trim($_GET['gid'])))->getId();

?>
