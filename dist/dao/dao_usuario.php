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

    function tr_table($id_usuario, $cd_usuario, $nm_usuario, $ds_email, $ds_perfil, $sn_ativo) {
        $referencia = substr($id_usuario, 1, strlen($id_usuario) - 2);

        $status  = "<i id='status_usuario_{$referencia}' class='fa " . ((int)$sn_ativo === 1?"fa-check-square-o text-green":"fa-square-o text-red") . "'></i>";
        $excluir = "<a id='excluir_usuario_{$referencia}' href='javascript:void(0);' onclick='excluir_registro( this.id, this )'><i class='fa fa-trash' title='Excluir Registro'></i>";

        $retorno =
              "    <tr id='tr-linha_{$referencia}'>  \n"
            . "      <td><a href='javascript:void(0);' id='reg-usuario_{$referencia}' onclick='abrir_cadastro(this, this.id);'>" . str_pad($cd_usuario, 2, "0", STR_PAD_LEFT) . "</a></td>  \n"
            . "      <td>{$nm_usuario}</td>  \n"
            . "      <td>{$ds_email}</td>  \n"
            . "      <td>" . (trim($ds_perfil) !== ""?$ds_perfil:"...") . "</td> \n"
            . "      <td align='center'>{$status}</td>                 \n"
            . "      <td align='center'>{$excluir}</td> \n"
            . "    </tr>  \n";
            
        return $retorno;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'pesquisar_usuarios' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $tp_filtro   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tipo'])));
                        $ds_filtro   = strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $qt_registro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/controle_usuario_' . $cookieID . '.json';
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
                              "<table id='tb-tabela_usuarios' class='table table-bordered table-hover'> \n"
                            . "  <thead>               \n"
                            . "    <tr>                \n"
                            . "      <th>#</th>        \n"
                            . "      <th>Nome</th>     \n"
                            . "      <th>Login</th>    \n"
                            . "      <th>Perfil</th>   \n"
                            . "      <th>Ativo</th>    \n"
                            . "      <th></th>         \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $sql = 
                              "Select   "
                            . "    u.*  "
                            . "  , p.ds_perfil   "
                            . "  , e.id_empresa  "
                            . "  , e.sn_ativo    "
                            . "  , e.dh_ativacao "
                            . "  , convert(varchar(12), e.dh_ativacao, 103) as dt_ativacao "
                            . "  , convert(varchar(8),  e.dh_ativacao, 108) as hr_ativacao "
                            . "from dbo.sys_usuario u  "
                            . "  left join dbo.sys_usuario_empresa e on (e.id_empresa = '{$id_empresa}' and e.id_usuario = u.id_usuario)  "
                            . "  left join dbo.sys_perfil p on (p.cd_perfil = u.cd_perfil)  "
                            . "where (upper(u.nm_usuario) like concat('%', '{$ds_filtro}', '%')) "
                            . "   or (upper(u.ds_email)   like concat('%', '{$ds_filtro}', '%')) "
                            . "order by         "
                            . "    u.nm_usuario ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $ds_perfil = (isset($obj->ds_perfil)?$obj->ds_perfil:"");
                            $retorno .= tr_table($obj->id_usuario, $obj->cd_usuario, $obj->nm_usuario, $obj->ds_email, $ds_perfil, $obj->sn_ativo);
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
            
                case 'carregar_usuario' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_usuario = "{" . strip_tags( strtoupper(trim($_POST['codigo'])) ) . "}";
                        
                        $file = '../../logs/json/usuario_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select   "
                            . "    u.*  "
                            . "  , p.ds_perfil   "
                            . "  , e.id_empresa  "
                            . "  , e.sn_ativo    "
                            . "  , e.dh_ativacao "
                            . "  , convert(varchar(12), e.dh_ativacao, 103) as dt_ativacao "
                            . "  , convert(varchar(8),  e.dh_ativacao, 108) as hr_ativacao "
                            . "from dbo.sys_usuario u  "
                            . "  left join dbo.sys_usuario_empresa e on (e.id_empresa = '{$id_empresa}' and e.id_usuario = u.id_usuario)  "
                            . "  left join dbo.sys_perfil p on (p.cd_perfil = u.cd_perfil)  "
                            . "where (u.id_usuario = '{$id_usuario}') ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][0]['id']         = $obj->id_usuario;
                            $registro['registro'][0]['codigo']     = $obj->cd_usuario;
                            $registro['registro'][0]['nome']       = $obj->nm_usuario;
                            $registro['registro'][0]['email']      = $obj->ds_email;
                            $registro['registro'][0]['empresa']    = (isset($obj->id_empresa)?$obj->id_empresa:"");
                            $registro['registro'][0]['perfil']     = (isset($obj->cd_perfil)?$obj->cd_perfil:"0");
                            $registro['registro'][0]['plataforma'] = $obj->tp_plataforma;
                            $registro['registro'][0]['ativacao']   = $obj->dt_ativacao . " " . $obj->hr_ativacao;
                            $registro['registro'][0]['ativo']      = $obj->sn_ativo;
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
            
                case 'salvar_usuario' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_usuario = strip_tags( strtoupper(trim($_POST['id_usuario'])) );
                        $cd_usuario = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_usuario'])));
                        $nm_usuario = strip_tags( trim($_POST['nm_usuario']) );
                        $cd_perfil  = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_perfil'])));
                        $ds_email   = strip_tags( strtolower(trim($_POST['ds_email'])) );
                        $ds_senha   = strip_tags( trim($_POST['ds_senha']) );
                        $sn_ativo   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_ativo'])));

                        if (!filter_var($ds_email, FILTER_VALIDATE_EMAIL)) {
                            echo "E-mail inválido!";
                            exit;
                        }
                        
                        $sql = 
                              "Select  "
                            . "    u.* "
                            . "from dbo.sys_usuario u  "
                            . "where (u.id_usuario <> '{$id_usuario}')"
                            . "  and (u.ds_email    = '{$ds_email}')  "; 
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            echo "O e-mail <strong>{$ds_email}</strong> já está associado ao usuário <strong>{$obj->nm_usuario}</strong> que já está cadastrado";
                            exit;
                        }
                        
                        $file = '../../logs/json/usuario_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        if ($cd_perfil === 0) { $cd_perfil = null; }
                        
                        $sql = 
                              "Select  "
                            . "    u.* "
                            . "from dbo.sys_usuario u  "
                            . "where (u.id_usuario = '{$id_usuario}')";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.sys_usuario ("
                                . "    id_usuario   "
                                . "  , nm_usuario   "
                                . "  , cd_perfil    "
                                . "  , ds_email     "
                                . "  , ds_senha     "
                                . ") "
                                . "    OUTPUT              "
                                . "    INSERTED.id_usuario "
                                . "  , INSERTED.cd_usuario "
                                . "  , INSERTED.nm_usuario "
                                . "values (                "
                                . "    dbo.ufnGetGuidID()  "
                                . "  , :nm_usuario   "
                                . "  , :cd_perfil    "
                                . "  , :ds_email     "
                                . "  , :ds_senha     "
                                . ")");

                            $stm->execute(array(
                                  ':nm_usuario' => $nm_usuario
                                , ':cd_perfil'  => $cd_perfil
                                , ':ds_email'   => $ds_email
                                , ':ds_senha'   => sha1($ds_email . $ds_senha)
                            ));
                            
                            $registro = array('registro' => array());

                            if (($obj = $stm->fetch(PDO::FETCH_OBJ)) !== false) {
                                $stm = $pdo->prepare("exec dbo.setUsuarioEmpresa N'{$id_empresa}', N'{$obj->id_usuario}', {$sn_ativo}");
                                $stm->execute();
                                
                                $tr_table = tr_table($obj->id_usuario, $obj->cd_usuario, $obj->nm_usuario, $ds_email, "...", $sn_ativo);
                                
                                $registro['registro'][0]['id']         = $obj->id_usuario;
                                $registro['registro'][0]['referencia'] = substr($obj->id_usuario, 1, strlen($obj->id_usuario) - 2);
                                $registro['registro'][0]['codigo']     = $obj->cd_usuario;
                                $registro['registro'][0]['nome']       = $nm_usuario;
                                $registro['registro'][0]['email']      = $ds_email;
                                $registro['registro'][0]['empresa']    = $id_empresa;
                                $registro['registro'][0]['perfil']     = $cd_perfil;
                                $registro['registro'][0]['ativo']      = $sn_ativo;
                                $registro['registro'][0]['tr_table']   = $tr_table;
                            }
                            
                            $pdo->commit();

                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        } else {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Update dbo.sys_usuario Set     "
                                . "    nm_usuario   = :nm_usuario "
                                . "  , ds_email     = :ds_email   "
                                . "  , cd_perfil    = :cd_perfil  "
                                . "where id_usuario = :id_usuario");

                            $stm->execute(array(
                                  ':id_usuario' => $id_usuario
                                , ':nm_usuario' => $nm_usuario
                                , ':ds_email'   => $ds_email
                                , ':cd_perfil'  => $cd_perfil
                            ));
                            
                            $stm = $pdo->prepare("exec dbo.setUsuarioEmpresa N'{$id_empresa}', N'{$obj->id_usuario}', {$sn_ativo}");
                            $stm->execute();
                            
                            if ($ds_senha !== "") {
                                $stm = $pdo->prepare(
                                      "Update dbo.sys_usuario Set    "
                                    . "    ds_senha     = :ds_senha  "
                                    . "where id_usuario = :id_usuario");

                                $stm->execute(array(
                                      ':id_usuario' => $id_usuario
                                    , ':ds_senha'   => sha1($ds_email . $ds_senha)
                                ));
                            }
                            
                            $registro = array('registro' => array());

                            $tr_table = tr_table($id_usuario, $cd_usuario, $nm_usuario, $ds_email, "...", $sn_ativo);

                            $registro['registro'][0]['id']         = $id_usuario;
                            $registro['registro'][0]['referencia'] = substr($id_usuario, 1, strlen($id_usuario) - 2);
                            $registro['registro'][0]['codigo']     = $cd_usuario;
                            $registro['registro'][0]['nome']       = $nm_usuario;
                            $registro['registro'][0]['email']      = $ds_email;
                            $registro['registro'][0]['empresa']    = $id_empresa;
                            $registro['registro'][0]['perfil']     = ($cd_perfil !== null?$cd_perfil:"0");
                            $registro['registro'][0]['ativo']      = $sn_ativo;
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
            
                case 'excluir_usuario' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_usuario = "{" . strip_tags( strtoupper(trim($_POST['usuario'])) ) . "}";
                        
                        $sql = 
                              "Select  "
                            . "    u.* "
                            . "  , coalesce(( "
                            . "      Select count(a.id_agenda) "
                            . "	  from dbo.tbl_agenda a        "
                            . "	  where (a.dt_agenda < getdate()) "
                            . "	    and (a.st_agenda > 0)         "
                            . "	    and (coalesce(a.us_alteracao, a.us_insercao) = u.id_usuario) "
                            . "    ), 0) as qt_agendamentos "
                            . "from dbo.sys_usuario u "
                            . "  inner join dbo.sys_usuario_empresa e on (e.id_empresa = '{$id_empresa}' and e.id_usuario = u.id_usuario)  "
                            . "where (u.id_usuario = '{$id_usuario}') "; 
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            if ((int)$obj->qt_agendamentos > 0) {
                                echo "Este registro de usuário possui associações e não poderá ser excluído.";
                            } else {
                                $pdo->beginTransaction();
                                $stm = $pdo->prepare(
                                      "Delete from dbo.sys_usuario     "
                                    . "where id_usuario = :id_usuario");

                                $stm->execute(array(
                                      ':id_usuario' => $id_usuario
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