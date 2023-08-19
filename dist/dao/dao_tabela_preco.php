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

    function tr_table($cd_tabela, $nm_tabela, $vl_servico, $sn_ativo) {
        $referencia = (int)$cd_tabela;

        //$status  = "<span class='label-" . ((int)$sn_ativo === 1?"success":"danger") . " btn-xs' id='reg-status_{$referencia}'>" . ((int)$sn_ativo === 1?"Ativo":"Inativo") . "</span>";
        $status  = "<i id='status_tabela_preco_{$referencia}' class='fa " . ((int)$sn_ativo === 1?"fa-check-square-o text-green":"fa-square-o text-red") . "'></i>";
        $excluir = "<a id='excluir_tabela_preco_{$referencia}' href='javascript:preventDefault();' onclick='excluir_registro( this.id, this )'><i class='fa fa-trash' title='Excluir Registro'></i>";

        $retorno =
              "    <tr id='tr-linha_{$referencia}'>  \n"
            . "      <td><a href='#' id='reg-tabela_preco_{$referencia}' onclick='abrir_cadastro(this, this.id);'>" . str_pad($cd_tabela, 3, "0", STR_PAD_LEFT) . "</a></td>  \n"
            . "      <td>{$nm_tabela}</td>   \n"
            . "      <td align='right'>" . number_format($vl_servico, 2, ",", ".") . "</td> \n" 
            . "      <td align='center'>{$status}</td>  \n"
            . "      <td align='center'>{$excluir}</td> \n"
            . "    </tr>  \n";
            
        return $retorno;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'pesquisar_tabela_precos' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $tp_filtro   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tipo'])));
                        $ds_filtro   = strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $qt_registro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/tabela_preco_' . $cookieID . '.json';
                        if (file_exists($file_cookie)) {
                            unlink($file_cookie);
                        }
                        
                        $registros = array('filtro' => array());
                        $registros['filtro'][0]['qt_registro'] = $qt_registro;
                        $registros['filtro'][0]['cd_tipo']     = $tp_filtro;
                        
                        $filtro = ($tp_filtro === 1?"  and (t.sn_ativo = 1)":"");
                        
                        $json = json_encode($registros);
                        file_put_contents($file_cookie, $json);
                        // Gravar as configurações do filtro utilizado pelo usuário -- (FINAL)
                        
                        $retorno = 
                              "<table id='tb-tabela_precos' class='table table-bordered table-hover'> \n"
                            . "  <thead>                    \n"
                            . "    <tr>                     \n"
                            . "      <th>#</th>             \n"
                            . "      <th>Descrição</th>     \n"
                            . "      <th>Valor (R$)</th>    \n"
                            . "      <th>Ativo</th>         \n"
                            . "      <th></th>              \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $sql = 
                              "Select  "
                            . "    t.* "
                            . "from dbo.tbl_tabela_cobranca t         "
                            . "where (t.id_empresa = '{$id_empresa}') " 
                            . "  and (upper(t.nm_tabela) like concat('%', '{$ds_filtro}', '%')) " 
                            . ($filtro) . "       "
                            . "order by           "
                            . "    t.nm_tabela ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $retorno .= tr_table($obj->cd_tabela, $obj->nm_tabela, $obj->vl_servico, $obj->sn_ativo);
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
            
                case 'carregar_tabela_preco' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $cd_tabela  = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['codigo'])));
                        
                        $file = '../../logs/json/tabela_preco_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    t.* "
                            . "  , coalesce(t.cd_convenio, 0)      as convenio "
                            . "  , coalesce(t.tp_atendimento, 0)   as atendimento "
                            . "  , coalesce(t.cd_especialidade, 0) as especialidade "
                            . "  , coalesce(t.vl_servico, 0.0)     as valor "
                            . "from dbo.tbl_tabela_cobranca t  "
                            . "where (t.cd_tabela  = {$cd_tabela})   "
                            . "  and (t.id_empresa = '{$id_empresa}') ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][0]['codigo']        = $obj->cd_tabela;
                            $registro['registro'][0]['nome']          = $obj->nm_tabela;
                            $registro['registro'][0]['convenio']      = $obj->convenio;
                            $registro['registro'][0]['atendimento']   = $obj->atendimento;
                            $registro['registro'][0]['especialidade'] = $obj->especialidade;
                            $registro['registro'][0]['valor']         = number_format($obj->valor, 2, ",", ".");
                            $registro['registro'][0]['ativo']         = $obj->sn_ativo;
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
            
                case 'buscar_tabela_valor' : {
                    try {
                        $id_empresa       = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $cd_convenio      = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['convenio'])));
                        $cd_especialidade = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['especialidade'])));
                        $tp_atendimento   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['atendimento'])));
                        
                        $file = '../../logs/json/tabela_preco_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query("Exec dbo.getTabelaValor N'{$id_empresa}', {$cd_convenio}, {$cd_especialidade}, {$tp_atendimento}");
                        
                        $registro = array('registro' => array());
                        $i = 0;
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][$i]['codigo'] = $obj->cd_tabela;
                            $registro['registro'][$i]['nome']   = $obj->nm_tabela;
                            $registro['registro'][$i]['valor']  = number_format($obj->vl_servico, 2, ",", ".");
                            $i += 1;
                        }
                        
                        if ($i === 0) {
                            $registro['registro'][$i]['codigo'] = "0";
                            $registro['registro'][$i]['nome']   = "...";
                            $registro['registro'][$i]['valor']  = "0,00";
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
            
                case 'salvar_tabela_preco' : {
                    try {
                        $id_empresa       = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $cd_tabela        = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tabela'])));
                        $nm_tabela        = strip_tags( trim($_POST['nm_tabela']) );
                        $cd_convenio      = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_convenio'])));
                        $tp_atendimento   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tp_atendimento'])));
                        $cd_especialidade = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_especialidade'])));
                        $vl_servico       = strip_tags( trim($_POST['vl_servico']) );
                        $sn_ativo         = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_ativo'])));
                        
                        $file = '../../logs/json/tabela_preco_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    t.* "
                            . "from dbo.tbl_tabela_cobranca t    "
                            . "where (t.cd_tabela  = {$cd_tabela})"
                            . "  and (t.id_empresa = '{$id_empresa}')";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.tbl_tabela_cobranca ("
                                . "    nm_tabela        \n"
                                . "  , id_empresa       \n"
                                . "  , cd_convenio      \n"
                                . "  , tp_atendimento   \n"
                                . "  , cd_especialidade \n"
                                . "  , vl_servico       \n"
                                . "  , dh_insercao      \n"
                                . "  , us_insercao      \n"
                                . "  , sn_ativo         \n"
                                . ") "
                                . "    OUTPUT              "
                                . "    INSERTED.cd_tabela  "
                                . "  , INSERTED.nm_tabela  "
                                . "  , INSERTED.vl_servico "
                                . "values (          "
                                . "    :nm_tabela       \n"
                                . "  , :id_empresa      \n"
                                . "  , " . ($cd_convenio !== 0?$cd_convenio:"NULL")     . "  "
                                . "  , " . ($tp_atendimento !== 0?$tp_atendimento:"0")  . "  "
                                . "  , " . ($cd_especialidade !== 0?$cd_especialidade:"NULL") . "  "
                                . "  , :vl_servico      \n"
                                . "  , getdate()        \n"
                                . "  , :us_insercao     \n"
                                . "  , :sn_ativo        \n"
                                . ")");

                            $stm->execute(array(
                                  ':nm_tabela'   => $nm_tabela
                                , ':id_empresa'  => $id_empresa
                                , ':vl_servico'  => $vl_servico
                                , ':us_insercao' => $user->getCodigo()
                                , ':sn_ativo'    => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            if (($obj = $stm->fetch(PDO::FETCH_OBJ)) !== false) {
                                $tr_table = tr_table($obj->cd_tabela, $obj->nm_tabela, $obj->vl_servico, $sn_ativo);
                                
                                $registro['registro'][0]['tabela']   = $obj->cd_tabela;
                                $registro['registro'][0]['nome']     = $obj->nm_tabela;
                                $registro['registro'][0]['valor']    = number_format($obj->vl_servico, 2, ",", ".");
                                $registro['registro'][0]['tr_table'] = $tr_table;
                            }
                            
                            $pdo->commit();

                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        } else {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_tabela_cobranca Set "
                                . "    nm_tabela        = :nm_tabela "
                                . "  , cd_convenio      = " . ($cd_convenio !== 0?$cd_convenio:"NULL") . " "
                                . "  , tp_atendimento   = :tp_atendimento  "
                                . "  , cd_especialidade = " . ($cd_especialidade !== 0?$cd_especialidade:"NULL") . " "
                                . "  , vl_servico       = :vl_servico  "
                                . "  , sn_ativo         = :sn_ativo "
                                . "where (cd_tabela  = :cd_tabela)"
                                . "  and (id_empresa = :id_empresa)");

                            $stm->execute(array(
                                  ':cd_tabela'      => $cd_tabela
                                , ':id_empresa'     => $id_empresa
                                , ':nm_tabela'      => $nm_tabela
                                , ':tp_atendimento' => $tp_atendimento
                                , ':vl_servico'     => $vl_servico
                                , ':sn_ativo'       => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            $tr_table = tr_table($cd_tabela, $nm_tabela, $vl_servico, $sn_ativo);

                            $registro['registro'][0]['tabela']   = $cd_tabela;
                            $registro['registro'][0]['nome']     = $nm_tabela;
                            $registro['registro'][0]['valor']    = number_format($vl_servico, 2, ",", ".");
                            $registro['registro'][0]['tr_table'] = $tr_table;
                            
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
            
                case 'excluir_tabela_preco' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $cd_tabela  = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tabela'])));
                        
                        $sql = 
                              "Select  "
                            . "    t.* "
                            . "  , coalesce(( "
                            . "      Select count(x.cd_paciente)    "
                            . "	  from dbo.tbl_agenda x             "
                            . "	  where (x.cd_tabela = t.cd_tabela) "
                            . "    ), 0) as qt_atendimentos   "
                            . "from dbo.tbl_tabela_cobranca t "
                            . "where (t.cd_tabela = {$cd_tabela})";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            if ((int)$obj->qt_atendimentos > 0) {
                                echo "Esta tabela de preço possui associações e não poderá ser excluída.";
                            } else {
                                $pdo->beginTransaction();
                                $stm = $pdo->prepare(
                                      "Delete from dbo.tbl_tabela_cobranca "
                                    . "where (cd_tabela  = :cd_tabela)     "
                                    . "  and (id_empresa = :id_empresa)    ");

                                $stm->execute(array(
                                      ':cd_tabela'  => $cd_tabela
                                    , ':id_empresa' => $id_empresa
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