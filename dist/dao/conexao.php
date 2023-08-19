<?php
/**
 * Description of conexao
 *
 * @author Isaque
 */
class Conexao {
    private static $conn;
    
    private function __construct(){}
    
    public static function getConnection() {
//        if(!defined('DB_HOST'))     define('DB_HOST'     , "localhost");
//        if(!defined('DB_USER'))     define('DB_USER'     , "sa");
//        if(!defined('DB_PASSWORD')) define('DB_PASSWORD' , "TheLordIsGod");
        if(!defined('DB_HOST'))     define('DB_HOST'     , "consultoriotofolojr.cu1zrhuzjx31.sa-east-1.rds.amazonaws.com");
        if(!defined('DB_USER'))     define('DB_USER'     , "tofolo");
        if(!defined('DB_PASSWORD')) define('DB_PASSWORD' , "pr9NyExEiWEmHc4");
        if(!defined('DB_NAME'))     define('DB_NAME'     , "SystemGCM");
        if(!defined('DB_DRIVER'))   define('DB_DRIVER'   , "sqlsrv");
        
        $pdo  = DB_DRIVER . ":". "Server=" . DB_HOST . ";";
        $pdo .= "Database=" . DB_NAME . ";";
        
        try {
            if(empty(self::$conn)){
                self::$conn = new PDO($pdo, DB_USER, DB_PASSWORD);
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            return self::$conn;
        } catch (Exception $ex) {
            $msg  = "Drivers disponiveis: " . implode(",", PDO::getAvailableDrivers());
            $msg .= "\nErro: " . $ex->getMessage();
            throw new Exception($msg);
        }
    }
}
