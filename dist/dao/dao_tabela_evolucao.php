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

    function tr_table($id_evolucao, $cd_evolucao, $ds_evolucao, $un_evolucao, $sn_ativo) {
        $referencia = substr($id_evolucao, 1, strlen($id_evolucao) - 2);

        $status  = "<i id='status_evolucao_{$referencia}' class='fa " . ((int)$sn_ativo === 1?"fa-check-square-o text-green":"fa-square-o text-red") . "'></i>";
        $excluir = "<a id='excluir_evolucao_{$referencia}' href='javascript:preventDefault();' onclick='excluir_registro( this.id, this )'><i class='fa fa-trash' title='Excluir Registro'></i>";

        $retorno =
              "    <tr id='tr-linha_{$referencia}'>  \n"
            . "      <td><a href='#' id='reg-exame_{$referencia}' onclick='abrir_cadastro(this, this.id);'>" . str_pad($cd_evolucao, 2, "0", STR_PAD_LEFT) . "</a></td>  \n"
            . "      <td>{$ds_evolucao}</td>  \n"
            . "      <td>" . (trim($un_evolucao) !== ""?$un_evolucao:"...") . "</td> \n"
            . "      <td align='center'>{$status}</td>                 \n"
            . "      <td align='center'>{$excluir}</td> \n"
            . "    </tr>  \n";
            
        return $retorno;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'pesquisar_evolucoes' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $tp_filtro   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tipo'])));
                        $ds_filtro   = strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $qt_registro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/tabela_evolucao_' . $cookieID . '.json';
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
                              "<table id='tb-tabela_evolucoes' class='table table-bordered table-hover'> \n"
                            . "  <thead>                \n"
                            . "    <tr>                 \n"
                            . "      <th>#</th>         \n"
                            . "      <th>Descrição</th> \n"
                            . "      <th>Unidade</th>   \n"
                            . "      <th>Ativo</th>     \n"
                            . "      <th></th>          \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $sql = 
                              "Select  "
                            . "    e.* "
                            . "  , dbo.ufnRemoverAcentos(e.ds_evolucao) as ds_evolucao_sem_acesso "
                            . "from dbo.tbl_evolucao e   "
                            . "where (e.id_empresa = '{$id_empresa}') "
                            . "  and (upper(e.ds_evolucao) like concat('%', '{$ds_filtro}', '%')) "
                            . "order by          "
                            . "    e.ds_evolucao ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $retorno .= tr_table($obj->id_evolucao, $obj->cd_evolucao, $obj->ds_evolucao_sem_acesso, $obj->un_evolucao, $obj->sn_ativo);
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
            
                case 'carregar_evolucao' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_evolucao = "{" . strip_tags( strtoupper(trim($_POST['codigo'])) ) . "}";
                        
                        $file = '../../logs/json/tabela_evolucao_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    e.* "
                            . "from dbo.tbl_evolucao e   "
                            . "where (e.id_empresa  = '{$id_empresa}') "
                            . "  and (e.id_evolucao = '{$id_evolucao}') ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][0]['id']        = $obj->id_evolucao;
                            $registro['registro'][0]['codigo']    = $obj->cd_evolucao;
                            $registro['registro'][0]['descricao'] = $obj->ds_evolucao;
                            $registro['registro'][0]['unidade']   = $obj->un_evolucao;
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
            
                case 'salvar_evolucao' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_evolucao = strip_tags( strtoupper(trim($_POST['id_evolucao'])) );
                        $cd_evolucao = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_evolucao'])));
                        $ds_evolucao = strip_tags( trim($_POST['ds_evolucao']) );
                        $un_evolucao = strip_tags( trim($_POST['un_evolucao']) );
                        $sn_ativo   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_ativo'])));
                        
                        $file = '../../logs/json/tabela_evolucao_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    e.* "
                            . "from dbo.tbl_evolucao e   "
                            . "where (e.id_evolucao = '{$id_evolucao}')";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.tbl_evolucao ("
                                . "    id_evolucao  "
                                . "  , cd_evolucao  "
                                . "  , ds_evolucao  "
                                . "  , un_evolucao  "
                                . "  , id_empresa   "
                                . "  , sn_ativo     "
                                . ") "
                                . "    OUTPUT               "
                                . "    INSERTED.id_evolucao "
                                . "  , INSERTED.cd_evolucao "
                                . "  , INSERTED.ds_evolucao "
                                //. "  , dbo.ufnRemoverAcentos(INSERTED.ds_evolucao) as ds_evolucao_sem_acesso  "
                                . "values (               "
                                . "    dbo.ufnGetGuidID() "
                                . "  , dbo.ufnGetNextCodigoEvolucao('{$id_empresa}') "
                                . "  , :ds_evolucao  "
                                . "  , :un_evolucao  "
                                . "  , :id_empresa   "
                                . "  , :sn_ativo     "
                                . ")");

                            $stm->execute(array(
                                  ':ds_evolucao' => $ds_evolucao
                                , ':un_evolucao' => $un_evolucao
                                , ':id_empresa'  => $id_empresa
                                , ':sn_ativo'    => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            if (($obj = $stm->fetch(PDO::FETCH_OBJ)) !== false) {
                                $tr_table = tr_table($obj->id_evolucao, $obj->cd_evolucao, $obj->ds_evolucao, $un_evolucao, $sn_ativo);
                                
                                $registro['registro'][0]['id']         = $obj->id_evolucao;
                                $registro['registro'][0]['referencia'] = substr($obj->id_evolucao, 1, strlen($obj->id_evolucao) - 2);
                                $registro['registro'][0]['codigo']     = $obj->cd_evolucao;
                                $registro['registro'][0]['descricao']  = $ds_evolucao;
                                $registro['registro'][0]['unidade']    = $un_evolucao;
                                $registro['registro'][0]['tr_table']   = $tr_table;
                            }
                            
                            $pdo->commit();

                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        } else {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_evolucao Set    "
                                . "    ds_evolucao   = :ds_evolucao "
                                . "  , un_evolucao   = :un_evolucao "
                                . "  , sn_ativo      = :sn_ativo "
                                . "where id_evolucao = :id_evolucao");

                            $stm->execute(array(
                                  ':id_evolucao' => $id_evolucao
                                , ':ds_evolucao' => $ds_evolucao
                                , ':un_evolucao' => $un_evolucao
                                , ':sn_ativo'    => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            $tr_table = tr_table($id_evolucao, $cd_evolucao, $ds_evolucao, $un_evolucao, $sn_ativo);

                            $registro['registro'][0]['id']         = $id_evolucao;
                            $registro['registro'][0]['referencia'] = substr($id_evolucao, 1, strlen($id_evolucao) - 2);
                            $registro['registro'][0]['codigo']     = $cd_evolucao;
                            $registro['registro'][0]['descricao']  = $ds_evolucao;
                            $registro['registro'][0]['unidade']    = $un_evolucao;
                            $registro['registro'][0]['tr_table']   = $tr_table;
                            
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
            
                case 'excluir_evolucao' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_evolucao = "{" . strip_tags( strtoupper(trim($_POST['evolucao'])) ) . "}";
                        
                        $sql = 
                              "Select  "
                            . "    e.* "
                            . "  , coalesce(( "
                            . "      Select count(x.cd_paciente)      "
                            . "	  from dbo.tbl_evolucao_medida_pac x  "
                            . "	  where x.id_evolucao = e.id_evolucao "
                            . "    ), 0) as qt_pacientes    "
                            . "from dbo.tbl_evolucao e "
                            . "where (e.id_evolucao = '{$id_evolucao}')  "
                            . "  and (e.id_empresa  = '{$id_empresa}')";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            if ((int)$obj->qt_pacientes > 0) {
                                echo "Este registro de evolução de medida possui associações e não poderá ser excluído.";
                            } else {
                                $pdo->beginTransaction();
                                $stm = $pdo->prepare(
                                      "Delete from dbo.tbl_evolucao  "
                                    . "where id_evolucao = :id_evolucao");

                                $stm->execute(array(
                                      ':id_evolucao' => $id_evolucao
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