<?php
namespace MyApp;

class Enemy extends MoveableObject{
    private $preBulletShootTime;
    protected $radius = 10;

    public function __construct($args){
        parent::__construct($args);
    }
}