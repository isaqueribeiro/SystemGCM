<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of sessao
 *
 * @author Isaque
 */
class Sessao {
    private static $instancia = array();
    
    /**
     * 
     * @return Session
     */
    public static function getInstancia() {
        if (self::$instancia =! null) {
            self::$instancia = new Sessao();
        }
        
        return self::$instancia;
    }
    
    public function set($chave, $valor) {
        /* Define o limitador de cache para 'private' */
        session_cache_limiter('private');
        session_cache_expire(30); // 30 minutos de duração
        
        session_start();

        $_SESSION[$chave] = $valor;
        session_write_close();
    }
    
    public function get($chave) {
        //session_start();
        $value = (isset($_SESSION[$chave])?$_SESSION[$chave]:"");
        session_write_close();
        return $value;
    }
    
    public function existe($chave) {
        session_start();
        if (isset($_SESSION[$chave]) && (!empty($_SESSION[$chave]))) {
            session_write_close();
            return true;
        } else {
            session_write_close();
            return false;
        }
    }
}
