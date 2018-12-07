<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;

class GameLoopObject {
    public const WAITTIME = 0.0075;

    public $connection;
    public $timer;

    private $objects=[];
    private $preRenderedTime;
    public $pressingKeys = [];
    private $hitLayer = [
        "Player" => ["Enemy", "EnemyBullet"],
        "Enemy" => ["PlayerBullet"],
        "PlayerBullet" => ["Enemy", "EnemyBullet"],
    ];
    public function __construct($connection){
        $this->connection = $connection;
    }

    public function onStart($jsonMsg){
        $this->addObject(new Player([
            "pos"=>["x"=>0,"y"=>$jsonMsg->height/2],
            "masterObject"=>$this,
            "label"=>"Player",
            "radius"=>20 ]));
        $this->addObject(new Enemy([
            "pos"=>["x"=>300,"y"=>$jsonMsg->height/2],
            "masterObject"=>$this,
            "label"=>"Enemy",
            "radius"=>20]));

        // ゲームループ
        return function() use ($jsonMsg){
            $result = [];
            $labelGroup = [];
            foreach($this->objects as $objKey => $obj){
                $labelGroup[$obj->getLabel()][] = $obj;
            }
            foreach($labelGroup as $label => $labeledObjects){
                foreach($labeledObjects as $obj){
                    foreach($this->hitLayer[$label] as $targetLayer){
                        if(!isset($labelGroup[$targetLayer]))break;
                        foreach($labelGroup[$targetLayer] as $targetObject){
                            $obj_2_pos = $targetObject->getPos()->Get();
                            $obj_1_pos = $obj->getPos()->Get();
                            $distance = ($obj_2_pos->x - $obj_1_pos->x) ** 2 + ($obj_2_pos->y - $obj_1_pos->y) ** 2;
                            if($distance < $obj->getRadius()**2 || $distance < $targetObject->getRadius()**2){
                                $obj->onHit($targetObject);
                            }
                        }
                    }
                }
            }
            foreach($this->objects as $objKey => $obj){
                $tmp = $obj->onUpdate($jsonMsg);
                if($tmp != null){
                    $result[$obj->getTag()] = $obj->onUpdate($jsonMsg);
                }
            }
            $this->connection->send(json_encode($result));
            $preRenderedTime = time();
        };
    }

    // KeyUpが呼ばれずにKeyDownが呼び出される場合があるためオブジェクトには落とし込まない。
    public function onKeyDown($jsonMsg){
        $this->pressingKeys[$jsonMsg->key] = 1;
    }
    public function onKeyUp($jsonMsg){
        unset($this->pressingKeys[$jsonMsg->key]);
    }

    public function addObject($obj){
        $this->objects[$obj->getTag()] = $obj;
    }
    public function removeObject($tag){
        unset($this->objects[$tag]);
    }

    public function getPreRenderedTime(){
        return $this->preRenderedTime;
    }
}