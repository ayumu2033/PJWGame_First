<?php
namespace MyApp;

class AutoMoveObject extends MoveableObject{
    private $shape;
    public function __construct($args){
        parent::__construct($args);
        if($args != null){
            if(array_key_exists("shape", $args)){
                $this->shape =  $args["shape"];
            }
        }
    }

    public function onUpdate($jsonMsg){
        $nowX = $this->pos->Get()->x + $this->velocity->Get()->x;
        $nowY = $this->pos->Get()->y + $this->velocity->Get()->y;
        if($nowX > $jsonMsg->width){
            $this->masterObject->removeObject($this->getTag());
        }
        $this->pos->Set((object)["x"=>$nowX, "y"=>$nowY], null);
        return ["pos"=>["x"=>$nowX, "y"=>$nowY], "shape" => $this->shape];
    }

    public function onHit($targetObj){
        $this->masterObject->removeObject($this->getTag());
    }
}