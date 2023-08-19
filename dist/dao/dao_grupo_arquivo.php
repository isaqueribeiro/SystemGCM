<?php
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

    function tr_table($cd_grupo, $ds_grupo, $sn_ativo) {
        $referencia = (int)$cd_grupo;

        //$status  = "<span class='label-" . ((int)$sn_ativo === 1?"success":"danger") . " btn-xs' id='reg-status_{$referencia}'>" . ((int)$sn_ativo === 1?"Ativo":"Inativo") . "</span>";
        $status  = "<i id='status_grupoarquivo_{$referencia}' class='fa " . ((int)$sn_ativo === 1?"fa-check-square-o text-green":"fa-square-o text-red") . "'></i>";
        $excluir = "<a id='excluir_grupoarquivo_{$referencia}' href='javascript:preventDefault();' onclick='excluir_registro( this.id, this )'><i class='fa fa-trash' title='Excluir Registro'></i>";

        $retorno =
              "    <tr id='tr-linha_{$referencia}'>  \n"
            . "      <td><a href='#' id='reg-grupoarquivo_{$referencia}' onclick='abrir_cadastro(this, this.id);'>" . str_pad($cd_grupo, 2, "0", STR_PAD_LEFT) . "</a></td>  \n"
            . "      <td>{$ds_grupo}</td>   \n"
            . "      <td align='center'>{$status}</td>  \n"
            . "      <td align='center'>{$excluir}</td> \n"
            . "    </tr>  \n";
            
        return $retorno;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'pesquisar_grupos' : {
                    try {
                        $tp_filtro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tipo'])));
                        $ds_filtro = strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $qt_registro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/grupoarquivo_' . $cookieID . '.json';
                        if (file_exists($file_cookie)) {
                            unlink($file_cookie);
                        }
                        
                        $registros = array('filtro' => array());
                        $registros['filtro'][0]['qt_registro'] = $qt_registro;
                        $registros['filtro'][0]['cd_tipo']     = $tp_filtro;
                        
                        $filtro = ($tp_filtro === 1?"  and (g.sn_ativo = 1)":"");
                        
                        $json = json_encode($registros);
                        file_put_contents($file_cookie, $json);
                        // Gravar as configurações do filtro utilizado pelo usuário -- (FINAL)
                        
                        $retorno = 
                              "<table id='tb-grupos' class='table table-bordered table-hover'> \n"
                            . "  <thead>                    \n"
                            . "    <tr>                     \n"
                            . "      <th>#</th>             \n"
                            . "      <th>Descrição</th>     \n"
                            . "      <th>Ativo</th>         \n"
                            . "      <th></th>              \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $sql = 
                              "Select  "
                            . "    g.* "
                            . "from dbo.sys_grupo_arquivo g   "
                            . "where (upper(g.ds_grupo) like concat('%', '{$ds_filtro}', '%')) " 
                            . ($filtro) . "       "
                            . "order by           "
                            . "    g.ds_grupo ";
                    
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $retorno .= tr_table($obj->cd_grupo, $obj->ds_grupo, $obj->sn_ativo);
                        }

                        $retorno .=
                              "  </tbody> \n"
                            . "</table>   \n";

                        echo $retorno;
                    } catch (Exception $ex) {
                        echo $ex . (isset($pdo) ? "<br><br><strong>Code:</strong> " . $pdo->errorInfo()[1] . "<br><strong>Message:</strong> " .  $pdo->errorInfo()[2] : "");
                    } finally {
                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                    }
                } break;
            
                case 'carregar_grupo' : {
                    try {
                        $cd_grupo = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['codigo'])));
                        
                        $file = '../../logs/json/grupoarquivo_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    g.* "
                            . "from dbo.sys_grupo_arquivo g  "
                            . "where (g.cd_grupo = {$cd_grupo}) ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][0]['codigo']    = $obj->cd_grupo;
                            $registro['registro'][0]['descricao'] = $obj->ds_grupo;
                            $registro['registro'][0]['ativo']     = $obj->sn_ativo;
                        }
                        
                        $json = json_encode($registro);
                        file_put_contents($file, $json);
                        
                        echo "OK";
                    } catch (Exception $ex) {
                        echo $ex . (isset($pdo) ? "<br><br><strong>Code:</strong> " . $pdo->errorInfo()[1] . "<br><strong>Message:</strong> " .  $pdo->errorInfo()[2] : "");
                    } finally {
                        // Fechar conexão PDO
                        unset($stm);
                        unset($pdo);
                    }
                } break;
            
                case 'salvar_grupo' : {
                    try {
                        $cd_grupo = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_grupo'])));
                        $ds_grupo = strip_tags( trim($_POST['ds_grupo']) );
                        $sn_ativo = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_ativo'])));
                        
                        $file = '../../logs/json/grupoarquivo_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    g.* "
                            . "from dbo.sys_grupo_arquivo g   "
                            . "where (g.cd_grupo = {$cd_grupo})";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.sys_grupo_arquivo ("
                                . "    ds_grupo "
                                . "  , sn_ativo "
                                . ") "
                                . "    OUTPUT            "
                                . "    INSERTED.cd_grupo "
                                . "  , INSERTED.ds_grupo "
                                . "values (              "
                                . "    :ds_grupo         "
                                . "  , :sn_ativo         "
                                . ")");

                            $stm->execute(array(
                                  ':ds_grupo' => $ds_grupo
                                , ':sn_ativo' => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            if (($obj = $stm->fetch(PDO::FETCH_OBJ)) !== false) {
                                $tr_table = tr_table($obj->cd_grupo, $ds_grupo, $sn_ativo);
                                
                                $registro['registro'][0]['codigo']    = $obj->cd_grupo;
                                $registro['registro'][0]['descricao'] = $obj->ds_grupo;
                                $registro['registro'][0]['tr_table']  = $tr_table;
                            }
                            
                            $pdo->commit();

                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        } else {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Update dbo.sys_grupo_arquivo Set "
                                . "    ds_grupo = :ds_grupo "
                                . "  , sn_ativo = :sn_ativo "
                                . "where cd_grupo = :cd_grupo");

                            $stm->execute(array(
                                  ':cd_grupo' => $cd_grupo
                                , ':ds_grupo' => $ds_grupo
                                , ':sn_ativo' => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            $tr_table = tr_table($cd_grupo, $ds_grupo, $sn_ativo);

                            $registro['registro'][0]['codigo']    = $cd_grupo;
                            $registro['registro'][0]['descricao'] = $ds_grupo;
                            $registro['registro'][0]['tr_table']  = $tr_table;
                            
                            $pdo->commit();

                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        }

                        echo "OK";
                    } catch (Exception $ex) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        echo $ex . (isset($pdo) ? "<br><br><strong>Code:</strong> " . $pdo->errorInfo()[1] . "<br><strong>Message:</strong> " .  $pdo->errorInfo()[2] : "");
                    } finally {
                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                    }
                } break;
            
                case 'excluir_grupo' : {
                    try {
                        $cd_grupo = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['grupo'])));
                        
                        $sql = 
                              "Select  "
                            . "  count(a.cd_arquivo) as qt_arquivos "
                            . "from dbo.tbl_arquivo_paciente a "
                            . "where (a.cd_grupo = {$cd_grupo})";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            if ((int)$obj->qt_arquivos > 0) {
                                echo "Este grupo de arquivos possui associações e não poderá ser excluído.";
                            } else {
                                $pdo->beginTransaction();
                                $stm = $pdo->prepare(
                                      "Delete from dbo.sys_grupo_arquivo "
                                    . "where cd_grupo = :cd_grupo");

                                $stm->execute(array(
                                      ':cd_grupo' => $cd_grupo
                                ));

                                $pdo->commit();

                                echo "OK";
                            }
                        }
                    } catch (Exception $ex) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        echo $ex . (isset($pdo) ? "<br><br><strong>Code:</strong> " . $pdo->errorInfo()[1] . "<br><strong>Message:</strong> " .  $pdo->errorInfo()[2] : "");
                    } finally {
                        // Fechar conexão PDO
                        unset($stm);
                        unset($pdo);
                    }
                } break;
            }
        } else {
            echo painel_alerta_danger("Permissão negada, pois a ação não foi definida.");        
        }
    }