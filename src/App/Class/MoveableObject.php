<?php
namespace MyApp;

class MoveableObject{
    private $pos;
    private $velocity;
    private $tag;

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
        }
        $this->tag = md5(uniqid(rand(),1));
    }
    
    public function setVelocity($v){ 
        $nowV = $this->velocity->Get();
        $this->velocity->Set((object)["x"=>($nowV->x + $v->x),"y"=>($nowV->y + $v->y)], null);
    }

    public function onUpdate($jsonMsg){
        $nowX = $this->pos->Get()->x + $this->velocity->Get()->x;
        $nowX = $nowX > $jsonMsg->width ? 0 : ($nowX < 0 ? $jsonMsg->width : $nowX);
        $nowY = $this->pos->Get()->y + $this->velocity->Get()->y;
        $nowY = $nowY > $jsonMsg->height ? 0 : ($nowY < 0 ? $jsonMsg->height : $nowY);
        $this->pos->Set((object)["x"=>$nowX, "y"=>$nowY], null);
        return ["pos"=>["x"=>$nowX, "y"=>$nowY]];
    }

    public function getTag(){
        return $this->tag;
    }

    public function onKeyDown($jsonMsg){
        $keydownV = (object)["x"=>0,"y"=>0];
        switch($jsonMsg->key){
            case 38:  // ArrowUp
                $keydownV->y = -1;
                $this->setVelocity($keydownV);
                break;
            case 39: // ArrowRight
                $keydownV->x = 1;
                $this->setVelocity($keydownV);
                break;
            case 40: // ArrowDown
                $keydownV->y = 1;
                $this->setVelocity($keydownV);
                break;
            case 37: // ArrowLeft
                $keydownV->x = -1;
                $this->setVelocity($keydownV);
                break;

            case 32: // Space
                $keydownV->x = -1;
                $this->setVelocity($keydownV);
                break;
            default:
        }
    }

    public function onKeyUp($jsonMsg){
        $keydownV = (object)["x"=>0,"y"=>0];
        switch($jsonMsg->key){
            case 38: // ArrowUp
                $keydownV->y = 1;
                $this->setVelocity($keydownV);
                break;
            case 39: // ArrowRight
                $keydownV->x = -1;
                $this->setVelocity($keydownV);
                break;
            case 40: // ArrowDown
                $keydownV->y = -1;
                $this->setVelocity($keydownV);
                break;
            case 37: // ArrowLeft
                $keydownV->x = 1;
                $this->setVelocity($keydownV);
                break;
            default:
        }
    }
}