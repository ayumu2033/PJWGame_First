<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;

class GameLoopObject {
    public const WAITTIME = 0.0075;

    public $connection;
    public $timer;
    public $pressingKeys = [];
    private $canvasHeight;

    private $objects=[];
    private $removedObjectTags=[];
    private $createdObjectTags=[];
    private $sceneObjects=[];
    private $sceneStartedTime;

    private $preRenderedTime;
    private $renderingTime;
    
    private $hitLayer = [
        "Player" => ["Enemy", "EnemyBullet", "Ground"],
        "Enemy" => ["PlayerBullet", "Player"],
        "PlayerBullet" => ["Enemy", "EnemyBullet", "Ground"],
        "Ground" => ["Enemy", "EnemyBullet", "Player"],
    ];

    public function __construct($connection){
        $this->connection = $connection;
    }

    public function onDeth(){
        $this->createObject([
            "class"=>"Player",
            "pos"=>["x"=>0,"y"=>$this->canvasHeight/2],
            "masterObject"=>$this,
            "label"=>"Player",
            "view"=> "Player",
            ]);
    }

    public function onStart($jsonMsg){
        $this->canvasHeight = $jsonMsg->height;

        $this->preRenderedTime = microtime(true);
        $preEnemyPopTime = microtime(true);
        $this->readSceneFile("game");
        $this->sceneStartedTime = microtime(true);
        // ゲームループ
        return function() use ($jsonMsg, &$preEnemyPopTime){
            $this->renderingTime = microtime(true);

            foreach($this->sceneObjects as $key=>$objarg){
                if($objarg["time"] > (microtime(true) - $this->sceneStartedTime) )break;
                $objarg["masterObject"] = $this;
                $this->createObject($objarg);
                unset($this->sceneObjects[$key]);
            }
            
            // あたり判定
            $labelGroup = [];
            foreach($this->objects as $objKey => $obj){
                $labelGroup[$obj->getLabel()][] = $obj;
            }
            $finishedLayer = [];//["target" => "faster(done)"]
            foreach($labelGroup as $label => $labeledObjects){
                foreach($labeledObjects as $obj){
                    foreach($this->hitLayer[$label] as $targetLayer){
                    if(isset($finishedLayer[$label]) && in_array($targetLayer, $finishedLayer[$label])) continue;
                        if(!isset($labelGroup[$targetLayer])) continue;

                        foreach($labelGroup[$targetLayer] as $targetObject){
                            if($this->collisionDetection($obj, $targetObject)){
                                $obj->onHit($targetObject);
                                $targetObject->onHit($obj);
                            }
                        }
                        $finishedLayer[$targetLayer][] = $label;
                    }
                }
            }

            $result = [];
            $result["update"] = [];

            if($preEnemyPopTime + 1 < $this->renderingTime){
                $preEnemyPopTime = $this->renderingTime;
                $this->createObject([
                    "class"=>"Enemy",
                    "pos"=>["x"=>rand(200, 500),"y"=>rand($jsonMsg->height/2 - 100, $jsonMsg->height/2 + 100)],
                    "masterObject"=>$this,
                    "label"=>"Enemy",
                    "view"=> "Enemy",
                    ]);
            }
            // アップデート
            foreach($this->createdObjectTags as $createdObjTag){
                $this->objects[$createdObjTag]->onUpdate($jsonMsg);
                $result["update"][$this->objects[$createdObjTag]->getTag()] = $this->objects[$createdObjTag]->getParams();
            }
            $this->createdObjectTags = [];

            foreach($this->objects as $objKey => $obj){
                if($obj->onUpdate($jsonMsg)){
                    $result["update"][$obj->getTag()] = $obj->getParams();
                }
            }

            $result["remove"] = (array)$this->removedObjectTags;
            // 更新の送信
            if(count($result["update"]) > 0 || count($result["remove"]) > 0){
                $this->connection->send(json_encode($result));
            }
            $this->removedObjectTags = [];
            $this->preRenderedTime = $this->renderingTime;
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
        $this->removedObjectTags[] = $tag;
    }

    private $sceneDir = __DIR__ ."/../../scene/";
    public function readSceneFile($sceneName){
        $fileContent = json_decode(file_get_contents($this->sceneDir.$sceneName.".json"), true);
        print_r($fileContent);
        $this->sceneObjects = $fileContent;
    }

    public function createObject($args){
        if($args["class"] == null){
            throw new Exception('オブジェクト生成においてクラスが指定されていません。');
        }

        $tmp = null;
        switch($args["class"]){
            case "Player":
                $tmp = new Player($args);
                break;
            case "Enemy":
                $tmp = new Enemy($args);
                break;
            case "Ground":
                $tmp = new Ground($args);
                break;
            case "Bullet":
                $tmp = new AutoMoveObject($args);
                break;
            default:
                throw new Exception('オブジェクト生成において指定のクラスが存在しません。:'.$args["class"]);
        }
        $this->objects[$tmp->getTag()] = $tmp;
        $this->createdObjectTags[] = $tmp->getTag();
    }

    public function getDeltaTime(){
        return $this->renderingTime - $this->preRenderedTime;
    }
    public function getRenderingTime(){
        return $this->renderingTime;
    }

    public function collisionDetection($obj_1, $obj_2){
        $type_1 = $obj_1->getCollisionType();
        $type_2 = $obj_2->getCollisionType();
        if($type_1 == "Circle" && $type_2 == "Circle"){
            $obj_2_pos = $obj_2->getPos()->Get();
            $obj_1_pos = $obj_1->getPos()->Get();
            $distance = ($obj_2_pos->x - $obj_1_pos->x) ** 2 + ($obj_2_pos->y - $obj_1_pos->y) ** 2;
            if($distance < $obj_1->getRadius()**2 || $distance < $obj_2->getRadius()**2){
                return true;
            }else{
                return false;
            }
        }else if($type_1 == "Circle" || $type_2 == "Circle"){
            $circleObj = $type_1 == "Circle" ? $obj_1 : $obj_2;
            $polygonObj = $type_1 == "Polygon" ? $obj_1 : $obj_2;

            $circlePos = $circleObj->getPos()->Get();
            $hitFlag = false;
            $isContain = true;
            foreach($polygonObj->getCollisionLines() as $line){
                $vecA = ["x"=>$circlePos->x - $line[0]["x"], "y"=>$circlePos->y - $line[0]["y"]];

                $vecLine = ["x"=>$line[1]["x"] - $line[0]["x"], "y"=>$line[1]["y"] - $line[0]["y"]];
                // 外積 ÷ 線分の大きさ
                $distance = ($vecA["x"] * $vecLine["y"] - $vecA["y"] * $vecLine["x"]) ** 2 / (($line[1]["x"] - $line[0]["x"] ) ** 2 + ($line[1]["y"] - $line[0]["y"]) **2);

                if($distance > $circleObj->getRadius()**2){
                    $hitFlag = $hitFlag || false;
                    // 外積で点が左にあるか調べる、全部右にあれば内包している。正で左側
                    if($vecA["x"] * $vecLine["y"] - $vecA["y"] * $vecLine["x"] > 0){
                        $isContain = false;
                    }
                }else{
                    //　内包判定は全部半径より大きい場合のみ
                    $isContain = false;
                    $vecB = ["x"=>$circlePos->x - $line[1]["x"], "y"=>$circlePos->y - $line[1]["y"]];
                    if(($vecA["x"]*$vecLine["x"] + $vecA["y"]*$vecLine["y"]) * ($vecB["x"]*$vecLine["x"] + $vecB["y"]*$vecLine["y"]) <= 0){
                        $hitFlag = true;
                    }else{
                        // 内積をしてcosθの符号を取得、両方とも鈍角なら当たらない。A.S, B.S
                        if($circleObj->getRadius()**2 > $vecA["x"]*$vecA["x"] + $vecA["y"]*$vecA["y"]
                            || $circleObj->getRadius()**2 > $vecB["x"]*$vecB["x"] + $vecB["y"]*$vecB["y"]){
                                // 半径より短ければ当たる
                                $hitFlag = true;
                        }else{
                            $hitFlag = $hitFlag || false;
                        }
                    }
                }
            }

            return $hitFlag || $isContain;
        }else{
            //no Circle

        }

    }
}