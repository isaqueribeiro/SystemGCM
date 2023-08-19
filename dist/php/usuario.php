<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of usuario
 *
 * @author Isaque
 */
class Perfil {
    private $codigo    = 0;
    private $descricao = "";
    
    function getCodigo() : int {
        return $this->codigo;
    }
    
    function getDescricao() {
        return $this->descricao;
    }
    
    function setCodigo(int $codigo) {
        $this->codigo = $codigo;
    }
    
    function setDescricao($descricao) {
        $this->descricao = $descricao;
    }
}
class Usuario {
    private $codigo    = "";
    private $nome      = "";
    private $email     = "";
    private $senha     = "";
    private $token     = "";
    private Perfil $perfil;
    private $bloqueado = false;
    private $medico    = false;
    private $profissional = 0;
    private $data_ativacao = "";
    private $hora_ativacao = "";
    
    function getCodigo() {
        return $this->codigo;
    }
    
    function getNome() {
        return $this->nome;
    }
    
    function getEmail() {
        return $this->email;
    }
    
    function getSenha() {
        return $this->senha;
    }
    
    function getToken() {
        return $this->token;
    }
    
    function getPerfil(): ?Perfil {
        return $this->perfil;
    }
    
    function getBloqueado() {
        return $this->bloqueio;
    }
    
    function getMedico() {
        return $this->medico;
    }
    
    function getProfissional() {
        return $this->profissional;
    }
    
    function getData_ativacao() {
        return $this->data_ativacao;
    }
    
    function getHora_ativacao() {
        return $this->hora_ativacao;
    }
    
    function setCodigo($codigo) {
        $this->codigo = $codigo;
    }
    
    function setNome($nome) {
        $this->nome = $nome;
    }
    
    function setEmail($email) {
        $this->email = $email;
    }
    
    function setSenha($senha) {
        $this->senha = $senha;
    }
    
    function setToken($token) {
        $this->token = $token;
    }
    
    function setPerfil(Perfil $perfil) {
        $this->perfil = $perfil;
    }
    
    function setBloqueado($bloqueado) {
        $this->bloqueado = $bloqueado;
    }
    
    function setMedico($medico) {
        $this->medico = $medico;
    }
    
    function setProfissional($profissional) {
        $this->profissional = $profissional;
    }
    
    function setData_ativacao($data_ativacao) {
        $this->data_ativacao = $data_ativacao;
    }
    
    function setHora_ativacao($hora_ativacao) {
        $this->hora_ativacao = $hora_ativacao;
    }
}
