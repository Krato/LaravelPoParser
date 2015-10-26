<?php namespace EricLagarda\LaravelPoParser;


use sepia\FileHandler;
use sepia\PoParser;

class ClassPoParser {


    public function FileHandler($file)
    {
        
            return new \Sepia\FileHandler($file);
       
    } 


     public function get($filehandler)
    {
        
        return new \Sepia\PoParser($filehandler);
       
    } 
    
}