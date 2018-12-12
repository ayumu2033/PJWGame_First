<?php
namespace MyApp;

class gameObject{
    protected $pos;
    private $tag;
    protected $masterObject;
    private $label;
    private $hitLeyer;
    protected $view;
    protected $collisionType = "Circle";
    protected $is_dirty = true;

    public function __construct($args){
        if($args != null){
            if(array_key_exists("pos", $args)){
                $x = array_key_exists("x", $args["pos"]) ? $args["pos"]["x"] : 0;
                $y = array_key_exists("y", $args["pos"]) ? $args["pos"]["y"] : 0;
                $this->pos = new SyncData((object)["x"=>$x,"y"=>$y]);
            }else{
                $this->pos = new SyncData((object)["x"=>0,"y"=>0]);
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
        if($this->is_dirty == true){
            $this->is_dirty = false;
            return true;
        }
        return false;
    }

    public function getParams(){
        return [
            "pos"=>["x"=>$this->pos->Get()->x, "y"=>$this->pos->Get()->y],
            "view"=>$this->view,
            "timestamp"=>$this->masterObject->getRenderingTime(),
        ];
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

    public function getCollisionType(){
        return $this->collisionType;
    }

    public function onHit($targetObject){
        $this->masterObject->removeObject($this->getTag());
    }
}