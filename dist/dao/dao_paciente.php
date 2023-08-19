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

    function tr_table($cd_paciente, $nm_paciente, $nr_fone, $ds_idade, $nr_rg, $nr_cpf, $sn_ativo) {
        $referencia = (int)$cd_paciente;

        //$status  = "<span class='label-" . ((int)$sn_ativo === 1?"success":"danger") . " btn-xs' id='reg-status_{$referencia}'>" . ((int)$sn_ativo === 1?"Ativo":"Inativo") . "</span>";
        $status  = "<i id='status_paciente_{$referencia}' class='fa " . ((int)$sn_ativo === 1?"fa-check-square-o text-green":"fa-square-o text-red") . "'></i>";
        $excluir = "<a id='excluir_paciente_{$referencia}' href='javascript:preventDefault();' onclick='excluir_registro( this.id, this )'><i class='fa fa-trash' title='Excluir Registro'></i>";

        $retorno =
              "    <tr id='tr-linha_{$referencia}'>  \n"
            . "      <td><a href='#' id='reg-paciente_{$referencia}' onclick='abrir_cadastro(this, this.id);'>" . str_pad($cd_paciente, 7, "0", STR_PAD_LEFT) . "</a></td>  \n"
            . "      <td>{$nm_paciente}</td>    \n"
            . "      <td>{$nr_fone}</td>        \n"
            . "      <td>{$ds_idade}</td>       \n"
            . "      <td>{$nr_rg}</td>          \n"
            . "      <td>{$nr_cpf}</td>         \n"
            . "      <td align='center'>{$status}</td>  \n"
            . "      <td align='center'>{$excluir}</td> \n"
            . "    </tr>  \n";
            
        return $retorno;
    }
    
    function tr_table_busca($cd_paciente, $nm_paciente, $dt_nascimento, $ds_idade, $nr_celular, $nr_telefone, $ds_email, $sn_ativo) {
        $referencia = (int)$cd_paciente;

        $input = 
              "<input type='hidden' id='cd_paciente_{$referencia}' value='{$cd_paciente}'>"
            . "<input type='hidden' id='nm_paciente_{$referencia}' value='{$nm_paciente}'>"
            . "<input type='hidden' id='nr_celular_{$referencia}'  value='{$nr_celular}'>"
            . "<input type='hidden' id='nr_telefone_{$referencia}' value='{$nr_telefone}'>"
            . "<input type='hidden' id='ds_email_{$referencia}'    value='{$ds_email}'>"
            . "<input type='hidden' id='sn_ativo_{$referencia}'    value='{$sn_ativo}'>";
            
        $status = "<i id='status_paciente_{$referencia}' class='fa " . ((int)$sn_ativo === 1?"fa-check-square-o text-green":"fa-square-o text-red") . "'></i>";

        $retorno =
              "    <tr id='tr-paciente_{$referencia}'>  \n"
            . "      <td><a href='#' id='reg-paciente_{$referencia}' onclick='selecinar_paciente(this, this.id);'>" . str_pad($cd_paciente, 7, "0", STR_PAD_LEFT) . "</a></td>  \n"
            . "      <td>{$nm_paciente}</td>    \n"
            . "      <td>{$dt_nascimento}</td>  \n"
            . "      <td>{$ds_idade}</td>       \n"
            . "      <td align='center'>{$status}{$input}</td>  \n"
            . "    </tr>  \n";
            
        return $retorno;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'pesquisar_pacientes' : {
                    try {
                        $tp_filtro   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tipo'])));
                        $ds_filtro   = strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $qt_registro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        $filtro   = str_replace(" ", "%", $ds_filtro);
                        $metafone = meta_fonema($ds_filtro);

                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/paciente_' . $cookieID . '.json';
                        if (file_exists($file_cookie)) {
                            unlink($file_cookie);
                        }
                        
                        $registros = array('filtro' => array());
                        $registros['filtro'][0]['qt_registro'] = $qt_registro;
                        $registros['filtro'][0]['tp_filtro']   = $tp_filtro;
                        
                        $json = json_encode($registros);
                        file_put_contents($file_cookie, $json);
                        // Gravar as configurações do filtro utilizado pelo usuário -- (FINAL)
                        
                        $retorno = 
                              "<table id='tb-pacientes' class='table table-bordered table-hover'> \n"
                            . "  <thead>                    \n"
                            . "    <tr>                     \n"
                            . "      <th>Prontuário</th>    \n"
                            . "      <th>Nome</th>          \n"
                            . "      <th>Fone</th>          \n"
                            . "      <th>Idade</th>         \n"
                            . "      <th>RG</th>            \n"
                            . "      <th>CPF</th>           \n"
                            . "      <th>Ativo</th>         \n"
                            . "      <th></th>              \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $tipo = ($tp_filtro === 1?"  and (p.sn_ativo = 1)":"");
                        
                        $sql = 
                              "Select   \n"
                            . "    p.*  \n"
                            . "  , coalesce(nullif(trim(p.nr_celular), ''), nullif(trim(p.nr_telefone), ''), p.ds_contatos) as nr_fone \n"
                            . "  , left(convert(varchar(12), p.dt_nascimento, 120), 10) as dt_nasc    \n" // Formato YYYY-MM-DD
                            . "  , left(convert(varchar(12), getdate(), 120), 10)       as dt_hoje    \n" // Formato YYYY-MM-DD
                            . "from dbo.tbl_paciente p  \n"
                            . "where ((upper(p.nm_paciente)  like concat('%', '{$filtro}', '%')) "
                            . "    or (upper(p.nm_paciente)  like concat('%', '" . remover_acentos($filtro) . "', '%')) \n"
                            . "    or (upper(p.nm_mnemonico) like concat('%', '" . str_replace(".", "%", $metafone) . "', '%')) \n"
                            . "  )               \n"
                            . $tipo . "          \n"
                            . "order by          \n"
                            . "    p.nm_paciente \n";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $ds_idade    = calcular_idade($obj->dt_nasc, $obj->dt_hoje);
                            $nr_registro = (isset($obj->nr_rg)?(trim($obj->nr_rg) !== ""?trim($obj->nr_rg . " " . $obj->ds_orgao_rg):"..."):"...");
                            $nr_fone     = (isset($obj->nr_fone)?(trim($obj->nr_fone) !== ""?trim($obj->nr_fone):"..."):"...");
                            $retorno .= tr_table($obj->cd_paciente, $obj->nm_paciente, $nr_fone, $ds_idade, $nr_registro, $obj->nr_cpf, $obj->sn_ativo);
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
            
                case 'listar_pacientes' : {
                    try {
                        $input_type = strip_tags( trim($_POST['input_type']) );
                        $input_name = strip_tags( trim($_POST['input_name']) );
                        
                        $sql = 
                              "Select   \n"
                            . "    p.*  \n"
                            . "  , coalesce(nullif(trim(p.nr_celular), ''), nullif(trim(p.nr_telefone), ''), p.ds_contatos) as nr_fone \n"
                            . "  , left(convert(varchar(12), p.dt_nascimento, 120), 10) as dt_nasc    \n" // Formato YYYY-MM-DD
                            . "  , left(convert(varchar(12), getdate(), 120), 10)       as dt_hoje    \n" // Formato YYYY-MM-DD
                            . "from dbo.tbl_paciente p  \n"
                            . "where (p.sn_ativo = 1)   \n"
                            . "order by          \n"
                            . "    p.nm_paciente \n";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $retorno = "";
                        
                        if ($input_type === 'select') {
                            $retorno  = "<select class='form-control select2'  id='{$input_name}' style='width: 100%;'>";
                            $retorno .= "  <option value='0'>Selecione o paciente</option>";
                        }
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            if ($input_type === 'select') {
                                $retorno .= "<option value='{$obj->cd_paciente}'>{$obj->nm_paciente}</option>";
                            }
                        }

                        if ($input_type === 'select') {
                            $retorno .= "</select>";
                        }
                        
                        echo $retorno;
                    } catch (Exception $ex) {
                        echo $ex . (isset($pdo) ? "<br><br><strong>Code:</strong> " . $pdo->errorInfo()[1] . "<br><strong>Message:</strong> " .  $pdo->errorInfo()[2] : "");
                    } finally {
                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                    }
                } break;
            
                case 'buscar_pacientes' : {
                    try {
                        $ds_filtro = strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $filtro    = str_replace(" ", "%", $ds_filtro);
                        $metafone  = meta_fonema($ds_filtro);
                        $data_nasc = explode('/', $filtro);
                        
                        $retorno = 
                              "<table id='tb-pacientes' class='table table-bordered table-hover'> \n"
                            . "  <thead>                    \n"
                            . "    <tr>                     \n"
                            . "      <th>Pront.</th>        \n"
                            . "      <th>Nome</th>          \n"
                            . "      <th>Nascimento</th>    \n"
                            . "      <th>Idade</th>         \n"
                            . "      <th>Ativo</th>         \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $sql = 
                              "Select   \n"
                            . "    p.*  \n"
                            . "  , coalesce(nullif(trim(p.nr_celular), ''), nullif(trim(p.nr_telefone), ''), p.ds_contatos) as nr_fone \n"
                            . "  , left(convert(varchar(12), p.dt_nascimento, 120), 10) as dt_nasc    \n" // Formato YYYY-MM-DD
                            . "  , left(convert(varchar(12), getdate(), 120), 10)       as dt_hoje    \n" // Formato YYYY-MM-DD
                            . "  , convert(varchar(12), p.dt_nascimento, 103) as data_nascimento      \n" // Formato DD/MM/YYYY
                            . "from dbo.tbl_paciente p  \n"
                            . "where ((upper(p.nm_paciente)  like concat('%', '{$filtro}', '%')) "
                            . "    or (upper(p.nm_paciente)  like concat('%', '" . remover_acentos($filtro) . "', '%')) \n"
                            . "    or (upper(p.nm_mnemonico) like concat('%', '" . str_replace(".", "%", $metafone) . "', '%')) \n"
                            . "  )               \n"
                            . "order by          \n"
                            . "    p.nm_paciente \n";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $dt_nascimento = (isset($obj->data_nascimento)?$obj->data_nascimento:"...");
                            $ds_idade    = calcular_idade($obj->dt_nasc, $obj->dt_hoje);
                            $nr_registro = (isset($obj->nr_rg)?(trim($obj->nr_rg) !== ""?trim($obj->nr_rg . " " . $obj->ds_orgao_rg):"..."):"...");
                            $nr_fone     = (isset($obj->nr_fone)?(trim($obj->nr_fone) !== ""?trim($obj->nr_fone):"..."):"...");
                            $retorno .= tr_table_busca($obj->cd_paciente, $obj->nm_paciente, $dt_nascimento, $ds_idade, $obj->nr_celular, $obj->nr_telefone, $obj->ds_email, $obj->sn_ativo);
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
            
                case 'carregar_paciente' : {
                    try {
                        $cd_paciente = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['codigo'])));
                        
                        $file = '../../logs/json/paciente_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select   \n"
                            . "    p.*  \n"
                            . "  , coalesce(nullif(trim(p.nr_celular), ''), nullif(trim(p.nr_telefone), ''), p.ds_contatos) as nr_fone \n"
                            . "  , left(convert(varchar(12), p.dt_nascimento, 120), 10) as dt_nasc    \n" // Formato YYYY-MM-DD
                            . "  , left(convert(varchar(12), getdate(), 120), 10)       as dt_hoje    \n" // Formato YYYY-MM-DD
                            . "  , convert(varchar(12), p.dt_nascimento, 103) as dt_nascimento_temp   \n" // Formato DD/MM/YYYY
                            . "  , convert(varchar(12), p.dt_emissao_rg, 103) as dt_emissao_rg_temp   \n" // Formato DD/MM/YYYY
                            . "  , u.ds_email as em_usuario \n"
                            // Endereço (Customizado)    
                            . "  , coalesce(p.end_logradouro, nullif(trim(concat(coalesce(l.sg_tipo, l.ds_tipo), ' ', p.ds_endereco, ', ', p.nr_endereco, ' - ', p.ds_complemento)), ',  -')) as logradouro \n"
                            . "  , coalesce(p.end_bairro, p.nm_bairro) as bairro \n"
                            . "  , coalesce(p.end_estado, e.nm_estado) as estado \n"
                            . "  , coalesce(p.end_cidade, c.nm_cidade) as cidade \n"
                            . "from dbo.tbl_paciente p      \n"
                            . "  left join dbo.sys_tipo_logradouro l on (l.cd_tipo = p.tp_endereco) \n"
                            . "  left join dbo.sys_estado e on (e.cd_estado = p.cd_estado)          \n"
                            . "  left join dbo.sys_cidade c on (c.cd_cidade = p.cd_cidade)          \n"
                            . "  left join dbo.sys_usuario u on (u.id_usuario = p.us_cadastro)      \n"
                            . "where (p.cd_paciente = {$cd_paciente}) ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $ds_idade    = calcular_idade($obj->dt_nasc, $obj->dt_hoje);
                            $nr_registro = trim($obj->nr_rg . " " . $obj->ds_orgao_rg);
                            
                            $registro['registro'][0]['prontario']  = $obj->cd_paciente;
                            $registro['registro'][0]['nome']       = $obj->nm_paciente;
                            $registro['registro'][0]['metafonema'] = $obj->nm_mnemonico;
                            $registro['registro'][0]['nascimento'] = $obj->dt_nascimento_temp;
                            $registro['registro'][0]['idade']      = $ds_idade;
                            $registro['registro'][0]['sexo']       = $obj->tp_sexo;
                            $registro['registro'][0]['pai']        = $obj->nm_pai;
                            $registro['registro'][0]['mae']        = $obj->nm_mae;
                            $registro['registro'][0]['foto']       = $obj->ft_paciente;
                            //-- Documentos
                            $registro['registro'][0]['cpf']      = $obj->nr_cpf;
                            $registro['registro'][0]['registro'] = $nr_registro;
                            $registro['registro'][0]['rg']       = $obj->nr_rg;
                            $registro['registro'][0]['orgao']    = $obj->ds_orgao_rg;
                            $registro['registro'][0]['emissao']  = $obj->dt_emissao_rg_temp;
                            //-- Contato
                            $registro['registro'][0]['fone']     = $obj->nr_telefone;
                            $registro['registro'][0]['celular']  = $obj->nr_celular;
                            $registro['registro'][0]['contatos'] = $obj->ds_contatos;
                            $registro['registro'][0]['email']    = $obj->ds_email;
                            //-- Endereço  (Customizado)
                            $registro['registro'][0]['end_logradouro'] = $obj->logradouro;
                            $registro['registro'][0]['end_bairro']     = $obj->bairro;
                            $registro['registro'][0]['end_estado']     = $obj->estado;
                            $registro['registro'][0]['end_cidade']     = $obj->cidade;
                            //-- Endereço 
                            $registro['registro'][0]['cep']         = $obj->nr_cep;
                            $registro['registro'][0]['tipo']        = $obj->tp_endereco;
                            $registro['registro'][0]['endereco']    = $obj->ds_endereco;
                            $registro['registro'][0]['numero']      = $obj->nr_endereco;
                            $registro['registro'][0]['complemento'] = $obj->ds_complemento;
                            $registro['registro'][0]['bairro']      = $obj->nm_bairro;
                            $registro['registro'][0]['estado']      = $obj->cd_estado;
                            $registro['registro'][0]['cidade']      = $obj->cd_cidade;
                            //-- Dados para atendimento médido
                            $registro['registro'][0]['convenio']    = $obj->cd_convenio;
                            $registro['registro'][0]['matricula']   = $obj->nr_matricula;
                            $registro['registro'][0]['alergias']    = $obj->ds_alergias;
                            $registro['registro'][0]['observacoes'] = $obj->ds_observacoes;
                            //-- Outras informações
                            $registro['registro'][0]['codigo_profissao'] = $obj->cd_profissao;
                            $registro['registro'][0]['profissao']        = $obj->ds_profissao;
                            $registro['registro'][0]['acompanhante']     = $obj->nm_acompanhante;
                            $registro['registro'][0]['indicacao']        = $obj->nm_indicacao;
                            //-- Auditoria básica
                            $registro['registro'][0]['cadastro']  = $obj->dh_cadastro;
                            $registro['registro'][0]['usuario']   = $obj->us_cadastro;
                            $registro['registro'][0]['ativo']     = $obj->sn_ativo;
                            $registro['registro'][0]['usuario']   = $obj->em_usuario;
                        } else {
                            $registro['registro'][0]['ativo'] = "0";
                        }
                        
                        $json = json_encode($registro);
                        file_put_contents($file, $json);
                        
                        echo "OK";
                    } catch (Exception $ex) {
                        echo $ex . (isset($pdo) ? "<br><br><strong>Code:</strong> " . $pdo->errorInfo()[1] . "<br><strong>Message:</strong> " .  $pdo->errorInfo()[2] : "");
                    } finally {
                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                    }
                } break;
            
                case 'salvar_paciente' : {
                    try {
                        $id_empresa    = strip_tags( trim($_POST['empresa']) );
                        $cd_paciente   = (float)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_paciente'])));
                        $nm_paciente   = strip_tags( trim($_POST['nm_paciente']) );
                        $dt_nascimento = strip_tags( trim($_POST['dt_nascimento']) );
                        $tp_sexo       = strip_tags( trim($_POST['tp_sexo']) );
                        $cd_profissao  = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_profissao'])));
                        $ds_profissao  = strip_tags( trim($_POST['ds_profissao']) );
                        $nr_rg         = preg_replace("/[^0-9]/", "", strip_tags( strtoupper(trim($_POST['nr_rg'])) ) );
                        $ds_orgao_rg   = strip_tags( trim($_POST['ds_orgao_rg']) );
                        $dt_emissao_rg = strip_tags( trim($_POST['dt_emissao_rg']) );
                        $nr_cpf        = preg_replace("/[^0-9]/", "", strip_tags( strtoupper(trim($_POST['nr_cpf'])) ) );
                        $nm_acompanhante = strip_tags( trim($_POST['nm_acompanhante']) );
                        $nm_pai          = strip_tags( trim($_POST['nm_pai']) );
                        $nm_mae          = strip_tags( trim($_POST['nm_mae']) );
                        // Endereço (Customizado)
                        $end_logradouro = strip_tags( trim($_POST['end_logradouro']) );
                        $end_bairro     = strip_tags( trim($_POST['end_bairro']) );
                        $end_cidade     = strip_tags( trim($_POST['end_cidade']) );
                        $end_estado     = strip_tags( trim($_POST['end_estado']) );
                        // Endereço
                        $cd_estado       = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_estado'])));
                        $cd_cidade       = (float)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_cidade'])));
                        $tp_endereco     = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tp_endereco'])));
                        $ds_endereco     = strip_tags( trim($_POST['ds_endereco']) );
                        $nr_endereco     = strip_tags( trim($_POST['nr_endereco']) );
                        $ds_complemento  = strip_tags( trim($_POST['ds_complemento']) );
                        $nm_bairro       = strip_tags( trim($_POST['nm_bairro']) );
                        $nr_cep          = (float)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['nr_cep'])));
                        // Contatos
                        $nr_telefone     = strip_tags( trim($_POST['nr_telefone']) );
                        $nr_celular      = strip_tags( trim($_POST['nr_celular']) );
                        $ds_contatos     = strip_tags( trim($_POST['ds_contatos']) );
                        $ds_email        = strip_tags( trim($_POST['ds_email']) );
                        // Outras informações
                        $cd_convenio     = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_convenio'])));
                        $nr_matricula    = strip_tags( trim($_POST['nr_matricula']) );
                        $nm_indicacao    = strip_tags( trim($_POST['nm_indicacao']) );
                        $ds_alergias     = strip_tags( trim($_POST['ds_alergias']) );
                        $ds_observacoes  = strip_tags( trim($_POST['ds_observacoes']) );
                        $sn_ativo = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_ativo'])));
                        
                        $nm_mnemonico = meta_fonema($nm_paciente);
                        
                        // Validar dados
                        if ($nr_cpf !== "00000000000") {
                            if (($nr_cpf !== "") && !cpf_valido($nr_cpf)) {
                                echo "Número de <strong>CPF</strong> inválido!";
                                exit();
                            }
                        }
                        
                        $nr_cpf = formatarTexto('###.###.###-##', $nr_cpf);
                        
                        $file = '../../logs/json/paciente_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    p.* "
                            . "from dbo.tbl_paciente p   "
                            . "where (p.cd_paciente = {$cd_paciente})";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.tbl_paciente ("
                                //-- Identificação
                                . "    nm_paciente		"
                                . "  , nm_mnemonico             "
                                . "  , dt_nascimento            "
                                . "  , tp_sexo			"
                                . "  , nm_pai			"
                                . "  , nm_mae			"
                                //. "  , ft_paciente		"
                                //-- Documentos      
                                . "  , nr_cpf			"
                                . "  , nr_rg			"
                                . "  , ds_orgao_rg		"
                                . "  , dt_emissao_rg            "
                                //-- Contato         
                                . "  , nr_telefone		"
                                . "  , nr_celular		"
                                . "  , ds_contatos		"
                                . "  , ds_email                 "
                                //-- Endereço atual  (Customizado)
                                . "  , end_logradouro           "
                                . "  , end_bairro		"
                                . "  , end_cidade		"
                                . "  , end_estado		"
                                //-- Endereço atual  
                                . "  , nr_cep			"
                                . "  , tp_endereco		"
                                . "  , ds_endereco		"
                                . "  , nr_endereco		"
                                . "  , ds_complemento           "
                                . "  , nm_bairro		"
                                . "  , cd_estado		"
                                . "  , cd_cidade		"
                                //-- Dados para atendimento médico
                                . "  , cd_convenio		"
                                . "  , nr_matricula             "
                                . "  , ds_alergias		"
                                . "  , ds_observacoes           "
                                //-- Outras informações 
                                . "  , cd_profissao             "
                                . "  , ds_profissao             "
                                . "  , nm_acompanhante          "
                                . "  , nm_indicacao             "
                                //-- Auditoria básica   
                                . "  , dh_cadastro              "
                                . "  , us_cadastro		"
                                . "  , sn_ativo                 "
                                . ") "
                                . "    OUTPUT                   "
                                . "    INSERTED.cd_paciente     "
                                . "  , INSERTED.nm_paciente     "
                                . "  , INSERTED.nm_mnemonico    "
                                . "  , left(convert(varchar(12), INSERTED.dt_nascimento, 120), 10) as dt_nasc    \n" // Formato YYYY-MM-DD
                                . "  , left(convert(varchar(12), getdate(), 120), 10)              as dt_hoje    \n" // Formato YYYY-MM-DD
                                . "  , coalesce(nullif(trim(INSERTED.nr_celular), ''), nullif(trim(INSERTED.nr_telefone), ''), INSERTED.ds_contatos) as nr_fone \n"
                                . "values (           "
                                //-- Identificação
                                . "    :nm_paciente		"
                                . "  , :nm_mnemonico            "
                                . "  , " . ($dt_nascimento !== ""?"convert(date, '{$dt_nascimento}', 103) ":"NULL")
                                . "  , :tp_sexo			"
                                . "  , :nm_pai			"
                                . "  , :nm_mae			"
                                //. "  , :ft_paciente		"
                                //-- Documentos      
                                . "  , :nr_cpf			"
                                . "  , :nr_rg			"
                                . "  , :ds_orgao_rg		"
                                . "  , " . ($dt_emissao_rg !== ""?"convert(date, '{$dt_emissao_rg}', 103) ":"NULL")
                                //-- Contato         
                                . "  , :nr_telefone		"
                                . "  , :nr_celular		"
                                . "  , :ds_contatos		"
                                . "  , :ds_email                "
                                //-- Endereço atual  (Customizado)
                                . "  , :end_logradouro          "
                                . "  , :end_bairro		"
                                . "  , :end_cidade		"
                                . "  , :end_estado		"
                                //-- Endereço atual  
                                . "  , " . ($nr_cep !== 0.0?"{$nr_cep}":"NULL")
                                . "  , " . ($tp_endereco !== 0?"{$tp_endereco}":"NULL")
                                . "  , :ds_endereco		"
                                . "  , :nr_endereco		"
                                . "  , :ds_complemento          "
                                . "  , :nm_bairro		"
                                . "  , " . ($cd_estado !== 0?"{$cd_estado}":"NULL")
                                . "  , " . ($cd_cidade !== 0?"{$cd_cidade}":"NULL")
                                //-- Dados para atendimento médico
                                . "  , " . ($cd_convenio !== 0?"{$cd_convenio}":"NULL")
                                . "  , :nr_matricula            "
                                . "  , :ds_alergias		"
                                . "  , :ds_observacoes          "
                                //-- Outras informações 
                                . "  , " . ($cd_profissao !== 0?"{$cd_profissao}":"NULL")
                                . "  , :ds_profissao            "
                                . "  , :nm_acompanhante         "
                                . "  , :nm_indicacao            "
                                //-- Auditoria básica   
                                . "  , getdate()                "
                                . "  , :us_cadastro		"
                                . "  , :sn_ativo                "
                                . ")"); 

                            $stm->execute(array(
                                //-- Identificação
                                  ':nm_paciente'     => $nm_paciente
                                , ':nm_mnemonico'    => $nm_mnemonico
                                , ':tp_sexo'         => $tp_sexo
                                , ':nm_pai'          => $nm_pai
                                , ':nm_mae'          => $nm_mae
                                //-- Documentos
                                , ':nr_cpf'          => $nr_cpf
                                , ':nr_rg'           => $nr_rg
                                , ':ds_orgao_rg'     => $ds_orgao_rg
                                //-- Contato
                                , ':nr_telefone'     => $nr_telefone
                                , ':nr_celular'      => $nr_celular
                                , ':ds_contatos'     => $ds_contatos
                                , ':ds_email'        => $ds_email
                                //-- Endereço atual (Customizado)
                                , ':end_logradouro'  => $end_logradouro
                                , ':end_bairro'      => $end_bairro
                                , ':end_cidade'      => $end_cidade
                                , ':end_estado'      => $end_estado
                                //-- Endereço atual  
                                , ':ds_endereco'     => $ds_endereco
                                , ':nr_endereco'     => $nr_endereco
                                , ':ds_complemento'  => $ds_complemento
                                , ':nm_bairro'       => $nm_bairro
                                //-- Dados para atendimento médico
                                , ':nr_matricula'    => $nr_matricula
                                , ':ds_alergias'     => $ds_alergias
                                , ':ds_observacoes'  => $ds_observacoes
                                //-- Outras informações 
                                , ':ds_profissao'    => $ds_profissao
                                , ':nm_acompanhante' => $nm_acompanhante
                                , ':nm_indicacao'    => $nm_indicacao
                                //-- Auditoria básica   
                                , ':us_cadastro'     => $user->getCodigo()
                                , ':sn_ativo'        => $sn_ativo
                            ));
                            
                            $registro = array('registro' => array());

                            if (($obj = $stm->fetch(PDO::FETCH_OBJ)) !== false) {
                                $ds_idade    = calcular_idade($obj->dt_nasc, $obj->dt_hoje);
                                $nr_registro = (isset($nr_rg)?(trim($nr_rg) !== ""?trim($nr_rg . " " . $ds_orgao_rg):"..."):"...");
                                $nr_fone     = (isset($obj->nr_fone)?(trim($obj->nr_fone) !== ""?trim($obj->nr_fone):"..."):"...");
                                $tr_table    = tr_table($obj->cd_paciente, $obj->nm_paciente, $nr_fone, $ds_idade, $nr_registro, $nr_cpf, $sn_ativo);
                                
                                $registro['registro'][0]['prontuario'] = $obj->cd_paciente;
                                $registro['registro'][0]['nome']       = $obj->nm_paciente;
                                $registro['registro'][0]['fone']       = $obj->nr_fone;
                                $registro['registro'][0]['celular']    = $nr_celular;
                                $registro['registro'][0]['telefone']   = $nr_telefone;
                                $registro['registro'][0]['email']      = $ds_email;
                                $registro['registro'][0]['rg']         = $nr_registro;
                                $registro['registro'][0]['cpf']        = $nr_cpf;
                                $registro['registro'][0]['idade']      = $ds_idade;
                                $registro['registro'][0]['tr_table']   = $tr_table;
                            }
                            
                            $pdo->commit();

//                            // Gerar Tabela de Exames vazia para o novo paciente
//                            $data = date('d/m/Y');
//                            $pdo->beginTransaction();
//                            
//                            $sql = 
//                                  "exec dbo.spGerarListaExamesPaciente \n"
//                                . "  N'{$id_empresa}' \n"
//                                . ", {$registro['registro'][0]['prontuario']} \n"
//                                . ", N'{$data}'  \n"
//                                . ", N'{$user->getCodigo()}'";
//                            
//                            $qry = $pdo->query($sql);
//                            
//                            // Gerar Tabela de Exames vazia para o novo paciente
//                            $sql = 
//                                  "exec dbo.spGerarListaEvolucaoMedidaPaciente \n"
//                                . "  N'{$id_empresa}' \n"
//                                . ", {$registro['registro'][0]['prontuario']} \n"
//                                . ", N'{$data}'  \n"
//                                . ", N'{$user->getCodigo()}'";
//                            
//                            $qry = $pdo->query($sql);
//                            
//                            $pdo->commit();
//                            
                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        } else {
                            $pdo->beginTransaction();
                            
                            $sql = 
                                  "Update dbo.tbl_paciente Set                  \n"
                                . "    nm_paciente   = :nm_paciente		\n"
                                . "  , nm_mnemonico  = :nm_mnemonico            \n"
                                . "  , dt_nascimento = " . ($dt_nascimento !== ""?"convert(date, '{$dt_nascimento}', 103) ":"NULL")
                                . "  , tp_sexo       = :tp_sexo			\n"
                                . "  , nm_pai        = :nm_pai			\n"
                                . "  , nm_mae        = :nm_mae			\n"
                                //. "  , :ft_paciente		\n"
                                //-- Documentos      
                                . "  , nr_cpf        = :nr_cpf			\n"
                                . "  , nr_rg         = :nr_rg			\n"
                                . "  , ds_orgao_rg   = :ds_orgao_rg		\n"
                                . "  , dt_emissao_rg = " . ($dt_emissao_rg !== ""?"convert(date, '{$dt_emissao_rg}', 103) ":"NULL")
                                //-- Contato         
                                . "  , nr_telefone   = :nr_telefone		\n"
                                . "  , nr_celular    = :nr_celular		\n"
                                . "  , ds_contatos   = :ds_contatos		\n"
                                . "  , ds_email      = :ds_email                \n"
                                //-- Endereço atual  (Customizado)
                                . "  , end_logradouro = :end_logradouro "
                                . "  , end_bairro     = :end_bairro     "
                                . "  , end_cidade     = :end_cidade     "
                                . "  , end_estado     = :end_estado     "
                                //-- Endereço atual  
                                . "  , nr_cep         = " . ($nr_cep !== 0.0?"{$nr_cep}":"NULL")
                                . "  , tp_endereco    = " . ($tp_endereco !== 0?"{$tp_endereco}":"NULL")
                                . "  , ds_endereco    = :ds_endereco		\n"
                                . "  , nr_endereco    = :nr_endereco		\n"
                                . "  , ds_complemento = :ds_complemento         \n"
                                . "  , nm_bairro      = :nm_bairro		\n"
                                . "  , cd_estado      = " . ($cd_estado !== 0?"{$cd_estado}":"NULL")
                                . "  , cd_cidade      = " . ($cd_cidade !== 0?"{$cd_cidade}":"NULL")
                                //-- Dados para atendimento médico
                                . "  , cd_convenio    = " . ($cd_convenio !== 0?"{$cd_convenio}":"NULL")
                                . "  , nr_matricula   = :nr_matricula           \n"
                                . "  , ds_alergias    = :ds_alergias		\n"
                                . "  , ds_observacoes = :ds_observacoes         \n"
                                //-- Outras informações 
                                . "  , cd_profissao    = " . ($cd_profissao !== 0?"{$cd_profissao}":"NULL")
                                . "  , ds_profissao    = :ds_profissao          \n"
                                . "  , nm_acompanhante = :nm_acompanhante       \n"
                                . "  , nm_indicacao    = :nm_indicacao          \n"
                                //-- Auditoria básica   
                                . "  , sn_ativo        = :sn_ativo              \n"
                                . "where cd_paciente   = :cd_paciente"; //echo $sql . "<br>";
                                
                            $stm = $pdo->prepare($sql);

                            $stm->execute(array(
                                //-- Identificação
                                  ':nm_paciente'     => $nm_paciente
                                , ':nm_mnemonico'    => $nm_mnemonico
                                , ':tp_sexo'         => $tp_sexo
                                , ':nm_pai'          => $nm_pai
                                , ':nm_mae'          => $nm_mae
                                //-- Documentos
                                , ':nr_cpf'          => $nr_cpf
                                , ':nr_rg'           => $nr_rg
                                , ':ds_orgao_rg'     => $ds_orgao_rg
                                //-- Contato
                                , ':nr_telefone'     => $nr_telefone
                                , ':nr_celular'      => $nr_celular
                                , ':ds_contatos'     => $ds_contatos
                                , ':ds_email'        => $ds_email
                                //-- Endereço atual (Customizado)
                                , ':end_logradouro'  => $end_logradouro
                                , ':end_bairro'      => $end_bairro
                                , ':end_cidade'      => $end_cidade
                                , ':end_estado'      => $end_estado
                                //-- Endereço atual  
                                , ':ds_endereco'     => $ds_endereco
                                , ':nr_endereco'     => $nr_endereco
                                , ':ds_complemento'  => $ds_complemento
                                , ':nm_bairro'       => $nm_bairro
                                //-- Dados para atendimento médico
                                , ':nr_matricula'    => $nr_matricula
                                , ':ds_alergias'     => $ds_alergias
                                , ':ds_observacoes'  => $ds_observacoes
                                //-- Outras informações 
                                , ':ds_profissao'    => $ds_profissao
                                , ':nm_acompanhante' => $nm_acompanhante
                                , ':nm_indicacao'    => $nm_indicacao
                                //-- Status
                                , ':sn_ativo'        => $sn_ativo
                                //-- ID
                                , ':cd_paciente'     => $cd_paciente
                            ));
                            
                            $registro = array('registro' => array());

                            $ds_idade    = calcular_idade(set_format_date($dt_nascimento, 'Y-m-d'), date('Y-m-d'));
                            $nr_registro = (isset($nr_rg)?(trim($nr_rg) !== ""?trim($nr_rg . " " . $ds_orgao_rg):"..."):"...");
                            $nr_fone     = ($nr_telefone !== ""?$nr_telefone:($nr_celular !== ""?$nr_celular:"..."));
                            $tr_table    = tr_table($cd_paciente, $nm_paciente, $nr_fone, $ds_idade, $nr_registro, $nr_cpf, $sn_ativo);

                            $registro['registro'][0]['prontuario'] = $cd_paciente;
                            $registro['registro'][0]['nome']       = $nm_paciente;
                            $registro['registro'][0]['fone']       = $nr_fone;
                            $registro['registro'][0]['celular']    = $nr_celular;
                            $registro['registro'][0]['telefone']   = $nr_telefone;
                            $registro['registro'][0]['email']      = $ds_email;
                            $registro['registro'][0]['rg']         = $nr_registro;
                            $registro['registro'][0]['cpf']        = $nr_cpf;
                            $registro['registro'][0]['idade']      = $ds_idade;
                            $registro['registro'][0]['tr_table']   = $tr_table;
                            
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
            
                case 'excluir_paciente' : {
                    try {
                        $cd_paciente = (float)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['paciente'])));
                        
                        if ($cd_paciente === 1.0) {
                            echo "Este convênio é padrão do sistema e não poderá ser excluído.";
                        } else {
                            $pdo = Conexao::getConnection();
                            
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Delete from dbo.tbl_paciente    "
                                . "where cd_paciente = :cd_paciente");

                            $stm->execute(array(
                                  ':cd_paciente' => $cd_paciente
                            ));

                            $pdo->commit();
                            
                            echo "OK";
                        }
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
            }
        } else {
            echo painel_alerta_danger("Permissão negada, pois a ação não foi definida.");        
        }
    }