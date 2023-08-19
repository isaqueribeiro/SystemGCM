<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once './sessao.php';
require_once '../dao/conexao.php';
require_once '../dao/autenticador.php';
require_once './usuario.php';

switch ($_REQUEST['acao']) {
    
    case 'login': { 
        $aut = Autenticador::getInstancia();
        if ($aut->logar(trim($_REQUEST['email']), trim($_REQUEST['senha']), $_REQUEST['token'])) {
            if (!$aut->usuario_medico()) { // Se não for usuário médico
                header('location: ../../starter.php');
            } else {
                header('location: ../../medical.php');
            }
        } else {
            header('location: ../../index.php?tag=' . $aut->retorno);
        }
    } break;
    
    case 'register': {
        if (trim($_REQUEST['senha']) !== trim($_REQUEST['resenha'])) {
            $sess = Sessao::getInstancia();
            $key  = 'user_SystemGCM';
            $sess->set($key, "Senha não confere!");
            
            header('location: ../../views/registrar.php?tag=erro_pwd');
            exit;
        }
        
        $aut  = Autenticador::getInstancia();
        $user = new Usuario();
        $user->setNome(trim($_REQUEST['nome']));
        $user->setEmail(trim($_REQUEST['email']));
        $user->setSenha(trim($_REQUEST['senha']));
        
        if ($aut->registrar($user, $_REQUEST['token'])) {
            header('location: ../../views/registrar.php?tag=' . $aut->retorno);
        } else {
            header('location: ../../views/registrar.php?tag=' . $aut->retorno);
        }
    } break;
    
    case 'close': {
        header('location: ../../index.php');
    } break;
}