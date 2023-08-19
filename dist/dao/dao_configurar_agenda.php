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

    function tr_table($cd_agenda, $nm_agenda, $ds_especialidade, $sn_ativo) {
        $referencia = (int)$cd_agenda;

        //$status  = "<span class='label-" . ((int)$sn_ativo === 1?"success":"danger") . " btn-xs' id='reg-status_{$referencia}'>" . ((int)$sn_ativo === 1?"Ativo":"Inativo") . "</span>";
        $status  = "<i id='status_configuracao_{$referencia}' class='fa " . ((int)$sn_ativo === 1?"fa-check-square-o text-green":"fa-square-o text-red") . "'></i>";
        $excluir = "<a id='excluir_configuracao_{$referencia}' href='javascript:preventDefault();' onclick='excluir_registro( this.id, this )'><i class='fa fa-trash' title='Excluir Configuração'></i>";

        $retorno =
              "    <tr id='tr-linha_{$referencia}'>  \n"
            . "      <td><a href='#' id='reg-configuracao_{$referencia}' onclick='abrir_cadastro(this, this.id);'>" . str_pad($cd_agenda, 2, "0", STR_PAD_LEFT) . "</a></td>  \n"
            . "      <td>{$nm_agenda}</td>              \n"
            . "      <td>{$ds_especialidade}</td>       \n"
            . "      <td align='center'>{$status}</td>  \n"
            . "      <td align='center'>{$excluir}</td> \n"
            . "    </tr>  \n";
            
        return $retorno;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'pesquisar_configuracoes' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $tp_filtro   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tipo'])));
                        $ds_filtro   = strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $qt_registro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/configurar_agenda_' . $cookieID . '.json';
                        if (file_exists($file_cookie)) {
                            unlink($file_cookie);
                        }
                        
                        $registros = array('filtro' => array());
                        $registros['filtro'][0]['qt_registro'] = $qt_registro;
                        $registros['filtro'][0]['cd_tipo']     = $tp_filtro;
                        $registros['filtro'][0]['empresa']     = $id_empresa;
                        
                        $filtro = ($tp_filtro === 1?"  and (c.sn_ativo = 1)":"");
                        
                        $json = json_encode($registros);
                        file_put_contents($file_cookie, $json);
                        // Gravar as configurações do filtro utilizado pelo usuário -- (FINAL)
                        
                        $retorno = 
                              "<table id='tb-configuracoes' class='table table-bordered table-hover'> \n"
                            . "  <thead>                        \n"
                            . "    <tr>                         \n"
                            . "      <th>#</th>                 \n"
                            . "      <th>Nome</th>              \n"
                            . "      <th>Especialidade</th>     \n"
                            . "      <th>Ativo</th>             \n"
                            . "      <th></th>                  \n"
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $sql = 
                              "Select  "
                            . "    c.* "
                            . "  , coalesce(e.ds_especialidade, 'Todas') as ds_especialidade "
                            . "  , coalesce(p.nm_profissional, 'Todos')  as nm_profissional  "
                            . "  , concat( "
                            . "	     case when c.sn_domingo = 1 then 'D' else '' end "
                            . "    , case when c.sn_domingo = 1 then 'S' else '' end "
                            . "    , case when c.sn_domingo = 1 then 'T' else '' end "
                            . "    , case when c.sn_domingo = 1 then 'Q' else '' end "
                            . "    , case when c.sn_domingo = 1 then 'Q' else '' end "
                            . "    , case when c.sn_domingo = 1 then 'S' else '' end "
                            . "    , case when c.sn_domingo = 1 then 'S' else '' end "
                            . "	) as ds_disponibilidade         "
                            . "from dbo.tbl_configurar_agenda c "
                            . "  left join dbo.tbl_especialidade e on (e.cd_especialidade = c.cd_especialidade) "
                            . "  left join dbo.tbl_profissional p on (p.cd_profissional = c.cd_profissional)    "
                            . "where (c.id_empresa = '{$id_empresa}') "
                            . ($filtro) . "     "
                            . "order by         "
                            . "    c.nm_agenda  "; 
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $retorno .= tr_table($obj->cd_agenda, $obj->nm_agenda, $obj->ds_especialidade, $obj->sn_ativo);
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
            
                case 'carregar_configuracao' : {
                    try {
                        $cd_agenda = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['codigo'])));
                        
                        $file = '../../logs/json/configurar_agenda_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    c.* "
                            . "  , convert(varchar(12), c.dt_inicial, 103) as data_inicial "
                            . "  , convert(varchar(12), c.dt_final,   103) as data_final   "
                            . "from dbo.tbl_configurar_agenda c "
                            . "where (c.cd_agenda = {$cd_agenda}) ";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][0]['codigo']         = $obj->cd_agenda;
                            $registro['registro'][0]['nome']           = $obj->nm_agenda;
                            $registro['registro'][0]['observacoes']    = $obj->ds_observacoes;;
                            $registro['registro'][0]['especialidade']  = (isset($obj->cd_especialidade)?$obj->cd_especialidade:"0");
                            $registro['registro'][0]['profissional']   = (isset($obj->cd_profissional)?$obj->cd_profissional:"0");
                            $registro['registro'][0]['data_inicial']   = $obj->data_inicial;
                            $registro['registro'][0]['data_final']     = $obj->data_final;
                            $registro['registro'][0]['divisao_agenda'] = $obj->hr_divisao_agenda;
                            $registro['registro'][0]['ativo']          = $obj->sn_ativo;
                            // Dias de funcionamento                      
                            $registro['registro'][0]['domingo'] = $obj->sn_domingo;
                            $registro['registro'][0]['segunda'] = $obj->sn_segunda;
                            $registro['registro'][0]['terca']   = $obj->sn_terca;
                            $registro['registro'][0]['quarta']  = $obj->sn_quarta;
                            $registro['registro'][0]['quinta']  = $obj->sn_quinta;
                            $registro['registro'][0]['sexta']   = $obj->sn_sexta;
                            $registro['registro'][0]['sabado']  = $obj->sn_sabado;
                            // Domingo                                    
                            $registro['registro'][0]['dom_ini_manha']  = $obj->hr_dom_ini_manha;
                            $registro['registro'][0]['dom_fim_manha']  = $obj->hr_dom_fim_manha;
                            $registro['registro'][0]['dom_ini_tarde']  = $obj->hr_dom_ini_tarde;
                            $registro['registro'][0]['dom_fim_tarde']  = $obj->hr_dom_fim_tarde;
                            // Segunda                                    
                            $registro['registro'][0]['seg_ini_manha']  = $obj->hr_seg_ini_manha;
                            $registro['registro'][0]['seg_fim_manha']  = $obj->hr_seg_fim_manha;
                            $registro['registro'][0]['seg_ini_tarde']  = $obj->hr_seg_ini_tarde;
                            $registro['registro'][0]['seg_fim_tarde']  = $obj->hr_seg_fim_tarde;
                            // Terça                                      
                            $registro['registro'][0]['ter_ini_manha']  = $obj->hr_ter_ini_manha;
                            $registro['registro'][0]['ter_fim_manha']  = $obj->hr_ter_fim_manha;
                            $registro['registro'][0]['ter_ini_tarde']  = $obj->hr_ter_ini_tarde;
                            $registro['registro'][0]['ter_fim_tarde']  = $obj->hr_ter_fim_tarde;
                            // Quarta                                     
                            $registro['registro'][0]['qua_ini_manha']  = $obj->hr_qua_ini_manha;
                            $registro['registro'][0]['qua_fim_manha']  = $obj->hr_qua_fim_manha;
                            $registro['registro'][0]['qua_ini_tarde']  = $obj->hr_qua_ini_tarde;
                            $registro['registro'][0]['qua_fim_tarde']  = $obj->hr_qua_fim_tarde;
                            // Quitnta                                    
                            $registro['registro'][0]['qui_ini_manha']  = $obj->hr_qui_ini_manha;
                            $registro['registro'][0]['qui_fim_manha']  = $obj->hr_qui_fim_manha;
                            $registro['registro'][0]['qui_ini_tarde']  = $obj->hr_qui_ini_tarde;
                            $registro['registro'][0]['qui_fim_tarde']  = $obj->hr_qui_fim_tarde;
                            // Sexta                                      
                            $registro['registro'][0]['sex_ini_manha']  = $obj->hr_sex_ini_manha;
                            $registro['registro'][0]['sex_fim_manha']  = $obj->hr_sex_fim_manha;
                            $registro['registro'][0]['sex_ini_tarde']  = $obj->hr_sex_ini_tarde;
                            $registro['registro'][0]['sex_fim_tarde']  = $obj->hr_sex_fim_tarde;
                            // Sábado                                     
                            $registro['registro'][0]['sab_ini_manha']  = $obj->hr_sab_ini_manha;
                            $registro['registro'][0]['sab_fim_manha']  = $obj->hr_sab_fim_manha;
                            $registro['registro'][0]['sab_ini_tarde']  = $obj->hr_sab_ini_tarde;
                            $registro['registro'][0]['sab_fim_tarde']  = $obj->hr_sab_fim_tarde;
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
            
                case 'salvar_configuracao' : {
                    try {
                        $id_empresa = strip_tags( trim($_POST['empresa']) );
                        $cd_agenda  = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_agenda'])));
                        $nm_agenda  = strip_tags( trim($_POST['nm_agenda']) );
                        $ds_observacoes   = strip_tags( trim($_POST['ds_observacoes']) );
                        $cd_especialidade = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_especialidade'])));
                        $cd_profissional  = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_profissional'])));
                        $dt_inicial = strip_tags( trim($_POST['dt_inicial']) );
                        $dt_final   = strip_tags( trim($_POST['dt_final']) );
                        $hr_divisao_agenda = strip_tags( trim($_POST['hr_divisao_agenda']) );
                        $sn_ativo   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_ativo'])));
                        // Dias da Semana
                        $sn_domingo = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_domingo'])));
                        $sn_segunda = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_segunda'])));
                        $sn_terca   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_terca'])));
                        $sn_quarta  = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_quarta'])));
                        $sn_quinta  = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_quinta'])));
                        $sn_sexta   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_sexta'])));
                        $sn_sabado  = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['sn_sabado'])));
                        // Domingo
                        $hr_dom_ini_manha = strip_tags( trim($_POST['hr_dom_ini_manha']) );
                        $hr_dom_fim_manha = strip_tags( trim($_POST['hr_dom_fim_manha']) );
                        $hr_dom_ini_tarde = strip_tags( trim($_POST['hr_dom_ini_tarde']) );
                        $hr_dom_fim_tarde = strip_tags( trim($_POST['hr_dom_fim_tarde']) );
                        // Segunda
                        $hr_seg_ini_manha = strip_tags( trim($_POST['hr_seg_ini_manha']) );
                        $hr_seg_fim_manha = strip_tags( trim($_POST['hr_seg_fim_manha']) );
                        $hr_seg_ini_tarde = strip_tags( trim($_POST['hr_seg_ini_tarde']) );
                        $hr_seg_fim_tarde = strip_tags( trim($_POST['hr_seg_fim_tarde']) );
                        // Terça
                        $hr_ter_ini_manha = strip_tags( trim($_POST['hr_ter_ini_manha']) );
                        $hr_ter_fim_manha = strip_tags( trim($_POST['hr_ter_fim_manha']) );
                        $hr_ter_ini_tarde = strip_tags( trim($_POST['hr_ter_ini_tarde']) );
                        $hr_ter_fim_tarde = strip_tags( trim($_POST['hr_ter_fim_tarde']) );
                        // Quarta
                        $hr_qua_ini_manha = strip_tags( trim($_POST['hr_qua_ini_manha']) );
                        $hr_qua_fim_manha = strip_tags( trim($_POST['hr_qua_fim_manha']) );
                        $hr_qua_ini_tarde = strip_tags( trim($_POST['hr_qua_ini_tarde']) );
                        $hr_qua_fim_tarde = strip_tags( trim($_POST['hr_qua_fim_tarde']) );
                        // Quinta
                        $hr_qui_ini_manha = strip_tags( trim($_POST['hr_qui_ini_manha']) );
                        $hr_qui_fim_manha = strip_tags( trim($_POST['hr_qui_fim_manha']) );
                        $hr_qui_ini_tarde = strip_tags( trim($_POST['hr_qui_ini_tarde']) );
                        $hr_qui_fim_tarde = strip_tags( trim($_POST['hr_qui_fim_tarde']) );
                        // Sexta
                        $hr_sex_ini_manha = strip_tags( trim($_POST['hr_sex_ini_manha']) );
                        $hr_sex_fim_manha = strip_tags( trim($_POST['hr_sex_fim_manha']) );
                        $hr_sex_ini_tarde = strip_tags( trim($_POST['hr_sex_ini_tarde']) );
                        $hr_sex_fim_tarde = strip_tags( trim($_POST['hr_sex_fim_tarde']) );
                        // Sábado
                        $hr_sab_ini_manha = strip_tags( trim($_POST['hr_sab_ini_manha']) );
                        $hr_sab_fim_manha = strip_tags( trim($_POST['hr_sab_fim_manha']) );
                        $hr_sab_ini_tarde = strip_tags( trim($_POST['hr_sab_ini_tarde']) );
                        $hr_sab_fim_tarde = strip_tags( trim($_POST['hr_sab_fim_tarde']) );
                        
                        // Tratamento de dados
                        if ($cd_especialidade === 0) {
                            $cd_especialidade = "NULL";
                        }
                        if ($cd_profissional === 0) {
                            $cd_profissional = "NULL";
                        }
                        
                        // Domingo
                        $hr_dom_ini_manha = ( (($hr_dom_ini_manha === "") || ($hr_dom_ini_manha === "00:00"))?"NULL":"'{$hr_dom_ini_manha}'" );
                        $hr_dom_fim_manha = ( (($hr_dom_fim_manha === "") || ($hr_dom_fim_manha === "00:00"))?"NULL":"'{$hr_dom_fim_manha}'" );
                        $hr_dom_ini_tarde = ( (($hr_dom_ini_tarde === "") || ($hr_dom_ini_tarde === "00:00"))?"NULL":"'{$hr_dom_ini_tarde}'" );
                        $hr_dom_fim_tarde = ( (($hr_dom_fim_tarde === "") || ($hr_dom_fim_tarde === "00:00"))?"NULL":"'{$hr_dom_fim_tarde}'" );
                        // Segunda
                        $hr_seg_ini_manha = ( (($hr_seg_ini_manha === "") || ($hr_seg_ini_manha === "00:00"))?"NULL":"'{$hr_seg_ini_manha}'" );
                        $hr_seg_fim_manha = ( (($hr_seg_fim_manha === "") || ($hr_seg_fim_manha === "00:00"))?"NULL":"'{$hr_seg_fim_manha}'" );
                        $hr_seg_ini_tarde = ( (($hr_seg_ini_tarde === "") || ($hr_seg_ini_tarde === "00:00"))?"NULL":"'{$hr_seg_ini_tarde}'" );
                        $hr_seg_fim_tarde = ( (($hr_seg_fim_tarde === "") || ($hr_seg_fim_tarde === "00:00"))?"NULL":"'{$hr_seg_fim_tarde}'" );
                        // Terça
                        $hr_ter_ini_manha = ( (($hr_ter_ini_manha === "") || ($hr_ter_ini_manha === "00:00"))?"NULL":"'{$hr_ter_ini_manha}'" );
                        $hr_ter_fim_manha = ( (($hr_ter_fim_manha === "") || ($hr_ter_fim_manha === "00:00"))?"NULL":"'{$hr_ter_fim_manha}'" );
                        $hr_ter_ini_tarde = ( (($hr_ter_ini_tarde === "") || ($hr_ter_ini_tarde === "00:00"))?"NULL":"'{$hr_ter_ini_tarde}'" );
                        $hr_ter_fim_tarde = ( (($hr_ter_fim_tarde === "") || ($hr_ter_fim_tarde === "00:00"))?"NULL":"'{$hr_ter_fim_tarde}'" );
                        // Quarta
                        $hr_qua_ini_manha = ( (($hr_qua_ini_manha === "") || ($hr_qua_ini_manha === "00:00"))?"NULL":"'{$hr_qua_ini_manha}'" );
                        $hr_qua_fim_manha = ( (($hr_qua_fim_manha === "") || ($hr_qua_fim_manha === "00:00"))?"NULL":"'{$hr_qua_fim_manha}'" );
                        $hr_qua_ini_tarde = ( (($hr_qua_ini_tarde === "") || ($hr_qua_ini_tarde === "00:00"))?"NULL":"'{$hr_qua_ini_tarde}'" );
                        $hr_qua_fim_tarde = ( (($hr_qua_fim_tarde === "") || ($hr_qua_fim_tarde === "00:00"))?"NULL":"'{$hr_qua_fim_tarde}'" );
                        // Quinta
                        $hr_qui_ini_manha = ( (($hr_qui_ini_manha === "") || ($hr_qui_ini_manha === "00:00"))?"NULL":"'{$hr_qui_ini_manha}'" );
                        $hr_qui_fim_manha = ( (($hr_qui_fim_manha === "") || ($hr_qui_fim_manha === "00:00"))?"NULL":"'{$hr_qui_fim_manha}'" );
                        $hr_qui_ini_tarde = ( (($hr_qui_ini_tarde === "") || ($hr_qui_ini_tarde === "00:00"))?"NULL":"'{$hr_qui_ini_tarde}'" );
                        $hr_qui_fim_tarde = ( (($hr_qui_fim_tarde === "") || ($hr_qui_fim_tarde === "00:00"))?"NULL":"'{$hr_qui_fim_tarde}'" );
                        // Sexta
                        $hr_sex_ini_manha = ( (($hr_sex_ini_manha === "") || ($hr_sex_ini_manha === "00:00"))?"NULL":"'{$hr_sex_ini_manha}'" );
                        $hr_sex_fim_manha = ( (($hr_sex_fim_manha === "") || ($hr_sex_fim_manha === "00:00"))?"NULL":"'{$hr_sex_fim_manha}'" );
                        $hr_sex_ini_tarde = ( (($hr_sex_ini_tarde === "") || ($hr_sex_ini_tarde === "00:00"))?"NULL":"'{$hr_sex_ini_tarde}'" );
                        $hr_sex_fim_tarde = ( (($hr_sex_fim_tarde === "") || ($hr_sex_fim_tarde === "00:00"))?"NULL":"'{$hr_sex_fim_tarde}'" );
                        // Sábado
                        $hr_sab_ini_manha = ( (($hr_sab_ini_manha === "") || ($hr_sab_ini_manha === "00:00"))?"NULL":"'{$hr_sab_ini_manha}'" );
                        $hr_sab_fim_manha = ( (($hr_sab_fim_manha === "") || ($hr_sab_fim_manha === "00:00"))?"NULL":"'{$hr_sab_fim_manha}'" );
                        $hr_sab_ini_tarde = ( (($hr_sab_ini_tarde === "") || ($hr_sab_ini_tarde === "00:00"))?"NULL":"'{$hr_sab_ini_tarde}'" );
                        $hr_sab_fim_tarde = ( (($hr_sab_fim_tarde === "") || ($hr_sab_fim_tarde === "00:00"))?"NULL":"'{$hr_sab_fim_tarde}'" );
                        
                        $file = '../../logs/json/configurar_agenda_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    c.* "
                            . "from dbo.tbl_configurar_agenda c   "
                            . "where (c.cd_agenda = {$cd_agenda})";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.tbl_configurar_agenda ("
                                . "    nm_agenda         "
                                . "  , ds_observacoes    "
                                . "  , id_empresa        "
                                . "  , cd_especialidade  "
                                . "  , cd_profissional   "
                                . "  , dt_inicial	 "
                                . "  , dt_final		 "
                                . "  , hr_divisao_agenda "
                                . "  , sn_ativo          "
                                  // Dias da Semana  
                                . "  , sn_domingo        "
                                . "  , sn_segunda        "
                                . "  , sn_terca          "
                                . "  , sn_quarta         "
                                . "  , sn_quinta         "
                                . "  , sn_sexta          "
                                . "  , sn_sabado         "
                                  // Domingo
                                . "  , hr_dom_ini_manha	"
                                . "  , hr_dom_fim_manha	"
                                . "  , hr_dom_ini_tarde	"
                                . "  , hr_dom_fim_tarde	"
                                  // Segunda
                                . "  , hr_seg_ini_manha	"
                                . "  , hr_seg_fim_manha	"
                                . "  , hr_seg_ini_tarde	"
                                . "  , hr_seg_fim_tarde	"
                                  // Terça
                                . "  , hr_ter_ini_manha	"
                                . "  , hr_ter_fim_manha	"
                                . "  , hr_ter_ini_tarde	"
                                . "  , hr_ter_fim_tarde	"
                                  // Quarta
                                . "  , hr_qua_ini_manha	"
                                . "  , hr_qua_fim_manha	"
                                . "  , hr_qua_ini_tarde	"
                                . "  , hr_qua_fim_tarde	"
                                  // Quinta
                                . "  , hr_qui_ini_manha	"
                                . "  , hr_qui_fim_manha	"
                                . "  , hr_qui_ini_tarde	"
                                . "  , hr_qui_fim_tarde	"
                                  // Sexta
                                . "  , hr_sex_ini_manha	"
                                . "  , hr_sex_fim_manha	"
                                . "  , hr_sex_ini_tarde	"
                                . "  , hr_sex_fim_tarde	"
                                  // Sábado
                                . "  , hr_sab_ini_manha	"
                                . "  , hr_sab_fim_manha	"
                                . "  , hr_sab_ini_tarde	"
                                . "  , hr_sab_fim_tarde	"
                                . ") "
                                . "    OUTPUT             "
                                . "    INSERTED.cd_agenda "
                                . "  , INSERTED.nm_agenda "
                                . "values (               "
                                . "    :nm_agenda         "
                                . "  , :ds_observacoes    "
                                . "  , :id_empresa        "
                                . "  , " . ($cd_especialidade !== 0?$cd_especialidade:"NULL") . "  "
                                . "  , " . ($cd_profissional  !== 0?$cd_profissional:"NULL")  . "  "
                                . "  , convert(date, '{$dt_inicial}', 103) "
                                . "  , convert(date, '{$dt_final}', 103)   "
                                . "  , :hr_divisao_agenda "
                                . "  , :sn_ativo          "
                                  // Dias da Semana  
                                . "  , :sn_domingo        "
                                . "  , :sn_segunda        "
                                . "  , :sn_terca          "
                                . "  , :sn_quarta         "
                                . "  , :sn_quinta         "
                                . "  , :sn_sexta          "
                                . "  , :sn_sabado         "
                                  // Domingo
                                . "  , {$hr_dom_ini_manha} "
                                . "  , {$hr_dom_fim_manha} "
                                . "  , {$hr_dom_ini_tarde} "
                                . "  , {$hr_dom_fim_tarde} "
                                  // Segunda
                                . "  , {$hr_seg_ini_manha} "
                                . "  , {$hr_seg_fim_manha} "
                                . "  , {$hr_seg_ini_tarde} "
                                . "  , {$hr_seg_fim_tarde} "
                                  // Terça
                                . "  , {$hr_ter_ini_manha} "
                                . "  , {$hr_ter_fim_manha} "
                                . "  , {$hr_ter_ini_tarde} "
                                . "  , {$hr_ter_fim_tarde} "
                                  // Quarta
                                . "  , {$hr_qua_ini_manha} "
                                . "  , {$hr_qua_fim_manha} "
                                . "  , {$hr_qua_ini_tarde} "
                                . "  , {$hr_qua_fim_tarde} "
                                  // Quinta
                                . "  , {$hr_qui_ini_manha} "
                                . "  , {$hr_qui_fim_manha} "
                                . "  , {$hr_qui_ini_tarde} "
                                . "  , {$hr_qui_fim_tarde} "
                                  // Sexta
                                . "  , {$hr_sex_ini_manha} "
                                . "  , {$hr_sex_fim_manha} "
                                . "  , {$hr_sex_ini_tarde} "
                                . "  , {$hr_sex_fim_tarde} "
                                  // Sábado
                                . "  , {$hr_sab_ini_manha} "
                                . "  , {$hr_sab_fim_manha} "
                                . "  , {$hr_sab_ini_tarde} "
                                . "  , {$hr_sab_fim_tarde} "
                                . ")");

                            $stm->execute(array(
                                  ':nm_agenda'         => $nm_agenda
                                , ':ds_observacoes'    => $ds_observacoes
                                , ':id_empresa'        => $id_empresa
                                , ':hr_divisao_agenda' => $hr_divisao_agenda
                                , ':sn_ativo'          => $sn_ativo
                                , ':sn_domingo' => $sn_domingo             // Dias da Semana  
                                , ':sn_segunda' => $sn_segunda
                                , ':sn_terca'   => $sn_terca
                                , ':sn_quarta'  => $sn_quarta
                                , ':sn_quinta'  => $sn_quinta
                                , ':sn_sexta'   => $sn_sexta
                                , ':sn_sabado'  => $sn_sabado
                            ));
                            
                            $registro = array('registro' => array());

                            if (($obj = $stm->fetch(PDO::FETCH_OBJ)) !== false) {
                                $tr_table = tr_table($obj->cd_agenda, $obj->nm_agenda, ($cd_especialidade !== 0?"Todas":""), $sn_ativo);
                                
                                $registro['registro'][0]['agenda']        = $obj->cd_agenda;
                                $registro['registro'][0]['nome']          = $obj->nm_agenda;
                                $registro['registro'][0]['observacao']    = $ds_observacoes;
                                $registro['registro'][0]['especialidade'] = ($cd_especialidade !== 0?"Todas":"");
                                $registro['registro'][0]['tr_table']      = $tr_table;
                            }
                            
                            $pdo->commit();

                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        } else {
                            $pdo->beginTransaction();

                            $stm = $pdo->prepare(
                                  "Delete from dbo.tbl_agenda "
                                . "where (st_agenda = 0)      "
                                . "  and (cd_configuracao = :cd_agenda)");

                            $stm->execute(array(
                                  ':cd_agenda' => $cd_agenda
                            ));

                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_configurar_agenda Set   "
                                . "    nm_agenda        = :nm_agenda      "
                                . "  , ds_observacoes   = :ds_observacoes "
                                . "  , cd_especialidade = " . ($cd_especialidade !== 0?$cd_especialidade:"NULL") . "  "
                                . "  , cd_profissional  = " . ($cd_profissional  !== 0?$cd_profissional:"NULL")  . "  "
                                . "  , dt_inicial       = convert(date, '{$dt_inicial}', 103) "
                                . "  , dt_final         = convert(date, '{$dt_final}', 103)   "
                                . "  , hr_divisao_agenda = :hr_divisao_agenda "
                                . "  , sn_ativo          = :sn_ativo "
                                  // Dias da Semana  
                                . "  , sn_domingo        = :sn_domingo "
                                . "  , sn_segunda        = :sn_segunda "
                                . "  , sn_terca          = :sn_terca   "
                                . "  , sn_quarta         = :sn_quarta  "
                                . "  , sn_quinta         = :sn_quinta  "
                                . "  , sn_sexta          = :sn_sexta   "
                                . "  , sn_sabado         = :sn_sabado  "
                                  // Domingo
                                . "  , hr_dom_ini_manha	= ${hr_dom_ini_manha} "
                                . "  , hr_dom_fim_manha	= ${hr_dom_fim_manha} "
                                . "  , hr_dom_ini_tarde	= ${hr_dom_ini_tarde} "
                                . "  , hr_dom_fim_tarde	= ${hr_dom_fim_tarde} "
                                  // Segunda
                                . "  , hr_seg_ini_manha	= ${hr_seg_ini_manha} "
                                . "  , hr_seg_fim_manha	= ${hr_seg_fim_manha} "
                                . "  , hr_seg_ini_tarde	= ${hr_seg_ini_tarde} "
                                . "  , hr_seg_fim_tarde	= ${hr_seg_fim_tarde} "
                                  // Terça
                                . "  , hr_ter_ini_manha	= ${hr_ter_ini_manha} "
                                . "  , hr_ter_fim_manha	= ${hr_ter_fim_manha} "
                                . "  , hr_ter_ini_tarde	= ${hr_ter_ini_tarde} "
                                . "  , hr_ter_fim_tarde	= ${hr_ter_fim_tarde} "
                                  // Quarta
                                . "  , hr_qua_ini_manha	= ${hr_qua_ini_manha} "
                                . "  , hr_qua_fim_manha	= ${hr_qua_fim_manha} "
                                . "  , hr_qua_ini_tarde	= ${hr_qua_ini_tarde} "
                                . "  , hr_qua_fim_tarde	= ${hr_qua_fim_tarde} "
                                  // Quinta
                                . "  , hr_qui_ini_manha	= ${hr_qui_ini_manha} "
                                . "  , hr_qui_fim_manha	= ${hr_qui_fim_manha} "
                                . "  , hr_qui_ini_tarde	= ${hr_qui_ini_tarde} "
                                . "  , hr_qui_fim_tarde	= ${hr_qui_fim_tarde} "
                                  // Sexta
                                . "  , hr_sex_ini_manha	= ${hr_sex_ini_manha} "
                                . "  , hr_sex_fim_manha	= ${hr_sex_fim_manha} "
                                . "  , hr_sex_ini_tarde	= ${hr_sex_ini_tarde} "
                                . "  , hr_sex_fim_tarde	= ${hr_sex_fim_tarde} "
                                  // Sábado
                                . "  , hr_sab_ini_manha	= ${hr_sab_ini_manha} "
                                . "  , hr_sab_fim_manha	= ${hr_sab_fim_manha} "
                                . "  , hr_sab_ini_tarde	= ${hr_sab_ini_tarde} "
                                . "  , hr_sab_fim_tarde	= ${hr_sab_fim_tarde} "
                                . "where cd_agenda = :cd_agenda");

                            $stm->execute(array(
                                  ':cd_agenda'         => $cd_agenda
                                , ':nm_agenda'         => $nm_agenda
                                , ':ds_observacoes'    => $ds_observacoes
                                , ':hr_divisao_agenda' => $hr_divisao_agenda
                                , ':sn_ativo'          => $sn_ativo
                                  // Dias da Semana  
                                , ':sn_domingo' => $sn_domingo
                                , ':sn_segunda' => $sn_segunda
                                , ':sn_terca'   => $sn_terca
                                , ':sn_quarta'  => $sn_quarta
                                , ':sn_quinta'  => $sn_quinta
                                , ':sn_sexta'   => $sn_sexta
                                , ':sn_sabado'  => $sn_sabado
                            ));
                            
                            $registro = array('registro' => array());

                            $tr_table = tr_table($cd_agenda, $nm_agenda, ($cd_especialidade !== 0?"Todas":""), $sn_ativo);

                            $registro['registro'][0]['agenda']        = $cd_agenda;
                            $registro['registro'][0]['nome']          = $nm_agenda;
                            $registro['registro'][0]['observacao']    = $ds_observacoes;
                            $registro['registro'][0]['especialidade'] = ($cd_especialidade !== 0?"Todas":"");
                            $registro['registro'][0]['tr_table']      = $tr_table;
                            
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
            
                case 'excluir_configuracao' : {
                    try {
                        $cd_agenda = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['configuracao'])));
                        
                        $sql = 
                              "Select  "
                            . "    c.* "
                            . "  , coalesce((                  "
                            . "      Select count(x.cd_agenda) "
                            . "	  from dbo.tbl_agenda x        "
                            . "	  where (x.cd_configuracao = c.cd_agenda) "
                            . "	    and (x.st_agenda > 0) "
                            . "    ), 0) as qt_agendamentos "
                            . "from dbo.tbl_configurar_agenda c "
                            . "where (c.cd_agenda = {$cd_agenda})";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            if ((int)$obj->qt_agendamentos > 0) {
                                echo "Este registro de configuração de agenda possui associações e não poderá ser excluído.";
                            } else {
                                $pdo->beginTransaction();
                                
                                $stm = $pdo->prepare(
                                      "Delete from dbo.tbl_agenda "
                                    . "where (st_agenda = 0)      "
                                    . "  and (cd_configuracao = :cd_agenda)");

                                $stm->execute(array(
                                      ':cd_agenda' => $cd_agenda
                                ));

                                $stm = $pdo->prepare(
                                      "Delete from dbo.tbl_configurar_agenda "
                                    . "where cd_agenda = :cd_agenda");

                                $stm->execute(array(
                                      ':cd_agenda' => $cd_agenda
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