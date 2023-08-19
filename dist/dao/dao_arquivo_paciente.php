<?php
/* 
 * Documentação para implementação de acesso ao S3 Amazon:
 * 1. https://docs.aws.amazon.com/code-samples/latest/catalog/php-s3-s3-uploading-object.php.html
 * 2. https://blog.mandic.com.br/artigos/upload-pro-s3-o-mais-simples-possivel-em-php/
 * 
 * Implementação de upload para o servidor web:
 * 1. https://www.youtube.com/watch?v=zz3wDgQVo90&t=887s
 */
    ini_set('display_errors', true);
    error_reporting(E_ALL);
    date_default_timezone_set('America/Belem');

    require_once '../php/constantes.php';
    require_once '../dao/conexao.php';
    require_once '../php/usuario.php';
    require_once '../php/funcoes.php';

    require "../../vendor/autoload.php";

    use Aws\S3\S3Client;
    
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

    function tr_table($cd_paciente, $cd_arquivo, $ds_grupo, $dt_arquivo, $nm_arquivo, $ex_arquivo, $ds_arquivo, $url) {
        $referencia = (int)$cd_arquivo;
        
        $protocol = protocol() . '://';
        $url_new  = str_replace('http://', $protocol, $url);
        
        $desc = ($ds_arquivo !== "" ? $ds_arquivo : $nm_arquivo . "." . $ex_arquivo);
        $dica = "{$nm_arquivo}.{$ex_arquivo}";
        
        $style = "padding: 1px;";
        $menu_opcoes = 
              "<div class='input-group-btn'>"
            . "  <input type='hidden' id='cd_arquivo_{$referencia}' value='{$cd_arquivo}'>"
            . "  <input type='hidden' id='ds_arquivo_{$referencia}' value='{$ds_arquivo}'>"
            . "  <input type='hidden' id='ur_arquivo_{$referencia}' value='{$url_new}'>"
            . "  <input type='hidden' id='ln_arquivo_{$referencia}' value='tr-linhaarquivo_{$referencia}'>"
            . "  <button type='button' class='btn dropdown-toggle' data-toggle='dropdown'>"
            . "     <span class='fa fa-navicon bg-primary'></span>"    // fa-navicon fa-caret-down
            . "  </button>"    
            . "  <ul class='dropdown-menu'>"    
            . "     <li><a id='editar_arquivo_{$referencia}'  href='javascript:preventDefault();' onclick='editar_arquivo (this.id)'><span class='fa fa-file'></span>Editar Informações</a></li>"    
            . "     <li class='divider'></li>"    
            . "     <li><a id='excluir_arquivo_{$referencia}' href='javascript:preventDefault();' onclick='excluir_arquivo(this.id)'><span class='fa fa-trash'></span>Excluir Arquivo</a></li>"    
            . "  </ul>"    
            . "</div>\n";
        
        $excluir = "<a id='excluir_arquivo_{$referencia}' href='javascript:preventDefault();' onclick='excluir_arquivo( this.id, this )'><i class='fa fa-trash' title='Excluir Arquivo'></i>";
        $link = "<a id='visualizar_arquivo_{$referencia}' href='javascript:preventDefault();' onclick='visualizar_arquivo( this.id, this )'><i class='fa fa-file' title='Visualizar Arquivo {$dica}'></i>";
        
        $retorno =
              "    <tr id='tr-linhaarquivo_{$referencia}'>  \n"
            . "      <td>{$ds_grupo}</td>      \n"
            . "      <td>{$dt_arquivo}</td>    \n"
            . "      <td>{$desc}</td>          \n"
            . "      <td align='center' style='{$style}'>{$menu_opcoes}</td> \n"
            . "      <td align='center'>{$link}</td> \n"
            . "    </tr>  \n";
            
        return $retorno;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'pesquisar_arquivos_paciente' : {
                    echo "Função indisponível nesta versão da plataforma!";
                } break;
            
                case 'carregar_arquivos_paciente' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_estacao  = strip_tags( trim($_POST['estacao']) );
                        $cd_paciente = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['codigo'])));
                        
                        $retorno = 
                              "<table id='tb-arquivos_paciente' class='table table-bordered table-hover'> \n"
                            . "  <thead>                \n"
                            . "    <tr>                 \n"
                            . "      <th>Grupo</th>     \n"
                            . "      <th>Data</th>      \n"
                            . "      <th>Descrição</th> \n"
                            . "      <th></th>          \n"
                            . "      <th></th>          \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";
                                
                        $sql = 
                              "Select   \n"
                            . "    a.*  \n"
                            . "  , convert(varchar(12), a.dt_arquivo, 103) as data_arquivo  "
                            . "  , g.ds_grupo \n"
                            . "from dbo.tbl_arquivo_paciente a   \n"
                            . "  left join dbo.sys_grupo_arquivo g on (g.cd_grupo = a.cd_grupo) \n"
                            . "where (a.cd_paciente = {$cd_paciente})  \n"
                            . "  and (a.id_empresa  = '{$id_empresa}') \n"
                            . "  and (a.sn_ativo    = 1)   \n"
                            . "order by   \n"
                            . "    a.dt_arquivo desc  \n";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $retorno .= tr_table($obj->cd_paciente, $obj->cd_arquivo, $obj->ds_grupo, $obj->data_arquivo, $obj->nm_arquivo, $obj->ex_arquivo, $obj->ds_arquivo, $obj->ur_arquivo);
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
                
                case 'upload_arquivo_paciente' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_estacao  = strip_tags( trim($_POST['estacao']) );
                        $cd_paciente = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['codigo'])));
                        $cd_arquivo  = (float)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_arquivo'])));
                        $dt_arquivo  = strip_tags( trim($_POST['dt_arquivo']) );
                        $ds_arquivo  = strip_tags( trim($_POST['ds_arquivo']) );
                        $cd_grupo    = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['grupo'])));

                        $erros    = 0;
                        $sucessos = 0;
                        $retorno  = "";
                        
                        // Organizar array de arquivos
                        $arquivos = [];
                        for ($index = 0; $index < count($_FILES['fl_arquivos']['name']); $index++) {
                            $nome     = explode(".", $_FILES['fl_arquivos']['name'][$index]);
                            $extensao = end($nome);
                            $data = date('d/m/Y H:i:s', filectime($_FILES['fl_arquivos']['tmp_name'][$index]));
                            
                            $arquivos[$index]['name']      = $_FILES['fl_arquivos']['name'][$index];
                            $arquivos[$index]['extension'] = $extensao;
                            $arquivos[$index]['type']      = $_FILES['fl_arquivos']['type'][$index];
                            $arquivos[$index]['tmp_name']  = $_FILES['fl_arquivos']['tmp_name'][$index];
                            $arquivos[$index]['error']     = $_FILES['fl_arquivos']['error'][$index];
                            $arquivos[$index]['size']      = $_FILES['fl_arquivos']['size'][$index];
                            $arquivos[$index]['datetime']  = $data;
                        }
                        
                        $protocol  = protocol();
                        $host      = $_SERVER['HTTP_HOST'];
                        
                        $registros = array('registro' => array());
                        foreach($arquivos as $item) {
                            $file = (object)$item;
                            
                            // Verificar erro no upload do arquivo
                            if ( ($file->error !== 0) && array_key_exists($file->error, Constante::UPLOAD['error']) ) {
                                $erros   += 1;
                                $retorno .= Constante::UPLOAD['error'][$file->error] . "<br>";
                            } else 
                            // Verificar se a extensão do arquivo é permitida
                            if (array_search($file->extension, Constante::UPLOAD['extensions']) === false) {
                                $erros   += 1;
                                $retorno .= "A extensão de arquivo <strong>{$file->extension}</strong> não é permitida na plataforma<br>";
                            } else 
                            // Verificar o tamanho do arquivo
                            if ( $file->size > (int)Constante::UPLOAD['max_size']  ) {
                                $erros   += 1;
                                $retorno .= "O arquivo <strong>{$file->name}</strong> excedeu ao tamanho máximo permitido pela plataforma<br>";
                            } else {
                                // Criar pasta para armazenar os arquivos do paciente
                                $path = '../../' . Constante::UPLOAD['storage'] . 'paciente/' . $cd_paciente . '/';
                                if(!is_dir($path)) {
                                    mkdir($path, 0740, true); // 0740 -> owner can read, write, & execute; group can only read; others have no permissions
                                }
                                
                                // Renomear arquivo, caso a configuração exija
                                $filename = str_replace(array("-", " ", "(", ")"), array("", "", "", ""), $file->name);
                                if (Constante::UPLOAD['rename'] === true) {
                                    $filename = md5( $file->name . $cd_paciente ) . "." . $file->extension;
                                }
                                
                                // Excluir arquivo antigo
                                if (file_exists($path . $filename)) {
                                    unlink($path . $filename);
                                }
                                
                                // Gerar URL do servidor local para o arquivo... essa URL mudará quando o arquivo estiver na AWS Amazon S3
                                $url = str_replace("../../", "","{$protocol}://{$host}/gcm/" . $path . $filename);
                                
                                if (Constante::AWS_AMAZON_S3_SERVER_ENABLED === true) {
                                    // ENVIAR ARQUIVOS GRAVADOS PARA O SERVIDOR S3 (BEGIN)
                                    // https://blog.mandic.com.br/artigos/upload-pro-s3-o-mais-simples-possivel-em-php/
                                    // https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.S3.S3Client.html

                                    // 1. Cria o objeto do cliente, necessita passar as credenciais da AWS
                                    $clientS3 = S3Client::factory(
                                        Constante::AWS_AMAZON_REGION + 
                                        ['credentials' => Constante::AWS_AMAZON_CREDENTIALS]
                                    );

                                    // 2. Método putObject envia os dados para bucket informado
                                    $response = $clientS3->putObject(array(
                                        'ACL'    => 'public-read',
                                        'Bucket' => Constante::AWS_AMAZON_S3_BUCKET,
                                        'Key'    => "paciente/{$cd_paciente}/" . $filename,
                                        'ContentType' => $file->type,
                                        'SourceFile'  => $file->tmp_name
                                    ));
                                    var_dump($response);
                                    // 3. Recuperar URL de retorno do objeto
                                    $url = $response['ObjectURL'];
                                    // ENVIAR ... (END)
                                }
                                
                                // Movar arquivo para a pasta de destino
                                if (move_uploaded_file($file->tmp_name, $path . $filename)) {
                                    // GRAVAR REFERÊNCIA DO ARQUIVO NA BASE DE DADOS (BEGIN)
                                    $sql = 
                                          "Select  "
                                        . "     a.id_arquivo   "
                                        . "   , a.cd_arquivo   "
                                        . "   , a.cd_paciente  "
                                        . "from dbo.tbl_arquivo_paciente a "
                                        . "where (a.cd_arquivo  = {$cd_arquivo}) "
                                        . "  or ((a.cd_paciente = {$cd_paciente}) and (a.ur_arquivo = '{$url}'))";

                                    $pdo = Conexao::getConnection();
                                    $qry = $pdo->query($sql);

                                    if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                                        $pdo->beginTransaction();
                                        $stm = $pdo->prepare(
                                              "Insert Into dbo.tbl_arquivo_paciente ( "
                                            . "    id_arquivo   "
                                            . "  , ds_arquivo   "
                                            . "  , nm_arquivo   "
                                            . "  , tp_arquivo   "
                                            . "  , ex_arquivo   "
                                            . "  , dt_arquivo   "
                                            . "  , ur_arquivo   "
                                            . "  , cd_grupo     "
                                            . "  , cd_paciente  "
                                            . "  , id_empresa   "
                                            . "  , dh_insercao  "
                                            . "  , us_insercao  "
                                            . "  , hs_estacao   "
                                            . "  , sn_ativo     "
                                            . ")  "
                                            . "    OUTPUT              "
                                            . "    INSERTED.id_arquivo "
                                            . "  , INSERTED.cd_arquivo "
                                            . "values ( "
                                            . "    dbo.ufnGetGuidID() "
                                            . "  , :ds_arquivo  "
                                            . "  , :nm_arquivo  "
                                            . "  , :tp_arquivo  "
                                            . "  , :ex_arquivo  "
                                            . "  , " . ($dt_arquivo !== "" ? "convert(date, '{$dt_arquivo}', 103) " : "convert(date, '" . date('d/m/Y') . "', 103) ")
                                            . "  , :ur_arquivo  "
                                            . "  , :cd_grupo    "
                                            . "  , :cd_paciente "
                                            . "  , :id_empresa  "
                                            . "  , getdate()    "
                                            . "  , :us_insercao "
                                            . "  , :hs_estacao  "
                                            . "  , 1 "
                                            . ")");                        

                                        $stm->execute(array(
                                              ':ds_arquivo'  => $ds_arquivo
                                            , ':nm_arquivo'  => str_replace("." . $file->extension, "", $file->name)
                                            , ':tp_arquivo'  => $file->type
                                            , ':ex_arquivo'  => $file->extension
                                            , ':ur_arquivo'  => $url
                                            , ':cd_grupo'    => ($cd_grupo === 0 ? 1 : $cd_grupo) // 1 - Prontuário 
                                            , ':cd_paciente' => $cd_paciente
                                            , ':id_empresa'  => $id_empresa
                                            , ':us_insercao' => $user->getCodigo()
                                            , ':hs_estacao'  => $id_estacao
                                        ));

                                        if (($obj = $stm->fetch(PDO::FETCH_OBJ)) !== false) {
                                            $x = count($registros);
                                            $registros[$x]['id']     = $obj->id_arquivo;
                                            $registros[$x]['codigo'] = $obj->cd_arquivo;
                                            $registros[$x]['objeto'] = $path . $filename;
                                            $registros[$x]['tipo']   = $file->type;
                                        }

                                        $pdo->commit();
                                        $sucessos += 1;
                                    } else {
                                        $pdo->beginTransaction();
                                        $stm = $pdo->prepare(
                                              "Update dbo.tbl_arquivo_paciente Set "
                                            . "    ds_arquivo   = :ds_arquivo   "
                                            . "  , nm_arquivo   = :nm_arquivo   "
                                            . "  , tp_arquivo   = :tp_arquivo   "
                                            . "  , ex_arquivo   = :ex_arquivo   "
                                            . "  , dt_arquivo   = " . ($dt_arquivo !== "" ? "convert(date, '{$dt_arquivo}', 103) " : "convert(date, '" . date('d/m/Y') . "', 103) ")
                                            . "  , ur_arquivo   = :ur_arquivo   "
                                            . "  , cd_grupo     = :cd_grupo     "
                                            . "  , dh_alteracao = getdate()     "
                                            . "  , us_alteracao = :us_alteracao "
                                            . "  , hs_estacao   = :hs_estacao   "
                                            . "where (id_arquivo = :id_arquivo) "); 
                                        
                                        $stm->execute(array(
                                              ':id_arquivo'   => $obj->id_arquivo
                                            , ':ds_arquivo'   => $ds_arquivo
                                            , ':nm_arquivo'   => str_replace("." . $file->extension, "", $file->name)
                                            , ':tp_arquivo'   => $file->type
                                            , ':ex_arquivo'   => $file->extension
                                            , ':ur_arquivo'   => $url
                                            , ':cd_grupo'     => ($cd_grupo === 0 ? 1 : $cd_grupo) // 1 - Prontuário 
                                            , ':us_alteracao' => $user->getCodigo()
                                            , ':hs_estacao'   => $id_estacao
                                        ));
                                        $pdo->commit();
                                        $sucessos += 1;
                                        
                                        $x = count($registros);
                                        $registros[$x]['id']     = $obj->id_arquivo;
                                        $registros[$x]['codigo'] = $obj->cd_arquivo;
                                        $registros[$x]['objeto'] = $path . $filename;
                                        $registros[$x]['tipo']   = $file->type;
                                    }
                                    // GRAVAR ... (END)
                                    
                                    // Fechar conexão PDO
                                    unset($qry);
                                    unset($pdo);
                                } else {
                                    $erros   += 1;
                                    $retorno .= "O arquivo <strong>{$file->name}</strong> excedeu ao tamanho máximo permitido pela plataforma<br>";
                                }
                            }
                        }
                        
                        if ($sucessos == 0) {
                            $erros   += 1;
                            $retorno .= "Nenhum arquivo foi gravado!";
                        }
                        
                        if (($sucessos !== 0) && ($erros === 0)) {
                            echo "OK";
                        } else
                        if ($erros !== 0) {
                            echo $retorno;
                        }
                    } catch (Exception $ex) {
                        echo "Error:<br><br><strong>Code:</strong> " . $ex->getCode() . "<br><strong>Message:</strong> " .  $ex->getMessage();
                    }
                } break;
                
                case 'visualizar_arquivo_paciente' : {
                    $retorno['success'] = false;
                    $retorno['message'] = "Recurso em desenvolvimento";
                    
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_estacao = strip_tags( trim($_POST['estacao']) );
                        $cd_arquivo = (float)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['codigo'])));
                    
                        $sql = 
                              "Select   \n"
                            . "    a.*  \n"
                            . "  , convert(varchar(12), a.dt_arquivo, 103) as data_arquivo  "
                            . "  , g.ds_grupo \n"
                            . "from dbo.tbl_arquivo_paciente a   \n"
                            . "  left join dbo.sys_grupo_arquivo g on (g.cd_grupo = a.cd_grupo) \n"
                            . "where (a.cd_arquivo = {$cd_arquivo})  \n"
                            . "  and (a.id_empresa = '{$id_empresa}') \n";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $protocol = protocol() . '://';
                            $url_new  = str_replace('http://', $protocol, $obj->ur_arquivo);
                            
                            $arquivo['id']        = $obj->id_arquivo;
                            $arquivo['codigo']    = $obj->cd_arquivo;
                            $arquivo['descricao'] = $obj->ds_arquivo;
                            $arquivo['nome']      = $obj->nm_arquivo;
                            $arquivo['grupo']     = $obj->cd_grupo;
                            $arquivo['tipo']      = $obj->tp_arquivo;
                            $arquivo['extensao']  = $obj->ex_arquivo;
                            $arquivo['url']       = $url_new;
                            $arquivo['data']      = $obj->data_arquivo;
                            
                            $retorno['success'] = true;
                            $retorno['message'] = "OK";
                            $retorno['arquivo'] = $arquivo;
                        } else {
                            $retorno['message'] = "Arquivo não localizado!";
                        }
                        
                        echo json_encode($retorno);
                    } catch (Exception $ex) {
                        $retorno['message'] = "Error:<br><br><strong>Code:</strong> " . $ex->getCode() . "<br><strong>Message:</strong> " .  $ex->getMessage();
                        echo json_encode($retorno);
                    } finally {
                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                    }
                } break;
                
                case 'excluir_arquivo_paciente' : {
                    $retorno['success'] = false;
                    $retorno['message'] = "Recurso em desenvolvimento";
                    
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_estacao = strip_tags( trim($_POST['estacao']) );
                        $cd_arquivo = (float)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['codigo'])));
                        
                        $pdo = Conexao::getConnection();

                        $sql = 
                              "Select   \n"
                            . "    a.*  \n"
                            . "  , convert(varchar(12), a.dt_arquivo, 103) as data_arquivo  "
                            . "  , g.ds_grupo \n"
                            . "from dbo.tbl_arquivo_paciente a   \n"
                            . "  left join dbo.sys_grupo_arquivo g on (g.cd_grupo = a.cd_grupo) \n"
                            . "where (a.cd_arquivo = {$cd_arquivo})  \n"
                            . "  and (a.id_empresa = '{$id_empresa}') \n";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            // Montar "path/url" base do arquivo
                            $protocol  = protocol();
                            $host = $_SERVER['HTTP_HOST'];
                            $path = '../../' . Constante::UPLOAD['storage'] . 'paciente/' . $obj->cd_paciente . '/';
                            
                            // Identificar nome do arquivo
                            $file = explode("/", $obj->ur_arquivo);
                            
                            // Excluir arquivo físico
                            if (file_exists($path . end($file))) {
                                unlink($path . end($file));
                            }
                            
                            // Excluir da base de dados
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Delete from dbo.tbl_arquivo_paciente "
                                . "where cd_arquivo = :cd_arquivo");

                            $stm->execute(array(
                                  ':cd_arquivo' => $cd_arquivo
                            ));
                            $pdo->commit();

                            $retorno['success'] = true;
                            $retorno['message'] = "OK";
                        } else {
                            $retorno['message'] = "Arquivo não localizado!";
                        }
                        
                        echo json_encode($retorno);
                    } catch (Exception $ex) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $retorno['message'] = "Error:<br><br><strong>Code:</strong> " . $ex->getCode() . "<br><strong>Message:</strong> " .  $ex->getMessage();
                        echo json_encode($retorno);
                    } finally {
                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                    }
                } break;
                
                case 'salvar_dados_arquivo_paciente' : {
                    $retorno['success'] = false;
                    $retorno['message'] = "Recurso em desenvolvimento";
                    
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_estacao = strip_tags( trim($_POST['estacao']) );
                        $cd_arquivo = (float)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['codigo'])));
                        
                        $dt_arquivo  = strip_tags( trim($_POST['dt_arquivo']) );
                        $ds_arquivo  = strip_tags( trim($_POST['ds_arquivo']) );
                        $cd_grupo    = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_grupo'])));
                        
                        $pdo = Conexao::getConnection();

                        $sql = 
                              "Select   \n"
                            . "    a.*  \n"
                            . "  , convert(varchar(12), a.dt_arquivo, 103) as data_arquivo  "
                            . "  , g.ds_grupo \n"
                            . "from dbo.tbl_arquivo_paciente a   \n"
                            . "  left join dbo.sys_grupo_arquivo g on (g.cd_grupo = a.cd_grupo) \n"
                            . "where (a.cd_arquivo = {$cd_arquivo})  \n"
                            . "  and (a.id_empresa = '{$id_empresa}') \n";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_arquivo_paciente Set "
                                . "    ds_arquivo   = :ds_arquivo   "
                                . "  , dt_arquivo   = " . ($dt_arquivo !== "" ? "convert(date, '{$dt_arquivo}', 103) " : "convert(date, '" . date('d/m/Y') . "', 103) ")
                                . "  , cd_grupo     = :cd_grupo     "
                                . "  , dh_alteracao = getdate()     "
                                . "  , us_alteracao = :us_alteracao "
                                . "  , hs_estacao   = :hs_estacao   "
                                . "where (id_arquivo = :id_arquivo) "); 

                            $stm->execute(array(
                                  ':id_arquivo'   => $obj->id_arquivo
                                , ':ds_arquivo'   => $ds_arquivo
                                , ':cd_grupo'     => ($cd_grupo === 0 ? 1 : $cd_grupo) // 1 - Prontuário 
                                , ':us_alteracao' => $user->getCodigo()
                                , ':hs_estacao'   => $id_estacao
                            ));
                            $pdo->commit();
                            
                            $retorno['success'] = true;
                            $retorno['message'] = "OK";
                        } else {
                            $retorno['message'] = "Arquivo não localizado!";
                        }
                        
                        echo json_encode($retorno);
                    } catch (Exception $ex) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $retorno['message'] = "Error:<br><br><strong>Code:</strong> " . $ex->getCode() . "<br><strong>Message:</strong> " .  $ex->getMessage();
                        echo json_encode($retorno);
                    } finally {
                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                    }
                } break;
            }
        } else {
            echo painel_alerta_danger("Permissão negada, pois a ação não foi definida.");        
        }
    }