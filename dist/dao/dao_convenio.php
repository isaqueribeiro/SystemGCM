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

    function tr_table($cd_convenio, $nm_convenio, $nm_resumido, $nr_cnpj_cpf, $nr_registro_ans, $sn_ativo) {
        $referencia = (int)$cd_convenio;

        //$status  = "<span class='label-" . ((int)$sn_ativo === 1?"success":"danger") . " btn-xs' id='reg-status_{$referencia}'>" . ((int)$sn_ativo === 1?"Ativo":"Inativo") . "</span>";
        $status  = "<i id='status_convenio_{$referencia}' class='fa " . ((int)$sn_ativo === 1?"fa-check-square-o text-green":"fa-square-o text-red") . "'></i>";
        $excluir = "<a id='excluir_convenio_{$referencia}' href='javascript:preventDefault();' onclick='excluir_registro( this.id, this )'><i class='fa fa-trash' title='Excluir Registro'></i>";

        $retorno =
              "    <tr id='tr-linha_{$referencia}'>  \n"
            . "      <td><a href='#' id='reg-convenio_{$referencia}' onclick='abrir_cadastro(this, this.id);'>" . str_pad($cd_convenio, 3, "0", STR_PAD_LEFT) . "</a></td>  \n"
            . "      <td>{$nm_resumido}</td>  \n"
            . "      <td>{$nm_convenio}</td>  \n"
            . "      <td>{$nr_cnpj_cpf}</td>  \n"
            . "      <td>{$nr_registro_ans}</td>   \n"
            . "      <td align='center'>{$status}</td>                 \n"
            . "      <td align='center'>{$excluir}</td> \n"
            . "    </tr>  \n";
            
        return $retorno;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'pesquisar_convenios' : {
                    try {
                        $tp_convenio = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tipo'])));
                        $ds_filtro   = strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $qt_registro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/convenio_' . $cookieID . '.json';
                        if (file_exists($file_cookie)) {
                            unlink($file_cookie);
                        }
                        
                        $registros = array('filtro' => array());
                        $registros['filtro'][0]['qt_registro'] = $qt_registro;
                        $registros['filtro'][0]['cd_tipo']     = $tp_convenio;
                        
                        $json = json_encode($registros);
                        file_put_contents($file_cookie, $json);
                        // Gravar as configurações do filtro utilizado pelo usuário -- (FINAL)
                        
                        $retorno = 
                              "<table id='tb-convenios' class='table table-bordered table-hover'> \n"
                            . "  <thead>                    \n"
                            . "    <tr>                     \n"
                            . "      <th>#</th>             \n"
                            . "      <th>Nome</th>          \n"
                            . "      <th>Razão Social</th>  \n"
                            . "      <th>CPF/CNPJ</th>      \n"
                            . "      <th>Registro ANS</th>  \n"
                            . "      <th>Ativo</th>         \n"
                            . "      <th></th>              \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $tipo = ($tp_convenio !== 0?"  and (c.tp_convenio = " . ($tp_convenio - 1) . ")":"");
                        
                        $sql = 
                              "Select  "
                            . "    c.* "
                            . "from dbo.tbl_convenio c   "
                            . "where ((upper(c.nm_convenio) like concat('%', '{$ds_filtro}', '%')) "
                            . "    or (upper(c.nm_resumido) like concat('%', '{$ds_filtro}', '%')) "
                            . "  ) "
                            . $tipo
                            . "order by          "
                            . "    c.nm_resumido ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $retorno .= tr_table($obj->cd_convenio, $obj->nm_convenio, $obj->nm_resumido, $obj->nr_cnpj_cpf, $obj->nr_registro_ans, $obj->sn_ativo);
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
            
                case 'carregar_convenio' : {
                    try {
                        $cd_convenio = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['codigo'])));
                        
                        $file = '../../logs/json/convenio_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    c.* "
                            . "from dbo.tbl_convenio c  "
                            . "where (c.cd_convenio = {$cd_convenio}) ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][0]['codigo']   = $obj->cd_convenio;
                            $registro['registro'][0]['nome']     = $obj->nm_convenio;
                            $registro['registro'][0]['resumo']   = $obj->nm_resumido;
                            $registro['registro'][0]['cpf_cnpj'] = $obj->nr_cnpj_cpf;
                            $registro['registro'][0]['ans']      = $obj->nr_registro_ans;
                            $registro['registro'][0]['ativo']    = $obj->sn_ativo;
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
            
                case 'salvar_convenio' : {
                    try {
                        $cd_convenio = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_convenio'])));
                        $nm_convenio = strip_tags( trim($_POST['nm_convenio']) );
                        $nm_resumido = strip_tags( trim($_POST['nm_resumido']) );
                        $nr_cnpj_cpf = preg_replace("/[^0-9]/", "", strip_tags( strtoupper(trim($_POST['nr_cnpj_cpf'])) ) );
                        $nr_registro_ans = strip_tags( strtoupper(trim($_POST['nr_registro_ans'])) );
                        $sn_ativo = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_ativo'])));
                        
                        // Validar dados
                        if (!cpf_cnpj_valido($nr_cnpj_cpf)) {
                            echo "<strong>CPF/CNPJ</strong> inválido!";
                            exit();
                        }
                        
                        /*
                        Tipo:
                        0 - Pessoa física
                        1 - Pessoa jurídica
                         */
                        $tp_convenio = (strlen(preg_replace('/[^0-9]/', '', $nr_cnpj_cpf))=== 11?0:1);

                        if ($tp_convenio === 0) {
                            $nr_cnpj_cpf = formatarTexto('###.###.###-##', $nr_cnpj_cpf);
                        } else {
                            $nr_cnpj_cpf = formatarTexto('##.###.###/####-##', $nr_cnpj_cpf);
                        }
                        
                        $nr_registro_ans = str_pad($nr_registro_ans, 6, "0", STR_PAD_LEFT);
                                
                        $file = '../../logs/json/convenio_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    c.* "
                            . "from dbo.tbl_convenio c   "
                            . "where (c.cd_convenio = {$cd_convenio})";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.tbl_convenio ("
                                . "    tp_convenio      "
                                . "  , nm_convenio      "
                                . "  , nm_resumido      "
                                . "  , nr_cnpj_cpf      "
                                . "  , nr_registro_ans  "
                                . "  , sn_ativo         "
                                . ") "
                                . "    OUTPUT               "
                                . "    INSERTED.cd_convenio "
                                . "  , INSERTED.nm_convenio "
                                . "  , INSERTED.nm_resumido "
                                . "values (           "
                                . "    :tp_convenio     "
                                . "  , :nm_convenio     "
                                . "  , :nm_resumido     "
                                . "  , :nr_cnpj_cpf     "
                                . "  , :nr_registro_ans "
                                . "  , :sn_ativo        "
                                . ")");

                            $stm->execute(array(
                                  ':tp_convenio'     => $tp_convenio
                                , ':nm_convenio'     => $nm_convenio
                                , ':nm_resumido'     => $nm_resumido
                                , ':nr_cnpj_cpf'     => $nr_cnpj_cpf
                                , ':nr_registro_ans' => $nr_registro_ans
                                , ':sn_ativo'        => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            if (($obj = $stm->fetch(PDO::FETCH_OBJ)) !== false) {
                                $tr_table = tr_table($obj->cd_convenio, $nm_convenio, $nm_resumido, $nr_cnpj_cpf, $nr_registro_ans, $sn_ativo);
                                
                                $registro['registro'][0]['convenio'] = $obj->cd_convenio;
                                $registro['registro'][0]['nome']     = $obj->nm_convenio;
                                $registro['registro'][0]['cnpj_cpf'] = $nr_cnpj_cpf;
                                $registro['registro'][0]['ans']      = $nr_registro_ans;
                                $registro['registro'][0]['tr_table'] = $tr_table;
                            }
                            
                            $pdo->commit();

                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        } else {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_convenio Set "
                                . "    tp_convenio      = :tp_convenio "
                                . "  , nm_convenio      = :nm_convenio "
                                . "  , nm_resumido      = :nm_resumido "
                                . "  , nr_cnpj_cpf      = :nr_cnpj_cpf "
                                . "  , nr_registro_ans  = :nr_registro_ans "
                                . "  , sn_ativo         = :sn_ativo "
                                . "where cd_convenio    = :cd_convenio");

                            $stm->execute(array(
                                  ':cd_convenio'     => $cd_convenio
                                , ':tp_convenio'     => $tp_convenio
                                , ':nm_convenio'     => $nm_convenio
                                , ':nm_resumido'     => $nm_resumido
                                , ':nr_cnpj_cpf'     => $nr_cnpj_cpf
                                , ':nr_registro_ans' => $nr_registro_ans
                                , ':sn_ativo'        => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            $tr_table = tr_table($cd_convenio, $nm_convenio, $nm_resumido, $nr_cnpj_cpf, $nr_registro_ans, $sn_ativo);

                            $registro['registro'][0]['convenio'] = $cd_convenio;
                            $registro['registro'][0]['nome']     = $nm_convenio;
                            $registro['registro'][0]['cnpj_cpf'] = $nr_cnpj_cpf;
                            $registro['registro'][0]['ans']      = $nr_registro_ans;
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
            
                case 'excluir_convenio' : {
                    try {
                        $cd_convenio = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['convenio'])));
                        
                        if ($cd_convenio === 1) {
                            echo "Este convênio é padrão do sistema e não poderá ser excluído.";
                        } else {
                            $pdo = Conexao::getConnection();
                            
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Delete from dbo.tbl_convenio    "
                                . "where cd_convenio = :cd_convenio");

                            $stm->execute(array(
                                  ':cd_convenio'     => $cd_convenio
                            ));

                            $pdo->commit();
                            
                            // Fechar conexão PDO
                            unset($stm);
                            unset($pdo);
                            
                            echo "OK";
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