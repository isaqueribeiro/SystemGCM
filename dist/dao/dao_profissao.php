<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

    ini_set('display_errors', true);
    error_reporting(E_ALL);
    date_default_timezone_set('America/Belem');

    require_once '../php/constantes.php';
    require_once '../dao/conexao.php';
    require_once '../php/usuario.php';
    require_once '../php/funcoes.php';
    
    session_start();
    $user = new Usuario();
    if ( isset($_SESSION['user']) ) {
        $user = unserialize($_SESSION['user']);
    } else {
        header('location: ./index.php');
        exit;
    }
    
    $ano = date("Y");
    $tokenID  = $user->getToken(); // sha1(date("d/m/Y") . $user->getCodigo() . $_SERVER["REMOTE_ADDR"]);
    $cookieID = sha1($user->getCodigo());

    if (!isset($_POST['token'])) {
        echo painel_alerta_danger("Permissão negada, pois o TokenID de segurança não está sendo carregado.");        
        exit;
    } else {
        if ($_POST['token'] !== $tokenID) {
            echo painel_alerta_danger("Permissão negada, pois o TokenID de segurança informado é inválido.");        
            exit;
        }
    }

    function tr_table($cd_profissao, $ds_profissao, $nr_cbo, $sn_ativo) {
        $referencia = (int)$cd_profissao;

        //$status  = "<span class='label-" . ((int)$sn_ativo === 1?"success":"danger") . " btn-xs' id='reg-status_{$referencia}'>" . ((int)$sn_ativo === 1?"Ativo":"Inativo") . "</span>";
        $status  = "<i id='status_profissao_{$referencia}' class='fa " . ((int)$sn_ativo === 1?"fa-check-square-o text-green":"fa-square-o text-red") . "'></i>";
        $excluir = "<a id='excluir_profissao_{$referencia}' href='javascript:preventDefault();' onclick='excluir_registro( this.id, this )'><i class='fa fa-trash' title='Excluir Registro'></i>";

        $retorno =
              "    <tr id='tr-linha_{$referencia}'>  \n"
            . "      <td><a href='#' id='reg-profissao_{$referencia}' onclick='abrir_cadastro(this, this.id);'>" . str_pad($cd_profissao, 3, "0", STR_PAD_LEFT) . "</a></td>  \n"
            . "      <td>{$ds_profissao}</td>  \n"
            . "      <td>" . str_pad($nr_cbo, 6, "0", STR_PAD_LEFT) . "</td>  \n"
            . "      <td align='center'>{$status}</td>                 \n"
            . "      <td align='center'>{$excluir}</td> \n"
            . "    </tr>  \n";
            
        return $retorno;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'pesquisar_profissoes' : {
                    try {
                        $tp_filtro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tipo'])));
                        $ds_filtro = strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $qt_registro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/profissao_' . $cookieID . '.json';
                        if (file_exists($file_cookie)) {
                            unlink($file_cookie);
                        }
                        
                        $registros = array('filtro' => array());
                        $registros['filtro'][0]['qt_registro'] = $qt_registro;
                        $registros['filtro'][0]['cd_tipo']     = $tp_filtro;
                        
                        $json = json_encode($registros);
                        file_put_contents($file_cookie, $json);
                        // Gravar as configurações do filtro utilizado pelo usuário -- (FINAL)
                        
                        $retorno = 
                              "<table id='tb-profissoes' class='table table-bordered table-hover'> \n"
                            . "  <thead>                    \n"
                            . "    <tr>                     \n"
                            . "      <th>#</th>             \n"
                            . "      <th>Descrição</th>     \n"
                            . "      <th>CBO</th>           \n"
                            . "      <th>Ativo</th>         \n"
                            . "      <th></th>              \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $sql = 
                              "Select  "
                            . "    p.* "
                            . "from dbo.tbl_profissao p   "
                            . "where (upper(p.ds_profissao) like concat('%', '{$ds_filtro}', '%')) "
                            . "order by          "
                            . "    p.ds_profissao ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $retorno .= tr_table($obj->cd_profissao, $obj->ds_profissao, $obj->nr_cbo, $obj->sn_ativo);
                        }

                        $retorno .=
                              "  </tbody> \n"
                            . "</table>   \n";


                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                        
                        echo $retorno;
                    } catch (Exception $ex) {
                        echo $ex . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                    } 
                } break;
            
                case 'carregar_profissao' : {
                    try {
                        $cd_profissao = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['codigo'])));
                        
                        $file = '../../logs/json/profissao_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    p.* "
                            . "from dbo.tbl_profissao p  "
                            . "where (p.cd_profissao = {$cd_profissao}) ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][0]['codigo']    = $obj->cd_profissao;
                            $registro['registro'][0]['descricao'] = $obj->ds_profissao;
                            $registro['registro'][0]['cbo']       = $obj->nr_cbo;
                            $registro['registro'][0]['ativo']     = $obj->sn_ativo;
                        }
                        
                        $json = json_encode($registro);
                        file_put_contents($file, $json);
                        
                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                        
                        echo "OK";
                    } catch (Exception $ex) {
                        echo $ex . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                    } 
                } break;
            
                case 'salvar_profissao' : {
                    try {
                        $cd_profissao = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_profissao'])));
                        $ds_profissao = strip_tags( trim($_POST['ds_profissao']) );
                        $nr_cbo       = preg_replace("/[^0-9]/", "", strip_tags( strtoupper(trim($_POST['nr_cbo'])) ) );
                        $sn_ativo     = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_ativo'])));
                        
                        $nr_cbo = str_pad($nr_cbo, 6, "0", STR_PAD_LEFT);
                                
                        $file = '../../logs/json/profissao_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    p.* "
                            . "from dbo.tbl_profissao p   "
                            . "where (p.cd_profissao = {$cd_profissao})";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.tbl_profissao ("
                                . "    ds_profissao "
                                . "  , nr_cbo       "
                                . "  , sn_ativo     "
                                . ") "
                                . "    OUTPUT                "
                                . "    INSERTED.cd_profissao "
                                . "  , INSERTED.ds_profissao "
                                . "values (          "
                                . "    :ds_profissao "
                                . "  , :nr_cbo       "
                                . "  , :sn_ativo     "
                                . ")");

                            $stm->execute(array(
                                  ':ds_profissao' => $ds_profissao
                                , ':nr_cbo'       => $nr_cbo
                                , ':sn_ativo'     => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            if (($obj = $stm->fetch(PDO::FETCH_OBJ)) !== false) {
                                $tr_table = tr_table($obj->cd_profissao, $ds_profissao, $nr_cbo, $sn_ativo);
                                
                                $registro['registro'][0]['profissao'] = $obj->cd_profissao;
                                $registro['registro'][0]['descricao'] = $obj->ds_profissao;
                                $registro['registro'][0]['cbo']       = $nr_cbo;
                                $registro['registro'][0]['tr_table']  = $tr_table;
                            }
                            
                            $pdo->commit();

                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        } else {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_profissao Set "
                                . "    ds_profissao = :ds_profissao "
                                . "  , nr_cbo       = :nr_cbo "
                                . "  , sn_ativo     = :sn_ativo "
                                . "where cd_profissao = :cd_profissao");

                            $stm->execute(array(
                                  ':cd_profissao' => $cd_profissao
                                , ':ds_profissao' => $ds_profissao
                                , ':nr_cbo'       => $nr_cbo
                                , ':sn_ativo'     => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            $tr_table = tr_table($cd_profissao, $ds_profissao, $nr_cbo, $sn_ativo);

                            $registro['registro'][0]['profissao'] = $cd_profissao;
                            $registro['registro'][0]['descricao'] = $ds_profissao;
                            $registro['registro'][0]['cbo']       = $nr_cbo;
                            $registro['registro'][0]['tr_table']  = $tr_table;
                            
                            $pdo->commit();

                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        }

                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                        
                        echo "OK";

                    } catch (Exception $ex) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        echo $ex . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                    } 
                } break;
            
                case 'excluir_profissao' : {
                    try {
                        $cd_profissao = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['profissao'])));
                        
                        $sql = 
                              "Select  "
                            . "    p.* "
                            . "  , coalesce(( "
                            . "      Select count(x.cd_paciente)        "
                            . "	  from dbo.tbl_paciente x               "
                            . "	  where x.cd_profissao = p.cd_profissao "
                            . "    ), 0) as qt_pacientes    "
                            . "from dbo.tbl_profissao p     "
                            . "where (p.cd_profissao = {$cd_profissao})";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            if ((int)$obj->qt_pacientes > 0) {
                                echo "Este registro de profissão possui associações e não poderá ser excluído.";
                            } else {
                                $pdo->beginTransaction();
                                $stm = $pdo->prepare(
                                      "Delete from dbo.tbl_profissao     "
                                    . "where cd_profissao = :cd_profissao");

                                $stm->execute(array(
                                      ':cd_profissao' => $cd_profissao
                                ));

                                $pdo->commit();

                                // Fechar conexão PDO
                                unset($stm);
                                unset($pdo);

                                echo "OK";
                            }
                        }
                    } catch (Exception $ex) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        echo $ex . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                    } 
                } break;
            }
        } else {
            echo painel_alerta_danger("Permissão negada, pois a ação não foi definida.");        
        }
    }