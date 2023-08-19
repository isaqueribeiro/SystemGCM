<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of configurar
 *
 * @author Isaque
 */
class Configurar {
    //put your code here
    private static $cnf;
    
    private function __construct(){}
    
    public static function set() {
//        define('DB_HOST'     , "localhost");
//        define('DB_USER'     , "sa");
//        define('DB_PASSWORD' , "TheLordIsGod");
        define('DB_HOST'     , "consultoriotofolojr.cu1zrhuzjx31.sa-east-1.rds.amazonaws.com");
        define('DB_USER'     , "tofolo");
        define('DB_PASSWORD' , "pr9NyExEiWEmHc4");
        define('DB_NAME'     , "SystemGCM");
        define('DB_DRIVER'   , "sqlsrv");
        define('DB_TIME_ZONE', "America/Belem");

        if(empty(self::$cnf)){
            self::$cnf = new Configurar();
        }
        
        return self::$cnf;
    }
}
