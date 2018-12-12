<?php
namespace MyApp;

class Player extends MoveableObject{
    private $preBulletShootTime;
    protected $radius = 5;
    private $speed = 200;

    public function __construct($args){
        parent::__construct($args);
    }

    public function onUpdate($jsonMsg){
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
        if($newV->x != 0 || $newV->y != 0){
            $size = sqrt($newV->x**2 + $newV->y**2);
            $newV->x = $newV->x / $size * $this->speed;
            $newV->y = $newV->y / $size * $this->speed;
        }


        // Z-Key
        if(isset($this->masterObject->pressingKeys[90])){
            if($this->preBulletShootTime == null || $this->preBulletShootTime + 0.25  < microtime(true)){
                $this->masterObject->createObject([
                    "class"=>"Bullet",
                    "pos"=>["x"=>$this->pos->Get()->x+5,"y"=>$this->pos->Get()->y],
                    "velocity"=>["x"=>$jsonMsg->width],
                    "shape"=>"normalBullet",
                    "masterObject"=>$this->masterObject,
                    "label"=>"PlayerBullet",
                    "view"=> "PlayerBullet",
                    ]);
                $this->preBulletShootTime = microtime(true);
            }
        }

        $nowX = $this->pos->Get()->x + $newV->x * $this->masterObject->getDeltaTime();
        if($nowX > $jsonMsg->width){
            $nowX = $jsonMsg->width;
            $newV->x = 0;
        }else if($nowX < 0){
            $nowX = 0;
            $newV->x = 0;
        }
        $nowY = $this->pos->Get()->y + $newV->y * $this->masterObject->getDeltaTime();
        if($nowY > $jsonMsg->height){
            $nowY = $jsonMsg->height;
            $newV->y = 0;
        }else if($nowY < 0){
            $nowY = 0;
            $newV->y = 0;
        }
        if($newV->x == $this->velocity->Get()->x && $newV->y == $this->velocity->Get()->y){
            $this->is_dirty = false;
        }else{
            $this->is_dirty = true;
            $this->velocity->Set($newV, null);
        }
        $this->pos->Set((object)["x"=>$nowX, "y"=>$nowY], null);
        if($this->is_dirty == true){
            $this->is_dirty = false;
            return true;
        }
        return false;
    }
    public function onHit($targetObject){
        parent::onHit($targetObject);
        $this->masterObject->onDeth();
    }
}