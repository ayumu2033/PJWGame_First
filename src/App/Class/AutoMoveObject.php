<?php
namespace MyApp;

class AutoMoveObject extends MoveableObject{
    private $shape;
    protected $radius = 1;

    public function __construct($args){
        parent::__construct($args);
        if($args != null){
            if(array_key_exists("shape", $args)){
                $this->shape =  $args["shape"];
            }
        }
    }

    public function onUpdate($jsonMsg){
        $nowX = $this->pos->Get()->x + $this->velocity->Get()->x * $this->masterObject->getDeltaTime();
        $nowY = $this->pos->Get()->y + $this->velocity->Get()->y * $this->masterObject->getDeltaTime();
        if($nowX > $jsonMsg->width){
            $this->masterObject->removeObject($this->getTag());
        }
        $this->pos->Set((object)["x"=>$nowX, "y"=>$nowY], null);
        return true;
    }

    public function getParams(){
        $result = parent::getParams();
        $result["shape"] = $this->shape;
        return $result;
    }

}