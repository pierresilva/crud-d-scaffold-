<?php

namespace pierresilva\LaravelCrud\Core;

use Illuminate\Support\Str;

class NameResolver
{
    static function solveName( $input, $type ){

        if( is_array($input) || is_object($input) ){
            dd($input);
            throw new \Exception("NameSolver accept invalid input");
        }
        if( is_array($type) || is_object($type) ){
            dd($type);
            throw new \Exception("NameSolver accept invalid type");
        }

        if( $type === 'nameName' ){
            $result = Str::camel( Str::singular($input) );
        }elseif( $type === 'NameName' ){
            $result = Str::studly( Str::singular($input) );
        }elseif( $type === 'nameNames' ){
            $result = Str::camel( Str::plural($input) );
        }elseif( $type === 'NameNames' ){
            $result = Str::studly( Str::plural($input) );
        }elseif( $type === 'name_name' ){
            $result = str_replace('__', '_', Str::snake( Str::singular($input) ) );
        }elseif( $type === 'name_names' ){
            $result = str_replace('__', '_', Str::snake( Str::plural($input) ) );
        }elseif( $type === 'NAME_NAME' ){
            $result = mb_strtoupper( str_replace('__', '_', Str::snake( Str::singular($input) ) ) );
        }elseif( $type === 'NAME_NAMES' ){
            $result = mb_strtoupper( str_replace('__', '_', Str::snake( Str::plural($input) ) ) );
        }elseif( $type === 'name-names' ){
            $result = mb_strtolower( str_replace('__', '-', Str::kebab( Str::plural($input) ) ) );
        }elseif( $type === 'name-name' ){
            $result = mb_strtolower( str_replace('__', '-', Str::kebab( Str::singular($input) ) ) );
        }elseif( $type === 'lower' ){
            $result = mb_strtolower( $input );
        }elseif( $type === 'upper' ){
            $result = mb_strtoupper( $input );
        }elseif( $type === 'title' ){
            $result = Str::title( $input );
        }elseif( $type === 'lowers' ){
            $result = mb_strtolower( Str::plural($input) );
        }elseif( $type === 'uppers' ){
            $result = mb_strtoupper( Str::plural($input) );
        }elseif( $type === 'titles' ){
            $result = Str::title( Str::plural($input) );
        }elseif( $type === 'NAME NAME' || $type === null ){
            $result = mb_strtoupper(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", Str::singular($input)));
        }elseif( $type === 'NAME NAMES' || $type === null ){
            $result = mb_strtoupper(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", Str::plural($input)));
        }elseif( $type === 'Name Name' || $type === null ){
            $result = ucwords(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", Str::singular($input)));
        }elseif( $type === 'Name Names' || $type === null ){
            $result = ucwords(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", Str::plural($input)));
        }elseif( $type === '' || $type === null ){
            $result = $input;
        }else{
            throw new \Exception("NameSolver accept invalid type");
        }
        return $result;
    }
}
