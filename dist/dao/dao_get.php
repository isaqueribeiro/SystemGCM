<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function get_atendimento($pdo, $codigo) {
    $retorno = "";
    $tp_atendimento = (int)preg_replace("/[^0-9]/", "", "0" . trim($codigo));

    $qry = $pdo->query("Select * from dbo.vw_tipo_atendimento a where a.cd_tipo = {$tp_atendimento}");
    if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
        $retorno = $obj->ds_tipo;
    }
    unset($qry);
    return $retorno;
}
    
function get_especialidade($pdo, $codigo) {
    $retorno = "";
    $cd_especialidade = (int)preg_replace("/[^0-9]/", "", "0" . trim($codigo));

    $pdo = Conexao::getConnection();
    $qry = $pdo->query("Select * from dbo.tbl_especialidade e where e.cd_especialidade = {$cd_especialidade}");
    if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
        $retorno = $obj->ds_especialidade;
    }
    unset($qry);
    return $retorno;
}


