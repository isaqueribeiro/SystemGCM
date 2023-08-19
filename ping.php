<?php

    require './dist/dao/conexao.php';
    try {
        $pdo = Conexao::getConnection();
        $qry = $pdo->query("Select Top 1 e.id_empresa from dbo.sys_empresa e");
        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
            echo "success";
        } else {
            echo "Erro na conexão com a base de dados!";
        }
        $qry->closeCursor();
    } catch (Exception $exc) {
        echo $exc->getTraceAsString();
    } finally {
        unset($qry);
        unset($pdo);
    }
