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

    function tr_table($cd_profissional, $nm_profissional, $nm_apresentacao, $ds_conselho, $sn_ativo) {
        $referencia = (int)$cd_profissional;

        //$status  = "<span class='label-" . ((int)$sn_ativo === 1?"success":"danger") . " btn-xs' id='reg-status_{$referencia}'>" . ((int)$sn_ativo === 1?"Ativo":"Inativo") . "</span>";
        $status  = "<i id='status_profissional_{$referencia}' class='fa " . ((int)$sn_ativo === 1?"fa-check-square-o text-green":"fa-square-o text-red") . "'></i>";
        $excluir = "<a id='excluir_profissional_{$referencia}' href='javascript:preventDefault();' onclick='excluir_registro( this.id, this )'><i class='fa fa-trash' title='Excluir registro'></i>";

        $retorno =
              "    <tr id='tr-linha_{$referencia}'>  \n"
            . "      <td><a href='#' id='reg-profissional_{$referencia}' onclick='abrir_cadastro(this, this.id);'>" . str_pad($cd_profissional, 2, "0", STR_PAD_LEFT) . "</a></td>  \n"
            . "      <td>{$nm_profissional}</td>        \n"
            . "      <td>{$nm_apresentacao}</td>        \n"
            . "      <td>{$ds_conselho}</td>            \n"
            . "      <td align='center'>{$status}</td>  \n"
            . "      <td align='center'>{$excluir}</td> \n"
            . "    </tr>  \n";
            
        return $retorno;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'pesquisar_profissionais' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $tp_filtro   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tipo'])));
                        $ds_filtro   = strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $qt_registro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/profissional_' . $cookieID . '.json';
                        if (file_exists($file_cookie)) {
                            unlink($file_cookie);
                        }
                        
                        $registros = array('filtro' => array());
                        $registros['filtro'][0]['qt_registro'] = $qt_registro;
                        $registros['filtro'][0]['cd_tipo']     = $tp_filtro;
                        $registros['filtro'][0]['empresa']     = $id_empresa;
                        
                        $filtro = ($tp_filtro === 1?"  and (p.sn_ativo = 1)":"");
                        
                        $json = json_encode($registros);
                        file_put_contents($file_cookie, $json);
                        // Gravar as configurações do filtro utilizado pelo usuário -- (FINAL)
                        
                        $retorno = 
                              "<table id='tb-profissionais' class='table table-bordered table-hover'> \n"
                            . "  <thead>                        \n"
                            . "    <tr>                         \n"
                            . "      <th>#</th>                 \n"
                            . "      <th>Nome Completo</th>     \n"
                            . "      <th>Apresentação</th>      \n"
                            . "      <th>Conselho</th>          \n"
                            . "      <th>Ativo</th>             \n"
                            . "      <th></th>                  \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $sql = 
                              "Select  "
                            . "    p.* "
                            . "from dbo.tbl_profissional p   "
                            . "where (p.id_empresa = '{$id_empresa}') "
                            . ($filtro) . "     "
                            . "order by         "
                            . "    p.nm_profissional  "; 
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $retorno .= tr_table($obj->cd_profissional, $obj->nm_profissional, $obj->nm_apresentacao, $obj->ds_conselho, $obj->sn_ativo);
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
            
                case 'carregar_profissional' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $cd_profissional = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['codigo'])));
                        
                        $file = '../../logs/json/profissional_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    p.* "
                            . "  , coalesce(( "
                            . "	     Select "
                            . "	       STRING_AGG(CONVERT(NVARCHAR(150), ISNULL(cd_especialidade, 0)), ',')  "
                            . "	     from dbo.tbl_profissional_especialidade e "
                            . "	     where e.cd_profissional = p.cd_profissional "
                            . "    ), '0') as especialidades "
                            . "from dbo.tbl_profissional p "
                            . "where (p.id_empresa      = '{$id_empresa}') "
                            . "  and (p.cd_profissional = {$cd_profissional}) "; 
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][0]['codigo']       = $obj->cd_profissional;
                            $registro['registro'][0]['nome']         = $obj->nm_profissional;
                            $registro['registro'][0]['apresentacao'] = $obj->nm_apresentacao;
                            $registro['registro'][0]['conselho']     = $obj->ds_conselho;
                            $registro['registro'][0]['usuario']      = $obj->id_usuario;
                            $registro['registro'][0]['ativo']        = $obj->sn_ativo;
                            $registro['registro'][0]['especialidades'] = $obj->especialidades;
                        } else {
                            $registro['registro'][0]['codigo'] = "0";
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
            
                case 'salvar_profissional' : {
                    try {
                        $id_empresa = strip_tags( trim($_POST['empresa']) );
                        $cd_profissional = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_profissional'])));
                        $nm_profissional = strip_tags( trim($_POST['nm_profissional']) );
                        $nm_apresentacao = strip_tags( trim($_POST['nm_apresentacao']) );
                        $ds_conselho     = strip_tags( trim($_POST['ds_conselho']) );
                        $id_usuario      = strip_tags( trim($_POST['id_usuario']) );
                        $sn_ativo        = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_ativo'])));
                        
                        // Tratamento de dados
                        if (trim($id_usuario) === '') {
                            $id_usuario = "NULL";
                        }
                        
                        $file = '../../logs/json/profissional_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    p.* "
                            . "from dbo.tbl_profissional p "
                            . "where (p.cd_profissional = {$cd_profissional}) "; 
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.tbl_profissional ("
                                . "    nm_profissional "
                                . "  , nm_apresentacao "
                                . "  , ds_conselho     "
                                . "  , id_usuario      "
                                . "  , id_empresa      "
                                . "  , sn_ativo        "
                                . ") "
                                . "    OUTPUT          "
                                . "    INSERTED.cd_profissional "
                                . "  , INSERTED.nm_profissional "
                                . "values (               "
                                . "    :nm_profissional "
                                . "  , :nm_apresentacao "
                                . "  , :ds_conselho     "
                                . "  , " . (trim($id_usuario) !== ""?"'{$id_usuario}'":"NULL") . "  "
                                . "  , :id_empresa      "
                                . "  , :sn_ativo        "
                                . ")");

                            $stm->execute(array(
                                  ':nm_profissional' => $nm_profissional
                                , ':nm_apresentacao' => $nm_apresentacao
                                , ':ds_conselho'     => $ds_conselho
                                , ':id_empresa'      => $id_empresa
                                , ':sn_ativo'        => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            if (($obj = $stm->fetch(PDO::FETCH_OBJ)) !== false) {
                                $tr_table = tr_table($obj->cd_profissional, $obj->nm_profissional, $nm_apresentacao, $ds_conselho, $sn_ativo);
                                
                                $registro['registro'][0]['profissional'] = $obj->cd_profissional;
                                $registro['registro'][0]['nome']         = $nm_profissional;
                                $registro['registro'][0]['apresentacao'] = $nm_apresentacao;
                                $registro['registro'][0]['conselho']     = $ds_conselho;
                                $registro['registro'][0]['id_empresa']   = $id_empresa;
                                $registro['registro'][0]['tr_table']     = $tr_table;
                            }
                            
                            $pdo->commit();

                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        } else {
                            $pdo->beginTransaction();

                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_profissional Set   "
                                . "    nm_profissional   = :nm_profissional "
                                . "  , nm_apresentacao   = :nm_apresentacao "
                                . "  , ds_conselho       = :ds_conselho     "
                                . "  , id_usuario        = " . (trim($id_usuario) !== ""?"'{$id_usuario}'":"NULL") . "  "
                                . "  , id_empresa        = :id_empresa      "
                                . "  , sn_ativo          = :sn_ativo        "
                                . "where cd_profissional = :cd_profissional");

                            $stm->execute(array(
                                  ':cd_profissional' => $cd_profissional
                                , ':nm_profissional' => $nm_profissional
                                , ':nm_apresentacao' => $nm_apresentacao
                                , ':ds_conselho'     => $ds_conselho
                                , ':id_empresa'      => $id_empresa
                                , ':sn_ativo'        => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            $tr_table = tr_table($cd_profissional, $nm_profissional, $nm_apresentacao, $ds_conselho, $sn_ativo);

                            $registro['registro'][0]['profissional'] = $cd_profissional;
                            $registro['registro'][0]['nome']         = $nm_profissional;
                            $registro['registro'][0]['apresentacao'] = $nm_apresentacao;
                            $registro['registro'][0]['conselho']     = $ds_conselho;
                            $registro['registro'][0]['id_empresa']   = $id_empresa;
                            $registro['registro'][0]['tr_table']     = $tr_table;
                            
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
            
                case 'salvar_especialidades' : {
                    try {
                        $id_empresa = strip_tags( trim($_POST['empresa']) );
                        $cd_profissional   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['profissional'])));
                        $cd_especialidades = strip_tags( trim($_POST['especialidades']) );
                        
                        $sql = 
                              "Select  "
                            . "    p.* "
                            . "from dbo.tbl_profissional p "
                            . "where (p.cd_profissional = {$cd_profissional}) "; 
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Delete from dbo.tbl_profissional_especialidade "
                                . "where (cd_profissional = {$cd_profissional})");
                            $stm->execute();
                            
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.tbl_profissional_especialidade ( "
                                . "    cd_profissional "
                                . "  , cd_especialidade "
                                . ") Select "
                                . "      {$cd_profissional} as cd_profissional "
                                . "    , e.cd_especialidade "
                                . "  from dbo.tbl_especialidade e "
                                . "    left join dbo.tbl_profissional_especialidade p on (p.cd_profissional = {$cd_profissional} and p.cd_especialidade = e.cd_especialidade) "
                                . "  where (e.cd_especialidade in ({$cd_especialidades})) "
                                . "    and (e.sn_ativo = 1) "
                                . "    and (p.cd_especialidade is null) ");
                            $stm->execute();
                            
                            $pdo->commit();
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
            
                case 'excluir_profisisonal' : {
                    try {
                        $id_empresa = strip_tags( trim($_POST['empresa']) );
                        $cd_profissional = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['profissional'])));
                        
                        $sql = 
                              "Select  "
                            . "    p.* "
                            . "  , coalesce((                  "
                            . "      Select count(x.cd_agenda) "
                            . "	  from dbo.tbl_agenda x        "
                            . "	  where (x.cd_profissional = p.cd_profissional) "
                            . "    ), 0) as qt_agendamentos "
                            . "from dbo.tbl_profissional p "
                            . "where (p.cd_profissional = {$cd_profissional})";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            if ((int)$obj->qt_agendamentos > 0) {
                                echo "Este registro de profissional possui agendamentos e não poderá ser excluído.";
                            } else {
                                $pdo->beginTransaction();
                                
                                $stm = $pdo->prepare(
                                      "Delete from dbo.tbl_profissional "
                                    . "where (cd_profissional = :cd_profissional)");

                                $stm->execute(array(
                                      ':cd_profissional' => $cd_profissional
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