<?php
namespace MyApp;

class Enemy extends MoveableObject{
    private $preBulletShootTime;

    public function __construct($args){
        parent::__construct($args);
    }

    public function onHit($targetObj){
        $this->masterObject->removeObject($this->getTag());
    }
}