<?php
namespace MyApp;

class MoveableObject{
    private $pos;
    private $velocity;
    private $tag;
    private $masterObject;
    private $preBulletShootTime;
    private $pressingKeys=[];

    public function __construct($args){
        if($args != null){
            if(array_key_exists("pos", $args)){
                $x = array_key_exists("x", $args["pos"]) ? $args["pos"]["x"] : 0;
                $y = array_key_exists("y", $args["pos"]) ? $args["pos"]["y"] : 0;
                $this->pos = new SyncData((object)["x"=>$x,"y"=>$y]);
            }else{
                $this->pos = new SyncData((object)["x"=>0,"y"=>0]);
            }
            if(array_key_exists("velocity", $args)){
                $x = array_key_exists("x", $args["velocity"]) ? $args["velocity"]["x"] : 0;
                $y = array_key_exists("y", $args["velocity"]) ? $args["velocity"]["y"] : 0;
                $this->velocity = new SyncData((object)["x"=>$x,"y"=>$y]);
            }else{
                $this->velocity = new SyncData((object)["x"=>0,"y"=>0]);
            }
            if(array_key_exists("masterObject", $args)){
                $this->masterObject =  $args["masterObject"];
            }
        }
        $this->tag = md5(uniqid(rand(),1));
    }

    public function onUpdate($jsonMsg){
        $nowX = $this->pos->Get()->x + $this->velocity->Get()->x;
        $nowX = $nowX > $jsonMsg->width ? $jsonMsg->width : ($nowX < 0 ? 0 : $nowX);
        $nowY = $this->pos->Get()->y + $this->velocity->Get()->y;
        $nowY = $nowY > $jsonMsg->height ? $jsonMsg->height : ($nowY < 0 ? 0 : $nowY);
        $this->pos->Set((object)["x"=>$nowX, "y"=>$nowY], null);

        $newV = (object)["x"=>0,"y"=>0];
        // ArrowUp
        if(isset($this->masterObject->pressingKeys[38])){
            $newV->y -=1;
        }
        // ArrowRight
        if(isset($this->masterObject->pressingKeys[39])){
            $newV->x +=1;
        }
        // ArrowDown
        if(isset($this->masterObject->pressingKeys[40])){
            $newV->y +=1;
        }
        // ArrowLeft
        if(isset($this->masterObject->pressingKeys[37])){
            $newV->x -=1;
        }
        $this->velocity->Set($newV, null);

        // Z-Key
        if(isset($this->masterObject->pressingKeys[90])){
            if($this->preBulletShootTime == null || $this->preBulletShootTime + 0.25  < microtime(true)){
                $this->masterObject->addObject(new AutoMoveObject([
                    "pos"=>["x"=>$this->pos->Get()->x+5,"y"=>$this->pos->Get()->y],
                    "velocity"=>["x"=>2],
                    "shape"=>"normalBullet",
                    "masterObject"=>$this->masterObject
                    ]));
                $this->preBulletShootTime = microtime(true);
            }
        }
        return ["pos"=>["x"=>$nowX, "y"=>$nowY]];
    }

    public function getTag(){
        return $this->tag;
    }

}