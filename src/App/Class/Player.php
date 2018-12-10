<?php
namespace MyApp;

class Player extends MoveableObject{
    private $preBulletShootTime;
    protected $radius = 5;

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
        $this->velocity->Set($newV, null);

        // Z-Key
        if(isset($this->masterObject->pressingKeys[90])){
            if($this->preBulletShootTime == null || $this->preBulletShootTime + 0.25  < microtime(true)){
                $this->masterObject->addObject(new AutoMoveObject([
                    "pos"=>["x"=>$this->pos->Get()->x+5,"y"=>$this->pos->Get()->y],
                    "velocity"=>["x"=>2],
                    "shape"=>"normalBullet",
                    "masterObject"=>$this->masterObject,
                    "label"=>"PlayerBullet",
                    "view"=> "PlayerBullet",
                    ]));
                $this->preBulletShootTime = microtime(true);
            }
        }

        $nowX = $this->pos->Get()->x + $this->velocity->Get()->x;
        $nowX = $nowX > $jsonMsg->width ? $jsonMsg->width : ($nowX < 0 ? 0 : $nowX);
        $nowY = $this->pos->Get()->y + $this->velocity->Get()->y;
        $nowY = $nowY > $jsonMsg->height ? $jsonMsg->height : ($nowY < 0 ? 0 : $nowY);
        $this->pos->Set((object)["x"=>$nowX, "y"=>$nowY], null);

        return ["pos"=>["x"=>$nowX, "y"=>$nowY], "view"=>$this->view];
    }
    public function onHit($targetObject){
        parent::onHit($targetObject);
        $this->masterObject->onDeth();
    }

}