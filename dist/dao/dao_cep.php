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
                
                case 'pesquisar_ceps' : {
                    try {
                        $cd_estado   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['estado'])));
                        $cd_cidade   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cidade'])));
                        $ds_filtro   = strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $qt_registro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/cep_' . $cookieID . '.json';
                        if (file_exists($file_cookie)) {
                            unlink($file_cookie);
                        }
                        
                        $registros = array('filtro' => array());
                        $registros['filtro'][0]['qt_registro']  = $qt_registro;
                        $registros['filtro'][0]['cd_estado']    = $cd_estado;
                        $registros['filtro'][0]['cd_cidade']    = $cd_cidade;
                        
                        $json = json_encode($registros);
                        file_put_contents($file_cookie, $json);
                        // Gravar as configurações do filtro utilizado pelo usuário -- (FINAL)
                        
                        $retorno = 
                              "<table id='tb-ceps' class='table table-bordered table-hover'> \n"
                            . "  <thead> \n"
                            . "    <tr>  \n"
                            . "      <th>Cep</th>  \n"
                            . "      <th>Endereço</th>  \n"
                            . "      <th>Bairro</th>  \n"
                            . "      <th>Cidade</th>  \n"
                            . "      <th>UF</th>  \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $sql = 
                              "Select               "
                            . "    c.nr_cep         "
                            . "  , c.ds_endereco    "
                            . "  , replace(dbo.ufnRemoverAcentos(c.nm_bairro), '?', '') as nm_bairro "
                            . "  , c.nm_cidade      "
                            . "  , c.sg_estado      "
                            . "from dbo.sys_cep c   "
                            . "where (c.cd_estado  = {$cd_estado})  "
                            . "  and ((c.cd_cidade = {$cd_cidade}) or ({$cd_cidade} = 0))         "
                            . "  and (upper(c.ds_endereco) like concat('%', '{$ds_filtro}', '%')) "
                            . "order by          "
                            . "    c.nm_cidade   "
                            . "  , c.ds_endereco ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $referencia = $obj->nr_cep;
                            
                            $retorno .=
                                  "    <tr id='tr-linha_{$referencia}'>  \n"
                                . "      <td><a href='#' id='reg-cep_{$referencia}' onclick='abrir_cadastro(this, this.id);'>" . formatarTexto('##.###-###', str_pad($obj->nr_cep, 8, "00000000", STR_PAD_LEFT)) . "</a></td>  \n"
                                . "      <td>{$obj->ds_endereco}</td>  \n"
                                . "      <td>{$obj->nm_bairro}</td>  \n"
                                . "      <td>{$obj->nm_cidade}</td>  \n"
                                . "      <td>{$obj->sg_estado}</td>  \n"
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
            
                case 'carregar_cep' : {
                    try {
                        $nr_cep = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cep'])));
                        
                        $file = '../../logs/json/cep_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select              "
                            . "    c.nr_cep        "
                            . "  , c.ds_endereco   "
                            . "  , c.ds_logradouro "
                            . "  , replace(dbo.ufnRemoverAcentos(c.nm_bairro), '?', '') as nm_bairro "
                            . "  , c.nm_cidade     "
                            . "  , e.nm_estado     "
                            . "  , c.sg_estado     "
                            . "  , c.cd_estado     "
                            . "  , c.cd_cidade     "
                            . "  , c.cd_tipo       "
                            . "  , coalesce(nullif(trim(t.sg_tipo), ''), t.ds_tipo) as ds_tipo "
                            . "from dbo.sys_cep c  "
                            . "  left join dbo.sys_estado e on (e.cd_estado = c.cd_estado) "
                            . "  left join dbo.sys_tipo_logradouro t on (t.cd_tipo = c.cd_tipo) "
                            . "where (c.nr_cep = {$nr_cep})";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][0]['cep']         = $obj->nr_cep;
                            $registro['registro'][0]['endereco']    = $obj->ds_endereco;
                            $registro['registro'][0]['logradouro']  = $obj->ds_logradouro;
                            $registro['registro'][0]['estado']      = $obj->cd_estado;
                            $registro['registro'][0]['nome_estado'] = $obj->nm_estado;
                            $registro['registro'][0]['cidade']      = $obj->cd_cidade;
                            $registro['registro'][0]['nome_cidade'] = $obj->nm_cidade;
                            $registro['registro'][0]['bairro']      = $obj->nm_bairro;
                            $registro['registro'][0]['uf']          = $obj->sg_estado;
                            $registro['registro'][0]['tipo']        = $obj->cd_tipo;
                            $registro['registro'][0]['descricao_tipo'] = $obj->ds_tipo;
                        }
                        
                        $json = json_encode($registro);
                        file_put_contents($file, $json);
                        
                        unset($qry);
                        unset($pdo);
                        
                        echo "OK";
                    } catch (Exception $ex) {
                        echo $ex . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                    } 
                } break;
            
                case 'salvar_cep' : {
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