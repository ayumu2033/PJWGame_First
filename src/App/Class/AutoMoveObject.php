<?php
namespace MyApp;

class AutoMoveObject{
    private $pos;
    private $velocity;
    private $tag;
    private $masterObject;
    private $shape;

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

            if(array_key_exists("shape", $args)){
                $this->shape = new SyncData( $args["shape"]);
            }else{
                $this->velocity = new SyncData("normalBullet");
            }

            if(array_key_exists("masterObject", $args)){
                $this->masterObject =  $args["masterObject"];
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
        $nowY = $this->pos->Get()->y + $this->velocity->Get()->y;
        if($nowX > $jsonMsg->width){
            $this->masterObject->removeObject($this->tag);
        }
        $this->pos->Set((object)["x"=>$nowX, "y"=>$nowY], null);
        return ["pos"=>["x"=>$nowX, "y"=>$nowY], "shape" => $this->shape->Get()];
    }

    public function getTag(){
        return $this->tag;
    }
}