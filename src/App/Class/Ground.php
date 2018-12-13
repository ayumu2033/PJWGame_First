<?php
namespace MyApp;

class Ground extends MoveableObject{
    private $polygon;
    protected $collisionType = "Polygon";

    public function __construct($args){
        parent::__construct($args);
        if($args != null){
            if(array_key_exists("polygon", $args)){
                $this->polygon =  $args["polygon"];
            }
        }
    }

    public function getParams(){
        $result = parent::getParams();
        $result["polygon"] = $this->polygon;
        return $result;
    }

    public function getCollisionLines(){
        $lines = [];
        $pos = 0;
        for($overCount=0; $overCount < count($this->polygon); $overCount++){
            $nextPos = fmod($pos + 1, count($this->polygon));
            $lines[] = [
                ["x"=>$this->polygon[$pos]["x"] + $this->pos->Get()->x, "y"=>$this->polygon[$pos]["y"]  + $this->pos->Get()->y],
                ["x"=>$this->polygon[$nextPos]["x"] + $this->pos->Get()->x, "y"=>$this->polygon[$nextPos]["y"] + $this->pos->Get()->y]
            ];
            $pos = $nextPos;
        }
        return $lines;
    }

    public function onHit($targetObj){
    }
}