<?php
namespace MyApp;

class SyncData{
    private $timestamp;
    private $data;

    public function __construct($val){
        $this->data = $val;
        $this->timestamp = microtime(true);
    }

    public function Get(){
        return $this->data;
    }
    public function Set($val, $time){
        if($time == null || $time !== $this->timestamp){
            $time = microtime(true);
        }
        if($time >= $this->timestamp){
            $this->data = $val;
        }
    }
}