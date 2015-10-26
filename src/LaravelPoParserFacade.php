<?php namespace EricLagarda\LaravelPoParser;


use Illuminate\Support\Facades\Facade;

class LaravelPoParserFacade extends Facade{
    protected static function getFacadeAccessor() { return 'poparser'; }
}