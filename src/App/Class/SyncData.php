<?php
namespace MyApp;

class SyncData{
    private $timestamp;
    private $data;

    public function __construct($val){
        $this->data = $val;
        $this->timestamp = time();
    }

    public function Get(){
        return $this->data;
    }
    public function Set($val, $time){
        if($time == null || $time !== $this->timestamp){
            $time = time();
        }
        if($time >= $this->timestamp){
            $this->data = $val;
        }
    }
}