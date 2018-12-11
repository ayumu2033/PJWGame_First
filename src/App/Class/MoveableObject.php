<?php
namespace MyApp;

class MoveableObject extends gameObject{
    protected $velocity;
    protected $masterObject;
    protected $radius;

    public function __construct($args){
        parent::__construct($args);
        if($args != null){
            if(array_key_exists("velocity", $args)){
                $x = array_key_exists("x", $args["velocity"]) ? $args["velocity"]["x"] : 0;
                $y = array_key_exists("y", $args["velocity"]) ? $args["velocity"]["y"] : 0;
                $this->velocity = new SyncData((object)["x"=>$x,"y"=>$y]);
            }else{
                $this->velocity = new SyncData((object)["x"=>0,"y"=>0]);
            }
        }
    }

    public function onUpdate($jsonMsg){
        $nowX = $this->pos->Get()->x + $this->velocity->Get()->x;
        $nowY = $this->pos->Get()->y + $this->velocity->Get()->y;

        if($nowX == $this->pos->Get()->x && $nowY == $this->pos->Get()->y){
            return false || parent::onUpdate($jsonMsg);
        }
        $this->pos->Set((object)["x"=>$nowX, "y"=>$nowY], null);
        return true;
    }

    public function getRadius(){
        return $this->radius;
    }

    public function onHit($targetObject){
        $this->masterObject->removeObject($this->getTag());
    }
}