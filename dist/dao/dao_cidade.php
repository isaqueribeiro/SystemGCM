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
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'pesquisar_cidades' : {
                    try {
                        $cd_estado   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['estado'])));
                        $ds_filtro   = strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $qt_registro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/cidade_' . $cookieID . '.json';
                        if (file_exists($file_cookie)) {
                            unlink($file_cookie);
                        }
                        
                        $registros = array('filtro' => array());
                        $registros['filtro'][0]['qt_registro']  = $qt_registro;
                        $registros['filtro'][0]['cd_estado']    = $cd_estado;
                        
                        $json = json_encode($registros);
                        file_put_contents($file_cookie, $json);
                        // Gravar as configurações do filtro utilizado pelo usuário -- (FINAL)
                        
                        $retorno = 
                              "<table id='tb-cidades' class='table table-bordered table-hover'> \n"
                            . "  <thead> \n"
                            . "    <tr>  \n"
                            . "      <th>#</th>  \n"
                            . "      <th>Nome</th>  \n"
                            . "      <th>UF</th>  \n"
                            . "      <th>CEP Inicial</th>  \n"
                            . "      <th>CEP Final</th>  \n"
                            . "      <th>DDD</th>  \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $sql = 
                              "Select * "
                            . "from dbo.sys_cidade c "
                            . "  inner join dbo.sys_estado e on (e.cd_estado = c.cd_estado) "
                            . "where (c.cd_estado = {$cd_estado}) "
                            . "  and (upper(c.nm_cidade) like concat('{$ds_filtro}', '%')) "
                            . "order by "
                            . "    e.sg_estado "
                            . "  , c.nm_cidade ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $referencia = str_pad($obj->cd_cidade, 7, "00000", STR_PAD_LEFT);

                            $retorno .=
                                  "    <tr id='tr-linha_{$referencia}'>  \n"
                                . "      <td><a href='#' id='reg-cidade_{$referencia}' onclick='abrir_cadastro(this, this.id);'>{$referencia}</a></td>  \n"
                                . "      <td>{$obj->nm_cidade}</td>  \n"
                                . "      <td>{$obj->sg_estado}</td>  \n"
                                . "      <td>" . formatarTexto('##.###-###', str_pad($obj->nr_cep_inicial, 8, "00000000", STR_PAD_LEFT)) . "</td>  \n"
                                . "      <td>" . formatarTexto('##.###-###', str_pad($obj->nr_cep_final,   8, "00000000", STR_PAD_LEFT)) . "</td>  \n"
                                . "      <td>{$obj->nr_ddd}</td>  \n"
                                . "    </tr>  \n";
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
            
                case 'listar_cidades_select' : {
                    try {
                        $cd_estado = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['estado'])));
                        $id_obj    = str_replace("#", "", strip_tags( trim($_POST['id_obj']) ));
                        
                        $retorno = 
                              "<select class=form-control select2'  id='{$id_obj}' style='width: 100%;'> \n"
                            . "  <option value='0'>Todas</option> \n";

                        $sql = 
                              "Select * "
                            . "from dbo.sys_cidade c "
                            . "where (c.cd_estado = {$cd_estado}) "
                            . "order by "
                            . "  c.nm_cidade ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $retorno .= "  <option value='{$obj->cd_cidade}'>{$obj->nm_cidade}</option> \n";
                        }

                        $retorno .= "</select> \n";
                        
//                        // Aplicar formatação CSS no Select (Funciona perfeitamente)
//                        $retorno .= "<script type='text/javascript'>  \n";
//                        $retorno .= "  $('#{$id_obj}').select2();     \n"; 
//                        $retorno .= "</script> \n";

                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                        
                        echo $retorno;
                    } catch (Exception $ex) {
                        echo $ex . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                    } 
                } break;
            
                case 'carregar_cidade' : {
                    try {
                        $cd_cidade = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cidade'])));
                        
                        $file = '../../logs/json/cidade_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select * "
                            . "from dbo.sys_cidade c "
                            . "  inner join dbo.sys_estado e on (e.cd_estado = c.cd_estado) "
                            . "where (c.cd_cidade = {$cd_cidade}) ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][0]['codigo'] = $obj->cd_cidade;
                            $registro['registro'][0]['nome']   = $obj->nm_cidade;
                            $registro['registro'][0]['estado'] = $obj->cd_estado;
                            $registro['registro'][0]['uf']     = $obj->sg_estado;
                            $registro['registro'][0]['cep_inicial'] = $obj->nr_cep_inicial;
                            $registro['registro'][0]['cep_final']   = $obj->nr_cep_final;
                            $registro['registro'][0]['ddd']     = $obj->nr_ddd;
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
            
                case 'salvar_cidade' : {
                    try {
                        echo "A edição destes dados não está disponível nesta versão do sistema";
                    } catch (Exception $ex) {
                        echo $ex . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                    } 
                } break;
            }
        } else {
            echo painel_alerta_danger("Permissão negada, pois a ação não foi definida.");        
        }
    }