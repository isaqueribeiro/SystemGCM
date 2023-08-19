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
    require_once '../dao/dao_get.php';
    
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

    function tr_table($id_agenda, $ds_horario, $st_agenda, $cd_paciente, $nm_paciente, $ds_idade, $ds_atendimento, $ds_especialidade, $cd_atendimento, $dt_atendimento) {
        //$referencia = (int)$cd_agenda;
        $referencia = substr($id_agenda, 1, strlen($id_agenda) - 2);
        $legenda    = "";
        $situacao   = "Horário sem atendimento agendado.";
        
        switch ($st_agenda) {
            case '1' : { // Agendado
                $legenda .= " text-bold bg-yellow";
                $situacao = "O paciente <strong>ainda não confirmou o agendamento</strong>.";
            } break;
            case '2' : { // Confirmado
                $legenda .= " text-bold bg-green";
                $situacao = "O atendimento selecionado já foi <strong>confirmado</strong>.";
            } break;
            case '3' : { // Atendido
                $legenda .= " text-bold bg-primary";
                $situacao = "O atendimento selecionado já foi <strong>finalizado</strong>.";
            } break;
            case '4' : { // Cancelado
                $legenda .= " text-bold bg-red";
                $situacao = "O atendimento selecionado está <strong>cancelado</strong>.";
            } break;
            case '9' : { // Bloqueado
                $legenda .= " text-bold bg-lime-active";
                $situacao = "O horário selecionado está <strong>bloqueado</strong>.";
                
                $nm_paciente = "... BLOQUEADO";
                $nr_fone     = "BLOQUEADO";
                $ds_atendimento   = "BLOQUEADO";
                $ds_especialidade = "BLOQUEADO";
            } break;
        }

        $style   = "padding-left: 1px; padding-right: 1px; padding-top: 1px; padding-bottom: 1px; ";
        //$horario = "<button type='button' class='btn{$legenda}' onclick='abrir_cadastro(this, null)' style='width: 100%;'>{$ds_horario}</button>";
        $menu_opcoes = 
              "<div class='input-group-btn'>"
            . "  <input type='hidden' id='st_agenda_{$referencia}'      value='{$st_agenda}'>"
            . "  <input type='hidden' id='ds_situacao_{$referencia}'    value='{$situacao}'>"
            . "  <input type='hidden' id='tg_legenda_{$referencia}'     value='" . trim($legenda) . "'>"
            . "  <input type='hidden' id='cd_atendimento_{$referencia}' value='{$cd_atendimento}'>"
            . "  <input type='hidden' id='dt_atendimento_{$referencia}' value='{$dt_atendimento}'>"
            . "  <input type='hidden' id='cd_paciente_{$referencia}'    value='{$cd_paciente}'>"
            . "  <button type='button' class='btn{$legenda}' id='btn_abrir_cadastro_{$referencia}' onclick='abrir_cadastro(this, null)'>{$ds_horario}</button>"
            . "  <button type='button' class='btn{$legenda} dropdown-toggle' data-toggle='dropdown'>"
            . "     <span class='fa fa-navicon'></span>"    // fa-navicon fa-caret-down
            . "  </button>"    
            . "  <ul class='dropdown-menu'>"    
            . "     <li><a href='javascript:preventDefault();' onclick='iniciar_atendimento(this)'><span class='fa fa-heartbeat'></span>Iniciar Atendimento</a></li>"    
            . "     <li><a href='javascript:preventDefault();' onclick='encerrar_atendimento(this)'><span class='fa fa-calendar-check-o'></span>Encerrar atendimento</a></li>"    
//            . "     <li><a href='javascript:preventDefault();' onclick='reagendar_atendimento(this)'><span class='fa fa-calendar-minus-o'></span>Reagendar</a></li>"    
            . "     <li class='divider'></li>"    
            . "     <li><a href='javascript:preventDefault();' onclick='imprimir_prescricao(this)'><span class='fa fa-print'></span>Imprimir Prescrição</a></li>"    // <a href="invoice-print.html" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
//            . "     <li class='divider'></li>"    
//            . "     <li><a href='javascript:preventDefault();' onclick='cancelar_atendimento(this)'><span class='fa fa-calendar-times-o'></span>Cancelar</a></li>"    
//            . "     <li><a href='javascript:preventDefault();' onclick='bloquear_atendimento(this)'><span class='fa fa-unlock-alt'></span>Bloquear Horário</a></li>"    
//            . "     <li><a href='javascript:preventDefault();' onclick='excluir_registro(this)'><span class='fa fa-trash'></span>Excluir</a></li>"    
            . "  </ul>"    
            . "</div>\n";
        
        $retorno =
              "    <tr id='tr-linha_{$referencia}'>             \n"
            . "      <td style='{$style}'>{$menu_opcoes}</td>   \n"
            . "      <td>{$cd_paciente}</td>        \n"
            . "      <td>{$nm_paciente}</td>        \n"
            . "      <td>{$ds_idade}</td>           \n"
            . "      <td>{$ds_atendimento}</td>     \n"
            . "      <td>{$ds_especialidade}</td>   \n"
            . "    </tr>  \n";
            
        return $retorno;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'pesquisar_atendimentos_hoje' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $dt_hoje    = strip_tags( strtoupper(trim($_POST['dt_hoje'])) );
                        $tp_filtro  = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tp_filtro'])));
                        $cd_profissional = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['cd_profissional'])));
                        $qt_registro     = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/atendimento_hoje_' . $cookieID . '.json';
                        if (file_exists($file_cookie)) {
                            unlink($file_cookie);
                        }
                        
                        $registros = array('filtro' => array());
                        $registros['filtro'][0]['qt_registro']     = $qt_registro;
                        $registros['filtro'][0]['cd_profissional'] = $cd_profissional;
                        $registros['filtro'][0]['tp_filtro']       = $tp_filtro;
                        $registros['filtro'][0]['dt_hoje']         = $dt_hoje;
                        
                        $json = json_encode($registros);
                        file_put_contents($file_cookie, $json);
                        // Gravar as configurações do filtro utilizado pelo usuário -- (FINAL)
                        
                        $retorno = 
                              "<table id='tb-atendimentos_hoje' class='table table-bordered table-hover table-striped'> \n"
                            . "  <thead>                    \n"
                            . "    <tr>                     \n"
                            . "      <th>Horário</th>       \n" // A legenda está junto com o horário <span>
                            . "      <th>Prontuário</th>    \n"
                            . "      <th>Paciente</th>      \n"
                            . "      <th>Idade</th>         \n"
                            . "      <th>Tipo</th>          \n" // Tipo do atendimento
                            . "      <th>Especialidade</th> \n" 
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query("Exec dbo.getAgendaAtendimentos {$tp_filtro}, N'{$dt_hoje}', N'{$id_empresa}'");
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $ds_horario  = substr($obj->hora_agenda, 0, 5);
                            $cd_paciente = (isset($obj->cd_paciente)?str_pad($obj->cd_paciente, 7, "0", STR_PAD_LEFT):"");
                            $ds_idade    = calcular_idade($obj->dt_nasc, $obj->dt_hoje);
                            $retorno    .= tr_table($obj->id_agenda, $ds_horario, $obj->st_agenda, $cd_paciente, $obj->paciente, $ds_idade, $obj->ds_atendimento, $obj->ds_especialidade, $obj->codigo_atendimento, $obj->data_atendimento);
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
            
                case 'inserir_agendamento_avulso' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $dt_agenda   = strip_tags( strtoupper(trim($_POST['data'])) );
                        $hr_agenda   = strip_tags( strtoupper(trim($_POST['hora'])) );
                        $st_agenda   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['situacao'])));
                        $cd_profissional = preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['profissional'])));
                        $cd_convenio = preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['convenio'])));
                        $cd_tabela   = preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['tabela'])));
                        $cd_paciente = preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['paciente'])));
                        $observacoes = strip_tags( trim($_POST['observacoes']) );

                        $file = '../../logs/json/atendimento_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $pdo = Conexao::getConnection();
                        
                        // Identificar profissinal pelo usuário
                        $sql = 
                              "Select  "
                            . "    p.cd_profissional "
                            . "from dbo.tbl_profissional p "
                            . "where (p.id_empresa = '{$id_empresa}') "
                            . "  and (p.id_usuario = '{$user->getCodigo()}') "; 

                        $qry = $pdo->query($sql);
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $cd_profissional = $obj->cd_profissional;
                        }
                        
                        unset($qry);
                        
                        if ((int)$cd_profissional === 0) {
                            echo "Usuário sem permissão para executar esta rotina no sistema";
                        } else {
                            $tr_table = "";
                            $registro = array('registro' => array());

                            $sql = 
                                  "exec dbo.setAgendamentoAvulso    \n"
                                . "  N'{$id_empresa}'               \n"
                                . ", N'{$dt_agenda}'                \n"
                                . ", N'{$hr_agenda}'                \n"
                                . ", N'{$user->getCodigo()}'        \n"
                                . ", {$cd_profissional}             \n"
                                . ", {$cd_convenio}                 \n"
                                . ", {$cd_tabela}                   \n"
                                . ", {$cd_paciente}                 \n"
                                . ", {$st_agenda}                   \n"
                                . ", N'{$observacoes}'              \n";
                            //var_dump($sql);    
                            $pdo->beginTransaction();
                            $qry = $pdo->query($sql);
                            $qry->nextRowset();
                            if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {                            //var_dump($obj);
                                $ds_horario  = substr($obj->hora_agenda, 0, 5);
                                $cd_paciente = (isset($obj->cd_paciente)?str_pad($obj->cd_paciente, 7, "0", STR_PAD_LEFT):"");
                                $ds_idade    = calcular_idade($obj->dt_nasc, $obj->dt_hoje);
                                $tr_table    = tr_table($obj->id_agenda, $ds_horario, $obj->st_agenda, $cd_paciente, $obj->paciente, $ds_idade, $obj->tipo, $obj->especialidade, "0", $obj->data_agenda);

                                $legenda  = "";
                                $situacao = "Horário sem atendimento agendado.";

                                switch ($st_agenda) {
                                    case 1 : { // Agendado
                                        $legenda .= " text-bold bg-yellow";
                                        $situacao = "O paciente <strong>ainda não confirmou o agendamento</strong>.";
                                    } break;
                                    case 2 : { // Confirmado
                                        $legenda .= " text-bold bg-green";
                                        $situacao = "O atendimento selecionado já foi <strong>confirmado</strong>.";
                                    } break;
                                    case 3 : { // Atendido
                                        $legenda .= " text-bold bg-primary";
                                        $situacao = "O atendimento selecionado já foi <strong>finalizado</strong>.";
                                    } break;
                                    case 4 : { // Cancelado
                                        $legenda .= " text-bold bg-red";
                                        $situacao = "O atendimento selecionado está <strong>cancelado</strong>.";
                                    } break;
                                    case 9 : { // Bloqueado
                                        $legenda .= " text-bold bg-lime-active";
                                        $situacao = "O horário selecionado está <strong>bloqueado</strong>.";
                                    } break;
                                }

                                $registro['registro'][0]['id']            = $obj->id_agenda;
                                $registro['registro'][0]['codigo']        = $obj->cd_atendimento;
                                $registro['registro'][0]['referencia']    = substr($obj->id_agenda, 1, strlen($obj->id_agenda) - 2);;
                                $registro['registro'][0]['situacao']      = $st_agenda;
                                $registro['registro'][0]['tag_legenda']   = $legenda;
                                $registro['registro'][0]['tag_situacao']  = $situacao;
                                $registro['registro'][0]['codigo_atendimento'] = $obj->cd_atendimento;
                                $registro['registro'][0]['data_atendimento']   = $obj->data_atendimento;
                                $registro['registro'][0]['hora_atendimento']   = $obj->hora_atendimento; 
                                $registro['registro'][0]['status']     = $obj->sn_avulso;
                                $registro['registro'][0]['historia']   = $obj->ds_historia;
                                $registro['registro'][0]['prescricao'] = $obj->ds_prescricao;
                                $registro['registro'][0]['avulso']     = "1";
                                $registro['registro'][0]['tr_table']   = $tr_table;
                            } else {
                                $registro['registro'][0]['avulso'] = "1";
                            }

                            $json = json_encode($registro);
                            file_put_contents($file, $json);

                            $pdo->commit();

                            // Fechar conexão PDO
                            unset($qry);
                            unset($pdo);

                            echo "OK";
                        }
                    } catch (Exception $ex) {
                        echo $ex . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                    } 
                } break;
            
                case 'historico_clinico' : {
                    try {
                        $id_empresa     = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_atendimento = strip_tags( strtoupper(trim($_POST['atendimento'])) );
                        $dt_atendimento = strip_tags( strtoupper(trim($_POST['data'])) );
                        $cd_paciente    = preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['paciente'])));
                        
                        $retorno = 
                              "<table id='tb-historicos' class='table table-bordered table-striped table-hover' style='width: 100%; table-layout: fixed;'> \n" //  table-layout: fixed;
                            . "  <thead>                        \n"
                            . "    <tr>                         \n"
                            . "      <th>História Clínica</th>  \n"
                            . "      <th>Prescrição</th>        \n" 
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $pdo = Conexao::getConnection();
                        $sql = 
                              "Select               "
                            . "    a.id_atendimento "
                            . "  , a.cd_atendimento "
                            . "  , convert(varchar(12), a.dt_atendimento, 103) as dt_atendimento "
                            . "  , convert(varchar(12), a.hr_atendimento, 108) as hr_atendimento "
                            . "  , a.cd_paciente "
                            . "  , p.nm_paciente "
                            . "  , convert(varchar(12), p.dt_nascimento, 103) as dt_nascimento          "
                            . "  , left(convert(varchar(12), p.dt_nascimento, 120), 10)     as dt_nasc  "
                            . "  , left(convert(varchar(12), getdate(), 120), 10)           as dt_hoje  "
                            . "  , a.cd_profissional "
                            . "  , coalesce(nullif(trim(m.nm_apresentacao), ''), m.nm_profissional) as nm_profissional "
                            . "  , m.ds_conselho        "
                            . "  , a.cd_especialidade   "
                            . "  , e.ds_especialidade   "
                            . "  , a.ds_historia        "
                            . "  , a.ds_prescricao      "
                            . "  , a.id_empresa         "
                            . "from dbo.tbl_atendimento a "
                            . "  inner join dbo.tbl_paciente p on (p.cd_paciente = a.cd_paciente) "
                            . "  left join dbo.tbl_profissional m on (m.cd_profissional = a.cd_profissional) "
                            . "  left join dbo.tbl_especialidade e on (e.cd_especialidade = a.cd_especialidade) "
                            . "where (a.id_empresa  = '{$id_empresa}') "
                            . "  and (a.cd_paciente = {$cd_paciente}) "
                            . "  and (a.dt_atendimento  < convert(date, '{$dt_atendimento}', 103)) "
                            . "  and (a.id_atendimento <> '{$id_atendimento}') "
                            . "order by "
                            . "	   a.dt_atendimento DESC "
                            . "  , a.hr_atendimento DESC ";
                                
                        $qry = $pdo->query($sql);
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $hr_atendi = str_replace(":", "h", substr($obj->hr_atendimento, 0, 5));
                            $historia  = str_replace(chr(32), "&nbsp;", str_replace(chr(10), "<br>", $obj->ds_historia));
                            $assinatura = "<strong>" . $obj->nm_profissional . "<br>" . $obj->ds_conselho . "</strong>";
                            $prescricao = str_replace(chr(32), "&nbsp;", str_replace(chr(10), "<br>", $obj->ds_prescricao));
                            
                            $empresa     = "'{$obj->id_empresa}'"; 
                            $atendimento = "'{$obj->id_atendimento}'"; 
                            $prontuario  = "'{$obj->cd_paciente}'"; 
                            $onclick = "onclick=imprimir_prescricao_historico(" . $empresa . "," . $atendimento . "," . $prontuario . ")";
                            
                            $retorno .= 
                                "    <tr>  \n"
                              . "      <td style='word-wrap:break-word;>"
                              . "        <font face='courier new'>"
                              . "          <span class='badge'><strong>{$obj->dt_atendimento} às {$hr_atendi}</strong></span><br>"
                              . "          <p class='text-uppercase no-shadow' style='margin-top: 1px;'>{$historia}"
                              . "          </p>{$assinatura}"
                              . "        </font>"
                              . "      </td>"
                              . "      <td style='word-wrap:break-word;>"
                              . "        <font face='courier new'>"
                              . "          <a href='javascript:preventDefault();'><span class='badge' {$onclick}><strong>Imprimir</strong></span></a><br>"
                              . "          <p class='text-uppercase no-shadow' style='margin-top: 1px;'>{$prescricao}"
                              . "          </p>"
                              . "        </font>"
                              . "      </td>"
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
            
                case 'carregar_atendimento' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_agenda  = "{" . strip_tags( strtoupper(trim($_POST['codigo'])) ) . "}";
                        
                        $file = '../../logs/json/atendimento_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    a.* "
                            . "  , convert(varchar(12), a.dt_agenda, 103) as data_agenda  "
                            . "  , convert(varchar(8),  a.hr_agenda, 108) as hora_agenda  "
                            . "  , coalesce(p.nm_paciente, a.nm_paciente, '...') as paciente    "
                            . "  , convert(varchar(12), p.dt_nascimento, 103)    as nascimento  "     // Formato DD/MM/YYYY
                            . "  , left(convert(varchar(12), p.dt_nascimento, 120), 10) as dt_nasc  " // Formato YYYY-MM-DD
                            . "  , left(convert(varchar(12), getdate(), 120), 10)       as dt_hoje  " // Formato YYYY-MM-DD
                            . "  , coalesce(nullif(a.nr_celular, ''), nullif(a.nr_telefone, ''), nullif(p.nr_celular, ''), nullif(p.nr_telefone, ''), '...') as contato "
                            . "  , p.ds_contatos     "
                            . "  , p.nm_acompanhante "
                            . "  , p.nm_indicacao    "
                            . "  , p.ds_profissao    "
                            . "  , p.end_logradouro  "
                            . "  , p.end_bairro      "
                            . "  , p.end_cidade      "
                            . "  , p.end_estado      "
                            . "  , p.ds_profissao    "
                            . "  , p.ds_alergias    as paciente_alergias    "
                            . "  , p.ds_observacoes as paciente_observacoes "
                            . "  , t.ds_tipo as ds_atendimento  "
                            . "  , s.ds_situacao                "
                            . "  , coalesce(e.ds_especialidade, '...') as ds_especialidade  "
                            . "  , coalesce(m.nm_profissional,  '...') as nm_profissional   "
                            . "  , coalesce(a.vl_servico, 0.0)         as valor_servico     "
                            . "  , coalesce(a.cd_paciente, 0)          as prontuario        "
                            . "  , convert(varchar(12), p.dh_cadastro, 103) as dt_cadastro  " // Formato DD/MM/YYYY
                            . "  "
                            . "  , at.* "
                            . "  , coalesce(a.cd_convenio, at.cd_convenio, 0)           as convenio      "
                            . "  , coalesce(a.cd_especialidade, at.cd_especialidade, 0) as especialidade "
                            . "  , coalesce(a.cd_profissional, at.cd_profissional, 0)   as medido        "
                            . "  , coalesce(at.cd_atendimento, 0)                                    as codigo_atendimento  "
                            . "  , convert(varchar(12), coalesce(at.dt_atendimento, getdate()), 103) as data_atendimento  "
                            . "  , convert(varchar(8),  coalesce(at.hr_atendimento, getdate()), 108) as hora_atendimento  "
                            . "from dbo.tbl_agenda a  "
                            . "  inner join dbo.vw_situacao_agenda s on (s.cd_situacao = a.st_agenda)    "
                            . "  inner join dbo.vw_tipo_atendimento t on (t.cd_tipo = a.tp_atendimento)  "
                            . "  left join dbo.tbl_atendimento at on (at.id_atendimento = a.id_atendimento)  "
                            . "  left join dbo.tbl_paciente p on (p.cd_paciente = coalesce(at.cd_paciente, a.cd_paciente)) "
                            . "  left join dbo.tbl_especialidade e on (e.cd_especialidade = coalesce(at.cd_especialidade, a.cd_especialidade)) "
                            . "  left join dbo.tbl_profissional m on (m.cd_profissional = coalesce(at.cd_profissional, a.cd_profissional))   "
                            . "where (a.id_agenda  = '{$id_agenda}')  "
                            . "  and (a.id_empresa = '{$id_empresa}') "; 
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $ds_idade        = calcular_idade($obj->dt_nasc, $obj->dt_hoje);
                            $nm_acompanhante = (isset($obj->nm_acompanhante)?(trim($obj->nm_acompanhante) !== ""?$obj->nm_acompanhante:"..."):"...");
                            $nm_indicacao    = (isset($obj->nm_indicacao)?(trim($obj->nm_indicacao) !== ""?$obj->nm_indicacao:"..."):"...");
                            $ds_email        = (isset($obj->ds_email)?(trim($obj->ds_email) !== ""?$obj->ds_email:"..."):"...");
                            $ds_endereco     = $obj->end_logradouro . " - " . $obj->end_bairro . " - " . $obj->end_cidade . "/" . $obj->end_estado;
                            
                            $nr_contatos     = (isset($obj->nr_celular)?(trim($obj->nr_celular) !== ""?$obj->nr_celular:"..."):"...");
                            $nr_contatos    .= (isset($obj->nr_telefone)?(trim($obj->nr_telefone) !== ""?", " . $obj->nr_telefone:""):"");
                            $nr_contatos    .= (isset($obj->ds_contatos)?(trim($obj->ds_contatos) !== ""?", " . $obj->ds_contatos:""):"");
                            
                            $registro['registro'][0]['id']            = $obj->id_agenda;
                            $registro['registro'][0]['data']          = $obj->data_agenda;
                            $registro['registro'][0]['hora']          = substr($obj->hora_agenda, 0, 5);
                            $registro['registro'][0]['referencia']    = substr($obj->id_agenda, 1, strlen($obj->id_agenda) - 2);
                            $registro['registro'][0]['codigo']        = $obj->cd_agenda;
                            $registro['registro'][0]['prontuario']    = $obj->prontuario; // str_pad($obj->prontuario, 7, "0", STR_PAD_LEFT)
                            $registro['registro'][0]['paciente']      = $obj->paciente;
                            $registro['registro'][0]['nascimento']    = $obj->nascimento;
                            $registro['registro'][0]['idade']         = $ds_idade;
                            $registro['registro'][0]['acompanhante']  = $nm_acompanhante;
                            $registro['registro'][0]['indicacao']     = $nm_indicacao;
                            $registro['registro'][0]['celular']       = $obj->nr_celular;
                            $registro['registro'][0]['telefone']      = $obj->nr_telefone;
                            $registro['registro'][0]['contatos']      = $nr_contatos;
                            $registro['registro'][0]['email']         = $ds_email;
                            $registro['registro'][0]['endereco']      = $ds_endereco;
                            $registro['registro'][0]['profissao']     = $obj->ds_profissao;
                            $registro['registro'][0]['atendimento']   = $obj->tp_atendimento;
                            $registro['registro'][0]['convenio']      = $obj->convenio;
                            $registro['registro'][0]['especialidade'] = $obj->especialidade;
                            $registro['registro'][0]['profissional']  = $obj->medido;
                            $registro['registro'][0]['tabela']        = $obj->cd_tabela;
                            $registro['registro'][0]['servico']       = $obj->cd_servico;
                            $registro['registro'][0]['valor']         = number_format($obj->valor_servico, 2, ",", ".");
                            $registro['registro'][0]['situacao']      = $obj->st_agenda;
                            $registro['registro'][0]['observacao']    = $obj->ds_observacao;
                            $registro['registro'][0]['avulso']        = $obj->sn_avulso;
                            $registro['registro'][0]['descricao_situacao']   = $obj->ds_situacao;
                            $registro['registro'][0]['descricao_servico']    = $obj->ds_atendimento . ' ' . $obj->ds_especialidade;
                            $registro['registro'][0]['paciente_alergias']    = $obj->paciente_alergias;
                            $registro['registro'][0]['paciente_observacoes'] = $obj->paciente_observacoes;
                            $registro['registro'][0]['cadastro']             = $obj->dt_cadastro;
                            
                            $registro['registro'][0]['codigo_atendimento'] = $obj->cd_atendimento;
                            $registro['registro'][0]['data_atendimento']   = $obj->data_atendimento;
                            $registro['registro'][0]['hora_atendimento']   = $obj->hora_atendimento; //substr($obj->hora_atendimento, 0, 5);
                            $registro['registro'][0]['status']     = $obj->st_atendimento;
                            $registro['registro'][0]['historia']   = $obj->ds_historia;
                            $registro['registro'][0]['prescricao'] = $obj->ds_prescricao;
                        } else {
                            $registro['registro'][0]['avulso'] = "1";
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
            
                case 'salvar_atendimento' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $referencia  = strip_tags( strtoupper(trim($_POST['agenda'])) );
                        $id_agenda   = strip_tags( strtoupper(trim($_POST['id_agenda'])) );
                        $st_agenda   = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['st_agenda']));
                        $dt_agenda   = strip_tags( strtoupper(trim($_POST['dt_agenda'])) );
                        $hr_agenda   = strip_tags( strtoupper(trim($_POST['hr_agenda'])) );
                        $cd_paciente = (float)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_paciente']));
                        $cd_convenio      = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_convenio']));
                        $cd_especialidade = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_especialidade']));
                        $cd_profissional  = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_profissional']));
                        $ds_alergias      = strip_tags( trim($_POST['ds_alergias']) );
                        $ds_observacoes   = strip_tags( trim($_POST['ds_observacoes']) );
                        $sn_avulso        = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['sn_avulso']));
                        
                        $id_atendimento   = strip_tags( trim($_POST['id_atendimento']) );
                        $cd_atendimento   = (float)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_atendimento']));
                        $dt_atendimento   = strip_tags( trim($_POST['dt_atendimento']) );
                        $hr_atendimento   = strip_tags( trim($_POST['hr_atendimento']) );
                        $st_atendimento   = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['st_atendimento']));
                        $ds_historia      = strip_tags( trim($_POST['ds_historia']) );
                        $ds_prescricao    = strip_tags( trim($_POST['ds_prescricao']) );
                        
                        $file = '../../logs/json/atendimento_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    a.* "
                            . "from dbo.tbl_atendimento a "
                            . "where (a.id_atendimento = '{$id_agenda}')";   // O ID do atendimento será o mesmo do agendamento                 
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        //echo "  , " . $cd_convenio . "<br>";
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.tbl_atendimento ("
                                . "    id_atendimento	 "
                                . "  , dt_atendimento	 "
                                . "  , hr_atendimento	 "
                                . "  , st_atendimento	 "
                                . "  , cd_paciente	 "
                                . "  , cd_convenio	 "
                                . "  , cd_especialidade	 "
                                . "  , cd_profissional	 "
                                . "  , ds_historia	 "
                                . "  , ds_prescricao	 "
                                . "  , id_empresa	 "
                                . ") "
                                . "    OUTPUT                  "
                                . "    INSERTED.id_atendimento "
                                . "  , INSERTED.cd_atendimento "
                                . "  , convert(varchar(12), INSERTED.dt_atendimento) as data_atendimento  "
                                . "  , convert(varchar(8),  INSERTED.hr_atendimento) as hora_atendimento  "
                                . "  , INSERTED.cd_paciente    "
                                . "values (               "
                                . "    :id_atendimento    "
                                . "  , " . ($dt_atendimento !== ""?"convert(date, '{$dt_atendimento}', 103) ":"NULL")
                                . "  , " . ($hr_atendimento !== ""?"convert(time, '{$hr_atendimento}', 108) ":"NULL")
                                . "  , :st_atendimento      "
                                . "  , :cd_paciente         "
                                . "  , :cd_convenio         "
                                . "  , :cd_especialidade    "
                                . "  , :cd_profissional     "
                                . "  , :ds_historia         "
                                . "  , :ds_prescricao       "
                                . "  , :id_empresa          "
                                . ")");                        

                            $stm->execute(array(
                                  ':id_atendimento'   => $id_agenda
                                , ':st_atendimento'   => "0"
                                , ':cd_paciente'      => $cd_paciente
                                , ':cd_convenio'      => $cd_convenio
                                , ':cd_especialidade' => $cd_especialidade
                                , ':cd_profissional'  => $cd_profissional
                                , ':ds_historia'      => $ds_historia
                                , ':ds_prescricao'    => $ds_prescricao
                                , ':id_empresa'       => $id_empresa
                            ));
                            
                            $registro = array('registro' => array());

                            if (($obj = $stm->fetch(PDO::FETCH_OBJ)) !== false) {
                                $ds_horario  = substr($hr_agenda, 0, 5);
                                $cd_paciente = str_pad($cd_paciente, 7, "0", STR_PAD_LEFT);
                                $nm_paciente = "";
                                $ds_atendimento   = "";
                                $ds_especialidade = "";
                                $ds_idade = ""; //calcular_idade($obj->dt_nasc, $obj->dt_hoje);
                                $tr_table = tr_table($id_agenda, $ds_horario, $st_agenda, $cd_paciente, $nm_paciente, $ds_idade, $ds_atendimento, $ds_especialidade, $obj->cd_atendimento, $dt_atendimento);

                                $legenda  = "";
                                $situacao = "Horário sem atendimento agendado.";
                                
                                switch ($st_agenda) {
                                    case 1 : { // Agendado
                                        $legenda .= " text-bold bg-yellow";
                                        $situacao = "O paciente <strong>ainda não confirmou o agendamento</strong>.";
                                    } break;
                                    case 2 : { // Confirmado
                                        $legenda .= " text-bold bg-green";
                                        $situacao = "O atendimento selecionado já foi <strong>confirmado</strong>.";
                                    } break;
                                    case 3 : { // Atendido
                                        $legenda .= " text-bold bg-primary";
                                        $situacao = "O atendimento selecionado já foi <strong>finalizado</strong>.";
                                    } break;
                                    case 4 : { // Cancelado
                                        $legenda .= " text-bold bg-red";
                                        $situacao = "O atendimento selecionado está <strong>cancelado</strong>.";
                                    } break;
                                    case 9 : { // Bloqueado
                                        $legenda .= " text-bold bg-lime-active";
                                        $situacao = "O horário selecionado está <strong>bloqueado</strong>.";
                                    } break;
                                }
                                
                                $registro['registro'][0]['id']            = $obj->id_atendimento;
                                $registro['registro'][0]['codigo']        = $obj->cd_atendimento;
                                $registro['registro'][0]['referencia']    = $referencia;
                                $registro['registro'][0]['situacao']      = $st_agenda;
                                $registro['registro'][0]['tag_legenda']   = $legenda;
                                $registro['registro'][0]['tag_situacao']  = $situacao;
                                $registro['registro'][0]['codigo_atendimento'] = $obj->cd_atendimento;
                                $registro['registro'][0]['data_atendimento']   = $obj->data_atendimento;
                                $registro['registro'][0]['hora_atendimento']   = $obj->hora_atendimento; //substr($obj->hora_atendimento, 0, 5);
                                $registro['registro'][0]['status']     = "0";
                                $registro['registro'][0]['historia']   = $ds_historia;
                                $registro['registro'][0]['prescricao'] = $ds_prescricao;
                                $registro['registro'][0]['tr_table']   = $tr_table;
                            }
                            
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_agenda Set "
                                . "    id_atendimento = :id_atendimento  "
                                . "where id_agenda    = :id_agenda  "
                                . "  and id_empresa   = :id_empresa "); 

                            $stm->execute(array(
                                  ':id_atendimento' => $id_agenda
                                , ':id_agenda'      => $id_agenda
                                , ':id_empresa'     => $id_empresa
                            ));
                            
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_paciente Set          "
                                . "    ds_alergias    = :ds_alergias    "
                                . "  , ds_observacoes = :ds_observacoes "
                                . "where cd_paciente  = :cd_paciente    "); 

                            $stm->execute(array(
                                  ':cd_paciente'    => $cd_paciente
                                , ':ds_alergias'    => $ds_alergias
                                , ':ds_observacoes' => $ds_observacoes
                            ));
                            
                            $pdo->commit();

                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        } else {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_atendimento Set           "
                                . "    cd_paciente	= :cd_paciente      "
                                . "  , cd_convenio	= :cd_convenio      "
                                . "  , cd_especialidade	= :cd_especialidade "
                                . "  , cd_profissional	= :cd_profissional  "
                                . "  , ds_historia	= :ds_historia      "
                                . "  , ds_prescricao	= :ds_prescricao    "
                                . "  , dh_atualizacao	= getdate()         "
                                . "  , us_atualizacao	= :us_atualizacao   "
                                . "  , st_atendimento	= :st_atendimento   "
                                . "where id_atendimento = :id_atendimento   "
                                . "  and id_empresa     = :id_empresa       "); 

                            $stm->execute(array(
                                  ':id_atendimento'   => $id_atendimento
                                , ':id_empresa'       => $id_empresa
                                , ':cd_paciente'      => $cd_paciente
                                , ':cd_convenio'      => $cd_convenio
                                , ':cd_especialidade' => $cd_especialidade
                                , ':cd_profissional'  => $cd_profissional
                                , ':ds_historia'      => $ds_historia
                                , ':ds_prescricao'    => $ds_prescricao
                                , ':us_atualizacao'   => $user->getCodigo()
                                , ':st_atendimento'   => $st_atendimento
                            ));
                            
                            $registro = array('registro' => array());

                            $ds_horario  = substr($hr_agenda, 0, 5);
                            $cd_paciente = str_pad($cd_paciente, 7, "0", STR_PAD_LEFT);
                            $nm_paciente = "";
                            $ds_atendimento   = "";
                            $ds_especialidade = "";
                            $ds_idade = ""; //calcular_idade($obj->dt_nasc, $obj->dt_hoje);
                            $tr_table = tr_table($id_agenda, $ds_horario, $st_agenda, $cd_paciente, $nm_paciente, $ds_idade, $ds_atendimento, $ds_especialidade, $cd_atendimento, $dt_atendimento);

                            $legenda  = "";
                            $situacao = "Horário sem atendimento agendado.";

                            switch ($st_agenda) {
                                case 1 : { // Agendado
                                    $legenda .= " text-bold bg-yellow";
                                    $situacao = "O paciente <strong>ainda não confirmou o agendamento</strong>.";
                                } break;
                                case 2 : { // Confirmado
                                    $legenda .= " text-bold bg-green";
                                    $situacao = "O atendimento selecionado já foi <strong>confirmado</strong>.";
                                } break;
                                case 3 : { // Atendido
                                    $legenda .= " text-bold bg-primary";
                                    $situacao = "O atendimento selecionado já foi <strong>finalizado</strong>.";
                                } break;
                                case 4 : { // Cancelado
                                    $legenda .= " text-bold bg-red";
                                    $situacao = "O atendimento selecionado está <strong>cancelado</strong>.";
                                } break;
                                case 9 : { // Bloqueado
                                    $legenda .= " text-bold bg-lime-active";
                                    $situacao = "O horário selecionado está <strong>bloqueado</strong>.";
                                } break;
                            }
                            
                            $registro['registro'][0]['id']            = $id_atendimento;
                            $registro['registro'][0]['codigo']        = $cd_atendimento;
                            $registro['registro'][0]['referencia']    = $referencia;
                            $registro['registro'][0]['situacao']      = $st_agenda;
                            $registro['registro'][0]['tag_legenda']   = $legenda;
                            $registro['registro'][0]['tag_situacao']  = $situacao;
                            $registro['registro'][0]['codigo_atendimento'] = $cd_atendimento;
                            $registro['registro'][0]['data_atendimento']   = $dt_atendimento;
                            $registro['registro'][0]['hora_atendimento']   = $hr_atendimento; //substr($obj->hora_atendimento, 0, 5);
                            $registro['registro'][0]['status']     = $st_atendimento;
                            $registro['registro'][0]['historia']   = $ds_historia;
                            $registro['registro'][0]['prescricao'] = $ds_prescricao;
                            $registro['registro'][0]['tr_table']   = $tr_table;
                            
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_agenda Set "
                                . "    id_atendimento = :id_atendimento  "
                                . "where id_agenda    = :id_agenda  "
                                . "  and id_empresa   = :id_empresa "); 

                            $stm->execute(array(
                                  ':id_atendimento' => $id_atendimento
                                , ':id_agenda'      => $id_agenda
                                , ':id_empresa'     => $id_empresa
                            ));
                            
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_paciente Set          "
                                . "    ds_alergias    = :ds_alergias    "
                                . "  , ds_observacoes = :ds_observacoes "
                                . "where cd_paciente  = :cd_paciente    "); 

                            $stm->execute(array(
                                  ':cd_paciente'    => $cd_paciente
                                , ':ds_alergias'    => $ds_alergias
                                , ':ds_observacoes' => $ds_observacoes
                            ));
                            
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
                
                case 'carregar_controle_exames' : {
                    try {
                        $id_empresa      = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $referencia      = strip_tags( strtoupper(trim($_POST['agenda'])) );
                        $id_atendimento  = strip_tags( trim($_POST['id_atendimento']) );
                        $dt_atendimento  = strip_tags( trim($_POST['dt_atendimento']) );
                        $sn_todos_exames = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['sn_todos_exames']));
                        $cd_paciente     = (float)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_paciente']));

                        $pdo = Conexao::getConnection();
                        
//                        // Gerar Tabela de Exames vazia para o novo paciente
//                        $data = $dt_atendimento;
//                        $pdo->beginTransaction();
//                        $sql = 
//                              "exec dbo.spGerarListaExamesPaciente \n"
//                            . "  N'{$id_empresa}' \n"
//                            . ", {$cd_paciente} \n"
//                            . ", N'{$data}'  \n"
//                            . ", N'{$user->getCodigo()}'";
//
//                        $qry = $pdo->query($sql);
//                        $pdo->commit();
//                        
//                        $sql = 
//                              "Select distinct  "
//                            . "    p.id_exame   "
//                            . "  , e.cd_exame   "
//                            . "  , e.nm_exame   "
//                            . "  , e.un_exame   "
//                            . "from dbo.tbl_exame_paciente p    "
//                            . "  inner join dbo.tbl_exame e on (e.id_exame = p.id_exame and e.id_empresa = '{$id_empresa}')   "
//                            . "where (p.cd_paciente = {$cd_paciente})                           "
//                            //. "  and (p.dt_exame   <= convert(date, '{$dt_atendimento}', 103))  "
//                            . ($sn_todos_exames === 0?"  and (p.dt_exame   <= convert(date, '{$dt_atendimento}', 103))  ":"")
//                            . "order by         "
//                            . "    e.cd_exame   "; 
                        
                        $sql = 
                              "Select distinct  "
                            . "    p.id_exame   "
                            . "  , e.cd_exame   "
                            . "  , e.nm_exame   "
                            . "  , e.un_exame   "
                            . "from dbo.tbl_exame e "
                            . "  left join dbo.tbl_exame_paciente p on (p.cd_paciente = {$cd_paciente} and p.id_exame = e.id_exame) "
                            . "where (e.id_empresa = '{$id_empresa}') "
                            . ($sn_todos_exames === 0?"  and (p.dt_exame   <= convert(date, '{$dt_atendimento}', 103))  ":"")
                            . "order by         "
                            . "    e.cd_exame   "; 
                        
                        $qry = $pdo->query($sql);
                        $exames = $qry->fetchAll(PDO::FETCH_ASSOC);
                        
                        $sql = 
                              "Select "
                            . "  convert(varchar(12), x.dt_exame, 103) as dt_exame "
                            . "from (           "
                            . "	Select distinct "
                            . "	  convert(date, convert(varchar(12), dat.dt_exame, 103), 103) as dt_exame "
                            . "	from ( "
                            . "	  Select getdate() as dt_exame "
                            . "	  union     "
                            . "	  Select    "
                            . "		p.dt_exame "
                            . "	  from dbo.tbl_exame_paciente p "
                            . "   where (p.cd_paciente = {$cd_paciente})                           "
                            . ($sn_todos_exames === 0?"     and (p.dt_exame   <= convert(date, '{$dt_atendimento}', 103))  ":"")
                            . "	) dat "
                            . ") x "
                            . "order by  "
                            . "  x.dt_exame DESC ";
                            
                        $qry = $pdo->query($sql);
                        $datas = $qry->fetchAll(PDO::FETCH_ASSOC);
                        
                        $inputmask  = "'alias': 'dd/mm/yyyy'";
                        $retorno    = "<p style='font-size: 3px;'>&nbsp;</p>"
                            . "<input type='hidden' id='qt_controle_exames' value='" . count($exames) . "'> "
                            . "<table id='tb-controle_exames' class='table table-bordered table-striped table-hover'> "
                            . "  <thead> "
                            . "    <tr> "
                            . "      <th>#</th> " 
                            . "      <th>Exame</th> ";

                        // Apenas os 7 últimos resultados de cada exame (x.dt_exame DESC)
                        $limite = (count($datas) <= 7?0:count($datas) - 7);
                        
                        // Montando os cabeçalhos da tabela com as datas
                        for ($i = (count($datas) - 1); $i >= $limite; $i--) {
                            $dat = $datas[$i];
                            if ($i === $limite) {
                                $input_data = '<input type="text" class="form-control proximo_campo" data-inputmask="' .$inputmask . '" data-mask id="dt_exame" value="' . $dat['dt_exame'] . '">';
                                $retorno .= 
                                  "      <th class='text-center no-padding' colspan='2' style='width: 15%;'> "
                                . "        <div class='input-group'> "
                                . "          <div class='input-group-addon'> "
                                . "            <i class='fa fa-calendar'></i> "
                                . "          </div> "
                                . "          {$input_data} "
                                . "        </div> "
                                . "      </th> ";
                                
                            } else {
                                $retorno .= "      <th class='text-center' style='width: 8%;'>{$dat['dt_exame']}</th> ";
                            }
                        }
                                        
                        $retorno .= 
                              "    </tr> "
                            . "  </thead> "
                            . "  <tbody> ";
                        
                        $idx_exame = 0;
                        
                        foreach($exames as $exm) {
                            $ref   = substr($exm['id_exame'], 1, strlen($exm['id_exame']) - 2);
                            $input = "<input type='hidden' id='ref_controle_exame_{$idx_exame}' value='{$exm['id_exame']}'>";
                            $retorno .= 
                                  "<tr id='reg-linha_exame_{$ref}'>"
                                . "  <td>" . str_pad($exm['cd_exame'], 2, "0", STR_PAD_LEFT) . "{$input}</td>"
                                . "  <td>{$exm['nm_exame']}</td>";

                            // Recuperar os valores dos exames de acordo com a data    
                            for ($i = (count($datas) - 1); $i >= $limite; $i--) {
                                $dat = $datas[$i];
                                $sql = "exec dbo.getExamePaciente N'{$id_empresa}', {$cd_paciente}, N'{$exm['id_exame']}', N'{$dat['dt_exame']}'"; 
                                $qry = $pdo->query($sql);
                                if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                                    if ($i === $limite) {
                                        $retorno .= 
                                            "  <td class='text-right no-padding'>"
                                          . "    <input type='text' class='form-control text-right proximo_campo' maxlength='10' id='vl_exame_texto_{$idx_exame}' value='{$obj->vl_exame_texto}' style='width: 100%;'>"
                                          . "  </td>";
                                    } else {
                                        $retorno .= "  <td class='text-right'>{$obj->vl_exame_texto}</td>";
                                    }
                                } else {
                                    if ($i === $limite) {
                                        $retorno .= 
                                            "  <td class='text-right no-padding'>"
                                          . "    <input type='text' class='form-control text-right proximo_campo' maxlength='10' id='vl_exame_texto_{$idx_exame}' value='' style='width: 100%;'>"
                                          . "  </td>";
                                    } else {
                                        $retorno .= "  <td class='text-right'>&nbsp;</td>";
                                    }
                                }
                            }
                            
                            $retorno .= 
                                  "  <td>{$exm['un_exame']}</td>"
                                . "</tr>";
                                  
                            $idx_exame += 1;      
                        }
                        
                        $retorno .=
                              "  </tbody> \n"
                            . "</table>   \n";
                        
                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                        
                        echo $retorno;
                    } catch (Exception $ex) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        echo $ex . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                    } 
                } break;
                
                case 'inserir_exame_atendimento' : {
                    try {
                        $id_empresa     = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $referencia     = strip_tags( strtoupper(trim($_POST['agenda'])) );
                        $id_atendimento = strip_tags( trim($_POST['id_atendimento']) );
                        $cd_paciente    = (float)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_paciente']));
                        $id_exame       = strip_tags( trim($_POST['id_exame']) );
                        $dt_exame       = strip_tags( trim($_POST['dt_atendimento']) );

                        $sql = 
                              "Select  "
                            . "    a.id_atendimento "
                            . "  , convert(varchar(12), a.dt_atendimento, 103) as dt_atendimento "
                            . "from dbo.tbl_atendimento a "
                            . "where (a.id_atendimento = '{$id_atendimento}')"; 
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $id_atendimento = "'{$id_atendimento}'";
                        } else {
                            $id_atendimento = "NULL";
                        }
                        
                        if ($dt_exame === "") {
                            $dt_exame = date('d/m/Y');
                        }
                        
                        $sql = 
                              "Select  "
                            . "    e.* "
                            . "from dbo.tbl_exame_paciente e   "
                            . "where (e.cd_paciente = {$cd_paciente})"
                            . "  and (e.id_exame    = '{$id_exame}')"
                            . "  and (e.dt_exame    = convert(date, '{$dt_exame}', 103))";
                        
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.tbl_exame_paciente ("
                                . "    id_lancamento    "
                                . "  , cd_paciente      "
                                . "  , id_exame         "
                                . "  , dt_exame         "
                                . "  , vl_exame         "
                                . "  , vl_exame_texto   "
                                . "  , id_atendimento   "
                                . "  , dh_insercao      "
                                . "  , us_insercao      "
                                . ") "
                                . "    OUTPUT           "
                                . "    INSERTED.id_lancamento "
                                . "  , INSERTED.cd_paciente   "
                                . "  , INSERTED.id_exame  "
                                . "  , INSERTED.dt_exame  "
                                . "values (               "
                                . "    dbo.ufnGetGuidID() "
                                . "  , :cd_paciente      "
                                . "  , :id_exame         "
                                . "  , convert(date, '{$dt_exame}', 103) "
                                . "  , :vl_exame         "
                                . "  , :vl_exame_texto   "
                                . "  , {$id_atendimento} "
                                . "  , getdate()         "
                                . "  , :us_insercao      "
                                . ")");

                            $stm->execute(array(
                                  ':cd_paciente'    => $cd_paciente
                                , ':id_exame'       => $id_exame
                                , ':vl_exame'       => null
                                , ':vl_exame_texto' => null
                                , ':us_insercao'    => $user->getCodigo()
                            ));
                            $pdo->commit();
                            
                            echo "OK";
                        } else {
                            echo "EXIST";
                        }
                        
                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                    } catch (Exception $ex) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        echo $ex . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                    } 
                } break;
                
                case 'salvar_resultados_exames' : {
                    try {
                        $id_empresa     = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $referencia     = strip_tags( strtoupper(trim($_POST['referencia'])) );
                        $id_atendimento = strip_tags( trim($_POST['id_atendimento']) );
                        $dt_atendimento = strip_tags( trim($_POST['dt_atendimento']) );
                        $cd_paciente    = (float)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_paciente']));
                        $dt_exame       = strip_tags( trim($_POST['dt_exame']) );
                        $exames         = explode("||", strip_tags( trim($_POST['exames'])  ));
                        $valores        = explode("||", strip_tags( trim($_POST['valores']) ));

                        $sql = 
                              "Select  "
                            . "    a.id_atendimento "
                            . "  , convert(varchar(12), a.dt_atendimento, 103) as dt_atendimento "
                            . "from dbo.tbl_atendimento a "
                            . "where (a.id_atendimento = '{$id_atendimento}')"; 
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $id_atendimento = "'{$id_atendimento}'";
                        } else {
                            $id_atendimento = "NULL";
                        }
                        
                        for ($i = 0; $i < count($exames); $i++) {
                            $vl_exame = str_replace(",", ".", str_replace(".", "", $valores[$i]));
                            $vl_exame = (floatval('0' . $vl_exame) === 0.0?"NULL":$vl_exame);
                            
                            $pdo->beginTransaction();
                            
                            $excluir = 
                                "Delete  "
                              . "from dbo.tbl_exame_paciente "
                              . "where (cd_paciente = :cd_paciente)"
                              . "  and (id_exame    = :id_exame)   "
                              . "  and (dt_exame    = convert(date, '{$dt_exame}', 103))";

                            $stm = $pdo->prepare($excluir);
                            $stm->execute(array(
                                  ':cd_paciente'    => $cd_paciente
                                , ':id_exame'       => $exames[$i]
                            ));
                              
                            //if (($vl_exame !== "NULL") && ($id_atendimento !== "NULL")) {
                            if ($vl_exame !== "NULL") {
                                $inserir = 
                                    "Insert Into dbo.tbl_exame_paciente ("
                                  . "    id_lancamento      "
                                  . "  , cd_paciente        "
                                  . "  , id_exame           "
                                  . "  , dt_exame           "
                                  . "  , vl_exame           "
                                  . "  , vl_exame_texto     "
                                  . "  , id_atendimento     "
                                  . "  , dh_insercao        "
                                  . "  , us_insercao        "
                                  . ") values (             "
                                  . "    dbo.ufnGetGuidID() "
                                  . "  , :cd_paciente       "
                                  . "  , :id_exame          "
                                  . "  , convert(date, '{$dt_exame}', 103) "
                                  . "  , {$vl_exame}        "
                                  . "  , :vl_exame_texto    "
                                  . "  , {$id_atendimento}  "
                                  . "  , getdate()          "
                                  . "  , :us_insercao       "
                                  . ")";

                                $stm = $pdo->prepare($inserir);
                                $stm->execute(array(
                                      ':cd_paciente'    => $cd_paciente
                                    , ':id_exame'       => $exames[$i]
                                    , ':vl_exame_texto' => $valores[$i]
                                    , ':us_insercao'    => $user->getCodigo()
                                ));
                            }
                            
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
                
                case 'carregar_controle_evolucoes' : {
                    try {
                        $id_empresa     = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $referencia     = strip_tags( strtoupper(trim($_POST['agenda'])) );
                        $id_atendimento = strip_tags( trim($_POST['id_atendimento']) );
                        $dt_atendimento = strip_tags( trim($_POST['dt_atendimento']) );
                        $sn_todas_medidas = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['sn_todas_medidas']));
                        $cd_paciente      = (float)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_paciente']));

                        $pdo = Conexao::getConnection();
                        
//                        // Gerar Tabela de Exames vazia para o novo paciente
//                        $data = $dt_atendimento;
//                        $pdo->beginTransaction();
//                        $sql = 
//                              "exec dbo.spGerarListaEvolucaoMedidaPaciente \n"
//                            . "  N'{$id_empresa}' \n"
//                            . ", {$cd_paciente} \n"
//                            . ", N'{$data}'  \n"
//                            . ", N'{$user->getCodigo()}'";
//
//                        $qry = $pdo->query($sql);
//                        $pdo->commit();
//                        
//                        $sql = 
//                              "Select distinct  "
//                            . "    p.id_evolucao   "
//                            . "  , e.cd_evolucao   "
//                            . "  , e.ds_evolucao   "
//                            . "  , e.un_evolucao   "
//                            . "from dbo.tbl_evolucao_medida_pac p    "
//                            . "  inner join dbo.tbl_evolucao e on (e.id_evolucao = p.id_evolucao and e.id_empresa = '{$id_empresa}')   "
//                            . "where (p.cd_paciente = {$cd_paciente})                           "
//                            //. "  and (p.dt_evolucao   <= convert(date, '{$dt_atendimento}', 103))  "
//                            . ($sn_todas_medidas === 0?"  and (p.dt_evolucao   <= convert(date, '{$dt_atendimento}', 103))  ":"")
//                            . "order by         "
//                            . "    e.cd_evolucao";
                        $sql = 
                              "Select distinct  "
                            . "    p.id_evolucao   "
                            . "  , e.cd_evolucao   "
                            . "  , e.ds_evolucao   "
                            . "  , e.un_evolucao   "
                            . "from dbo.tbl_evolucao e "
                            . "  left join dbo.tbl_evolucao_medida_pac p on (p.cd_paciente = {$cd_paciente} and p.id_evolucao = e.id_evolucao)  "
                            . "where (e.id_empresa = '{$id_empresa}') "
                            . ($sn_todas_medidas === 0?"  and (p.dt_evolucao   <= convert(date, '{$dt_atendimento}', 103))  ":"")
                            . "order by         "
                            . "    e.cd_evolucao";
                        
                        
                        $qry = $pdo->query($sql);
                        $evolucoes = $qry->fetchAll(PDO::FETCH_ASSOC);
                        
                        $sql = 
                              "Select "
                            . "  convert(varchar(12), x.dt_evolucao, 103) as dt_evolucao "
                            . "from (           "
                            . "	Select distinct "
                            . "	  convert(date, convert(varchar(12), dat.dt_evolucao, 103), 103) as dt_evolucao "
                            . "	from ( "
                            . "	  Select getdate() as dt_evolucao "
                            . "	  union     "
                            . "	  Select    "
                            . "		p.dt_evolucao "
                            . "	  from dbo.tbl_evolucao_medida_pac p "
                            . "   where (p.cd_paciente = {$cd_paciente})                           "
                            . ($sn_todas_medidas === 0?"     and (p.dt_evolucao   <= convert(date, '{$dt_atendimento}', 103))  ":"")
                            . "	) dat "
                            . ") x "
                            . "order by  "
                            . "  x.dt_evolucao DESC ";
                            
                        $qry = $pdo->query($sql);
                        $datas = $qry->fetchAll(PDO::FETCH_ASSOC);
                        
                        $inputmask  = "'alias': 'dd/mm/yyyy'";
                        $retorno    = "<p style='font-size: 3px;'>&nbsp;</p>"
                            . "<input type='hidden' id='qt_controle_evolucoes' value='" . count($evolucoes) . "'> "
                            . "<table id='tb-controle_evolucoes' class='table table-bordered table-striped table-hover'> "
                            . "  <thead> "
                            . "    <tr> "
                            . "      <th>#</th> " 
                            . "      <th>Evolução</th> ";

                        // Apenas os 7 últimos resultados de cada evolucao (x.dt_evolucao DESC)
                        $limite = (count($datas) <= 7?0:count($datas) - 7);
                        
                        // Montando os cabeçalhos da tabela com as datas
                        for ($i = (count($datas) - 1); $i >= $limite; $i--) {
                            $dat = $datas[$i];
                            if ($i === $limite) {
                                $input_data = '<input type="text" class="form-control proximo_campo" data-inputmask="' .$inputmask . '" data-mask id="dt_evolucao" value="' . $dat['dt_evolucao'] . '">';
                                $retorno .= 
                                  "      <th class='text-center no-padding' colspan='2' style='width: 15%;'> "
                                . "        <div class='input-group'> "
                                . "          <div class='input-group-addon'> "
                                . "            <i class='fa fa-calendar'></i> "
                                . "          </div> "
                                . "          {$input_data} "
                                . "        </div> "
                                . "      </th> ";
                                
                            } else {
                                $retorno .= "      <th class='text-center' style='width: 8%;'>{$dat['dt_evolucao']}</th> ";
                            }
                        }
                                        
                        $retorno .= 
                              "    </tr> "
                            . "  </thead> "
                            . "  <tbody> ";
                        
                        $idx_evolucao = 0;
                        
                        foreach($evolucoes as $exm) {
                            $ref   = substr($exm['id_evolucao'], 1, strlen($exm['id_evolucao']) - 2);
                            $input = "<input type='hidden' id='ref_controle_evolucao_{$idx_evolucao}' value='{$exm['id_evolucao']}'>";
                            $retorno .= 
                                  "<tr id='reg-linha_evolucao_{$ref}'>"
                                . "  <td>" . str_pad($exm['cd_evolucao'], 2, "0", STR_PAD_LEFT) . "{$input}</td>"
                                . "  <td>{$exm['ds_evolucao']}</td>";

                            // Recuperar os valores dos evolucaos de acordo com a data    
                            for ($i = (count($datas) - 1); $i >= $limite; $i--) {
                                $dat = $datas[$i];
                                $sql = "exec dbo.getevolucaoPaciente N'{$id_empresa}', {$cd_paciente}, N'{$exm['id_evolucao']}', N'{$dat['dt_evolucao']}'"; 
                                $qry = $pdo->query($sql);
                                if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                                    if ($i === $limite) {
                                        $retorno .= 
                                            "  <td class='text-right no-padding'>"
                                          . "    <input type='text' class='form-control text-right proximo_campo' maxlength='10' id='vl_evolucao_texto_{$idx_evolucao}' value='{$obj->vl_evolucao_texto}' style='width: 100%;'>"
                                          . "  </td>";
                                    } else {
                                        $retorno .= "  <td class='text-right'>{$obj->vl_evolucao_texto}</td>";
                                    }
                                } else {
                                    if ($i === $limite) {
                                        $retorno .= 
                                            "  <td class='text-right no-padding'>"
                                          . "    <input type='text' class='form-control text-right proximo_campo' maxlength='10' id='vl_evolucao_texto_{$idx_evolucao}' value='' style='width: 100%;'>"
                                          . "  </td>";
                                    } else {
                                        $retorno .= "  <td class='text-right'>&nbsp;</td>";
                                    }
                                }
                            }
                            
                            $retorno .= 
                                  "  <td>{$exm['un_evolucao']}</td>"
                                . "</tr>";
                                  
                            $idx_evolucao += 1;      
                        }
                        
                        $retorno .=
                              "  </tbody> \n"
                            . "</table>   \n";
                        
                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                        
                        echo $retorno;
                    } catch (Exception $ex) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        echo $ex . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                    } 
                } break;
                
                case 'inserir_evolucao_atendimento' : {
                    try {
                        $id_empresa     = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $referencia     = strip_tags( strtoupper(trim($_POST['agenda'])) );
                        $id_atendimento = strip_tags( trim($_POST['id_atendimento']) );
                        $cd_paciente    = (float)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_paciente']));
                        $id_evolucao    = strip_tags( trim($_POST['id_evolucao']) );
                        $dt_evolucao    = strip_tags( trim($_POST['dt_atendimento']) );

                        $sql = 
                              "Select  "
                            . "    a.id_atendimento "
                            . "  , convert(varchar(12), a.dt_atendimento, 103) as dt_atendimento "
                            . "from dbo.tbl_atendimento a "
                            . "where (a.id_atendimento = '{$id_atendimento}')"; 
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $id_atendimento = "'{$id_atendimento}'";
                        } else {
                            $id_atendimento = "NULL";
                        }
                        
                        if ($dt_evolucao === "") {
                            $dt_evolucao = date('d/m/Y');
                        }
                        
                        $sql = 
                              "Select  "
                            . "    e.* "
                            . "from dbo.tbl_evolucao_medida_pac e   "
                            . "where (e.cd_paciente = {$cd_paciente})"
                            . "  and (e.id_evolucao = '{$id_evolucao}')"
                            . "  and (e.dt_evolucao = convert(date, '{$dt_evolucao}', 103))";
                        
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.tbl_evolucao_medida_pac ("
                                . "    id_lancamento    "
                                . "  , cd_paciente      "
                                . "  , id_evolucao         "
                                . "  , dt_evolucao         "
                                . "  , vl_evolucao         "
                                . "  , vl_evolucao_texto   "
                                . "  , id_atendimento   "
                                . "  , dh_insercao      "
                                . "  , us_insercao      "
                                . ") "
                                . "    OUTPUT           "
                                . "    INSERTED.id_lancamento "
                                . "  , INSERTED.cd_paciente   "
                                . "  , INSERTED.id_evolucao  "
                                . "  , INSERTED.dt_evolucao  "
                                . "values (               "
                                . "    dbo.ufnGetGuidID() "
                                . "  , :cd_paciente      "
                                . "  , :id_evolucao         "
                                . "  , convert(date, '{$dt_evolucao}', 103) "
                                . "  , :vl_evolucao         "
                                . "  , :vl_evolucao_texto   "
                                . "  , {$id_atendimento} "
                                . "  , getdate()         "
                                . "  , :us_insercao      "
                                . ")");

                            $stm->execute(array(
                                  ':cd_paciente'    => $cd_paciente
                                , ':id_evolucao'       => $id_evolucao
                                , ':vl_evolucao'       => null
                                , ':vl_evolucao_texto' => null
                                , ':us_insercao'    => $user->getCodigo()
                            ));
                            $pdo->commit();
                            
                            echo "OK";
                        } else {
                            echo "EXIST";
                        }
                        
                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                    } catch (Exception $ex) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        echo $ex . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                    } 
                } break;
                
                case 'salvar_resultados_evolucoes' : {
                    try {
                        $id_empresa     = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $referencia     = strip_tags( strtoupper(trim($_POST['referencia'])) );
                        $id_atendimento = strip_tags( trim($_POST['id_atendimento']) );
                        $dt_atendimento = strip_tags( trim($_POST['dt_atendimento']) );
                        $cd_paciente    = (float)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_paciente']));
                        $dt_evolucao    = strip_tags( trim($_POST['dt_evolucao']) );
                        $evolucoes      = explode("||", strip_tags( trim($_POST['evolucoes'])  ));
                        $valores        = explode("||", strip_tags( trim($_POST['valores']) ));

                        $sql = 
                              "Select  "
                            . "    a.id_atendimento "
                            . "  , convert(varchar(12), a.dt_atendimento, 103) as dt_atendimento "
                            . "from dbo.tbl_atendimento a "
                            . "where (a.id_atendimento = '{$id_atendimento}')"; 
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $id_atendimento = "'{$id_atendimento}'";
                        } else {
                            $id_atendimento = "NULL";
                        }
                        
                        for ($i = 0; $i < count($evolucoes); $i++) {
                            $vl_evolucao = str_replace(",", ".", str_replace(".", "", $valores[$i]));
                            $vl_evolucao = (floatval('0' . $vl_evolucao) === 0.0?"NULL":$vl_evolucao);
                            
                            $pdo->beginTransaction();
                            
                            $excluir = 
                                "Delete  "
                              . "from dbo.tbl_evolucao_medida_pac "
                              . "where (cd_paciente = :cd_paciente)"
                              . "  and (id_evolucao    = :id_evolucao)   "
                              . "  and (dt_evolucao    = convert(date, '{$dt_evolucao}', 103))";

                            $stm = $pdo->prepare($excluir);
                            $stm->execute(array(
                                  ':cd_paciente'    => $cd_paciente
                                , ':id_evolucao'       => $evolucoes[$i]
                            ));
                              
                            //if (($vl_evolucao !== "NULL") && ($id_atendimento !== "NULL")) {
                            if ($vl_evolucao !== "NULL") {
                                $inserir = 
                                    "Insert Into dbo.tbl_evolucao_medida_pac ("
                                  . "    id_lancamento      "
                                  . "  , cd_paciente        "
                                  . "  , id_evolucao           "
                                  . "  , dt_evolucao           "
                                  . "  , vl_evolucao           "
                                  . "  , vl_evolucao_texto     "
                                  . "  , id_atendimento     "
                                  . "  , dh_insercao        "
                                  . "  , us_insercao        "
                                  . ") values (             "
                                  . "    dbo.ufnGetGuidID() "
                                  . "  , :cd_paciente       "
                                  . "  , :id_evolucao          "
                                  . "  , convert(date, '{$dt_evolucao}', 103) "
                                  . "  , {$vl_evolucao}        "
                                  . "  , :vl_evolucao_texto    "
                                  . "  , {$id_atendimento}  "
                                  . "  , getdate()          "
                                  . "  , :us_insercao       "
                                  . ")";

                                $stm = $pdo->prepare($inserir);
                                $stm->execute(array(
                                      ':cd_paciente'    => $cd_paciente
                                    , ':id_evolucao'       => $evolucoes[$i]
                                    , ':vl_evolucao_texto' => $valores[$i]
                                    , ':us_insercao'    => $user->getCodigo()
                                ));
                            }
                            
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
                
                case 'encerrar_atendimento' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_agenda  = '{' . strip_tags( strtoupper(trim($_POST['id_agenda'])) ) . '}';
                        $st_agenda  = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['st_agenda']));
                        
                        $sql = 
                              "Select  "
                            . "    a.* "
                            . "from dbo.tbl_agenda a "
                            . "where (a.id_agenda = '{$id_agenda}')";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $pdo->beginTransaction();
                            
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_agenda Set "
                                . "    st_agenda    = :st_agenda  "
                                . "where id_agenda  = :id_agenda  "
                                . "  and id_empresa = :id_empresa "); 

                            $stm->execute(array(
                                  ':id_agenda'  => $id_agenda
                                , ':id_empresa' => $id_empresa
                                , ':st_agenda'  => $st_agenda
                            ));
                            
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_atendimento Set "
                                . "    st_atendimento   = 1       "
                                . "  , us_finalizacao   = :us_finalizacao "
                                . "  , dh_finalizacao   = getdate()       "
                                . "where id_atendimento = :id_atendimento "
                                . "  and id_empresa     = :id_empresa "); 

                            $stm->execute(array(
                                  ':id_atendimento' => $id_agenda
                                , ':id_empresa'     => $id_empresa
                                , ':us_finalizacao' => $user->getCodigo()
                            ));
                            
                            $pdo->commit();

                            // Fechar conexão PDO
                            unset($stm);
                            unset($pdo);

                            echo "OK";
                        } else {
                            echo "Atendimento não localizado!";
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