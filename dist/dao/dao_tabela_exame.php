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

    function tr_table($id_exame, $cd_exame, $nm_exame, $sg_exame, $un_exame, $sn_ativo) {
        $referencia = substr($id_exame, 1, strlen($id_exame) - 2);

        $status  = "<i id='status_exame_{$referencia}' class='fa " . ((int)$sn_ativo === 1?"fa-check-square-o text-green":"fa-square-o text-red") . "'></i>";
        $excluir = "<a id='excluir_exame_{$referencia}' href='javascript:preventDefault();' onclick='excluir_registro( this.id, this )'><i class='fa fa-trash' title='Excluir Registro'></i>";

        $retorno =
              "    <tr id='tr-linha_{$referencia}'>  \n"
            . "      <td><a href='#' id='reg-exame_{$referencia}' onclick='abrir_cadastro(this, this.id);'>" . str_pad($cd_exame, 2, "0", STR_PAD_LEFT) . "</a></td>  \n"
            . "      <td>{$nm_exame}</td>  \n"
            . "      <td>{$sg_exame}</td>  \n"
            . "      <td>" . (trim($un_exame) !== ""?$un_exame:"...") . "</td> \n"
            . "      <td align='center'>{$status}</td>                 \n"
            . "      <td align='center'>{$excluir}</td> \n"
            . "    </tr>  \n";
            
        return $retorno;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'pesquisar_exames' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $tp_filtro   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tipo'])));
                        $ds_filtro   = strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $qt_registro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/tabela_exame_' . $cookieID . '.json';
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
                              "<table id='tb-tabela_exames' class='table table-bordered table-hover'> \n"
                            . "  <thead>                \n"
                            . "    <tr>                 \n"
                            . "      <th>#</th>         \n"
                            . "      <th>Nome</th>      \n"
                            . "      <th>Sigla</th>     \n"
                            . "      <th>Unidade</th>   \n"
                            . "      <th>Ativo</th>     \n"
                            . "      <th></th>          \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $sql = 
                              "Select  "
                            . "    e.* "
                            . "  , dbo.ufnRemoverAcentos(e.nm_exame) as nm_exame_sem_acesso "
                            . "from dbo.tbl_exame e   "
                            . "where (e.id_empresa = '{$id_empresa}') "
                            . "  and (upper(e.nm_exame) like concat('%', '{$ds_filtro}', '%')) "
                            . "order by          "
                            . "    e.nm_exame ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $retorno .= tr_table($obj->id_exame, $obj->cd_exame, $obj->nm_exame_sem_acesso, $obj->sg_exame, $obj->un_exame, $obj->sn_ativo);
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
            
                case 'carregar_exame' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_exame   = "{" . strip_tags( strtoupper(trim($_POST['codigo'])) ) . "}";
                        
                        $file = '../../logs/json/tabela_exame_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    e.* "
                            . "from dbo.tbl_exame e   "
                            . "where (e.id_empresa = '{$id_empresa}') "
                            . "  and (e.id_exame   = '{$id_exame}') ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][0]['id']      = $obj->id_exame;
                            $registro['registro'][0]['codigo']  = $obj->cd_exame;
                            $registro['registro'][0]['nome']    = $obj->nm_exame;
                            $registro['registro'][0]['sigla']   = $obj->sg_exame;
                            $registro['registro'][0]['unidade'] = $obj->un_exame;
                            $registro['registro'][0]['ativo']   = $obj->sn_ativo;
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
            
                case 'salvar_exame' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_exame   = strip_tags( strtoupper(trim($_POST['id_exame'])) );
                        $cd_exame   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_exame'])));
                        $nm_exame   = strip_tags( trim($_POST['nm_exame']) );
                        $sg_exame   = strip_tags( trim($_POST['sg_exame']) );
                        $un_exame   = strip_tags( trim($_POST['un_exame']) );
                        $sn_ativo   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_ativo'])));
                        
                        $file = '../../logs/json/tabela_exame_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    e.* "
                            . "from dbo.tbl_exame e   "
                            . "where (e.id_exame = '{$id_exame}')";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.tbl_exame ("
                                . "    id_exame     "
                                . "  , cd_exame     "
                                . "  , nm_exame     "
                                . "  , sg_exame     "
                                . "  , un_exame     "
                                . "  , id_empresa   "
                                . "  , sn_ativo     "
                                . ") "
                                . "    OUTPUT             "
                                . "    INSERTED.id_exame  "
                                . "  , INSERTED.cd_exame  "
                                . "  , INSERTED.nm_exame  "
                                //. "  , dbo.ufnRemoverAcentos(INSERTED.nm_exame) as nm_exame_sem_acesso  "
                                . "values (               "
                                . "    dbo.ufnGetGuidID() "
                                . "  , dbo.ufnGetNextCodigoExame('{$id_empresa}') "
                                . "  , :nm_exame     "
                                . "  , :sg_exame     "
                                . "  , :un_exame     "
                                . "  , :id_empresa   "
                                . "  , :sn_ativo     "
                                . ")");

                            $stm->execute(array(
                                  ':nm_exame'   => $nm_exame
                                , ':sg_exame'   => $sg_exame
                                , ':un_exame'   => $un_exame
                                , ':id_empresa' => $id_empresa
                                , ':sn_ativo'   => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            if (($obj = $stm->fetch(PDO::FETCH_OBJ)) !== false) {
                                $tr_table = tr_table($obj->id_exame, $obj->cd_exame, $obj->nm_exame, $sg_exame, $un_exame, $sn_ativo);
                                
                                $registro['registro'][0]['id']         = $obj->id_exame;
                                $registro['registro'][0]['referencia'] = substr($obj->id_exame, 1, strlen($obj->id_exame) - 2);
                                $registro['registro'][0]['codigo']     = $obj->cd_exame;
                                $registro['registro'][0]['nome']       = $nm_exame;
                                $registro['registro'][0]['sigla']      = $sg_exame;
                                $registro['registro'][0]['unidade']    = $un_exame;
                                $registro['registro'][0]['tr_table']   = $tr_table;
                            }
                            
                            $pdo->commit();

                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        } else {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_exame Set "
                                . "    nm_exame = :nm_exame "
                                . "  , sg_exame = :sg_exame "
                                . "  , un_exame = :un_exame "
                                . "  , sn_ativo = :sn_ativo "
                                . "where id_exame = :id_exame");

                            $stm->execute(array(
                                  ':id_exame' => $id_exame
                                , ':nm_exame' => $nm_exame
                                , ':sg_exame' => $sg_exame
                                , ':un_exame' => $un_exame
                                , ':sn_ativo' => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            $tr_table = tr_table($id_exame, $cd_exame, $nm_exame, $sg_exame, $un_exame, $sn_ativo);

                            $registro['registro'][0]['id']         = $id_exame;
                            $registro['registro'][0]['referencia'] = substr($id_exame, 1, strlen($id_exame) - 2);
                            $registro['registro'][0]['codigo']     = $cd_exame;
                            $registro['registro'][0]['nome']       = $nm_exame;
                            $registro['registro'][0]['sigla']      = $sg_exame;
                            $registro['registro'][0]['unidade']    = $un_exame;
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
            
                case 'excluir_exame' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_exame   = "{" . strip_tags( strtoupper(trim($_POST['exame'])) ) . "}";
                        
                        $sql = 
                              "Select  "
                            . "    e.* "
                            . "  , coalesce(( "
                            . "      Select count(x.cd_paciente) "
                            . "	  from dbo.tbl_exame_paciente x  "
                            . "	  where x.id_exame = e.id_exame  "
                            . "    ), 0) as qt_pacientes    "
                            . "from dbo.tbl_exame e "
                            . "where (e.id_exame   = '{$id_exame}')  "
                            . "  and (e.id_empresa = '{$id_empresa}')";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            if ((int)$obj->qt_pacientes > 0) {
                                echo "Este registro de exame possui associações e não poderá ser excluído.";
                            } else {
                                $pdo->beginTransaction();
                                $stm = $pdo->prepare(
                                      "Delete from dbo.tbl_exame     "
                                    . "where id_exame = :id_exame");

                                $stm->execute(array(
                                      ':id_exame' => $id_exame
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