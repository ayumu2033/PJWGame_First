<?php
namespace MyApp;

class MoveableObject{
    protected $pos;
    protected $velocity;
    private $tag;
    protected $masterObject;
    private $label;
    private $hitLeyer;
    protected $radius;
    protected $view;
    protected $collisionType = "Circle";

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
            if(array_key_exists("label", $args)){
                $this->label =  $args["label"];
            }
            if(array_key_exists("hitLayer", $args)){
                $this->hitLayer =  $args["hitLayer"];
            }
            if(array_key_exists("view", $args)){
                $this->view =  $args["view"];
            }
        }
        $this->tag = md5(uniqid(rand(),1));
    }

    public function onUpdate($jsonMsg){
        $nowX = $this->pos->Get()->x + $this->velocity->Get()->x;
        $nowY = $this->pos->Get()->y + $this->velocity->Get()->y;
        $this->pos->Set((object)["x"=>$nowX, "y"=>$nowY], null);

        return ["pos"=>["x"=>$nowX, "y"=>$nowY], "view"=>$this->view];
    }

    public function getTag(){
        return $this->tag;
    }
    public function getLabel(){
        return $this->label;
    }

    public function getPos(){
        return $this->pos;
    }
    public function getRadius(){
        return $this->radius;
    }

    public function getCollisionType(){
        return $this->collisionType;
    }

    public function onHit($targetObject){
        $this->masterObject->removeObject($this->getTag());
    }
}