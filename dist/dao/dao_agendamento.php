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

    function tr_table($id_agenda, $cd_agenda, $ds_horario, $st_agenda, $nm_paciente, $nr_fone, $ds_atendimento, $ds_especialidade) {
        //$referencia = (int)$cd_agenda;
        $referencia = substr($id_agenda, 1, strlen($id_agenda) - 2);
        $legenda    = "";
        $situacao   = "Horário sem atendimento agendado.";
        
        switch ($st_agenda) {
            case '1' : { // Agendado
                $legenda .= " text-bold bg-yellow";
                $situacao = "O atendimento já está <strong>agendado</strong>.";
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
        $horario = "<button type='button' class='btn{$legenda}' onclick='abrir_cadastro(this, null)' style='width: 100%;'>{$ds_horario}</button>";
        $menu_opcoes = 
              "<div class='input-group-btn'>"
            . "  <input type='hidden' id='st_agenda_{$referencia}'   value='{$st_agenda}'>"
            . "  <input type='hidden' id='ds_situacao_{$referencia}' value='{$situacao}'>"
            . "  <button type='button' class='btn{$legenda}' onclick='abrir_cadastro(this)'>{$ds_horario}</button>"
            . "  <button type='button' class='btn{$legenda} dropdown-toggle' data-toggle='dropdown'>"
            . "     <span class='fa fa-navicon'></span>"    // fa-navicon fa-caret-down
            . "  </button>"    
            . "  <ul class='dropdown-menu'>"    
            . "     <li><a href='javascript:preventDefault();' onclick='iniciar_agendamento(this)'><span class='fa fa-calendar-plus-o'></span>Agendar</a></li>"    
            . "     <li><a href='javascript:preventDefault();' onclick='confirmar_agendamento(this)'><span class='fa fa-calendar-check-o'></span>Confirmar</a></li>"    
            . "     <li><a href='javascript:preventDefault();' onclick='marcar_agend_atendido(this)'><span class='fa fa-check'></span>Marcar como atendido</a></li>"    
            . "     <li><a href='javascript:preventDefault();' onclick='reagendar_atendimento(this)'><span class='fa fa-calendar-minus-o'></span>Reagendar</a></li>"    
            . "     <li class='divider'></li>"    
            . "     <li><a href='javascript:preventDefault();' onclick='imprimir_atendimento(this)'><span class='fa fa-print'></span>Imprimir Fatura</a></li>"    // <a href="invoice-print.html" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
            . "     <li class='divider'></li>"    
            . "     <li><a href='javascript:preventDefault();' onclick='cancelar_atendimento(this)'><span class='fa fa-calendar-times-o'></span>Cancelar</a></li>"    
            . "     <li><a href='javascript:preventDefault();' onclick='bloquear_atendimento(this)'><span class='fa fa-unlock-alt'></span>Bloquear Horário</a></li>"    
            . "     <li><a href='javascript:preventDefault();' onclick='excluir_registro(this)'><span class='fa fa-trash'></span>Excluir</a></li>"    
            . "  </ul>"    
            . "</div>\n";
        
        $retorno =
              "    <tr id='tr-linha_{$referencia}'>             \n"
            . "      <td style='{$style}'>{$menu_opcoes}</td>   \n"
            . "      <td>{$nm_paciente}</td>        \n"
            . "      <td>{$nr_fone}</td>            \n"
            . "      <td>{$ds_atendimento}</td>     \n"
            . "      <td>{$ds_especialidade}</td>   \n"
            . "    </tr>  \n";
            
        return $retorno;
    }

    
    function get_numero_dias($mes, $ano) {
	$numero_dias = array( 
              '01' => 31
            , '02' => 28
            , '03' => 31
            , '04' => 30
            , '05' => 31
            , '06' => 30
            , '07' => 31
            , '08' => 31
            , '09' => 30
            , '10' => 31
            , '11' => 30
            , '12' => 31
	);
 
        $nr_mes = str_pad($mes, 2, "0", STR_PAD_LEFT);
        $nr_ano = (int)$ano;
	if ( (($nr_ano % 4) == 0 and ($nr_ano % 100) !== 0) or ($nr_ano % 400) === 0 ) {
	    $numero_dias['02'] = 29; // Altera o numero de dias de Fevereiro se o ano for bissexto
	}
 
	return $numero_dias[$nr_mes];
    }
    
    function montar_tr_linha_semana() {
        //$semanas = "DSTQQSS";
        $semanas = array("D", "S", "T", "Q", "Q", "S", "S");
        $retorno = "";

        for( $i = 0; $i < 7; $i++ ) {
          $retorno .= "<td align='center' class='bg-gray-light'><strong>" . $semanas[$i] . "</strong></td>  \n";
        }
        
        return $retorno;
    }
    
    function get_nome_mes($mes) {
        $meses = array( 
              '01' => "Janeiro"
            , '02' => "Fevereiro"
            , '03' => "Março"
            , '04' => "Abril"
            , '05' => "Maio"
            , '06' => "Junho"
            , '07' => "Julho"
            , '08' => "Agosto"
            , '09' => "Setembro"
            , '10' => "Outubro"
            , '11' => "Novembro"
            , '12' => "Dezembro"
        );

        $nr_mes = str_pad($mes, 2, "0", STR_PAD_LEFT);
        if( (int)$mes >= 1 && (int)$mes <= 12) {
            return $meses[$nr_mes];
        } else {
            return "Desconhecido";
        }
    }
    
    function montar_calendario($mes, $ano, $marcadores) {
        $retorno = "";
        
        $numero_dias = get_numero_dias($mes, $ano); // Retorna o número de dias que tem o mês/ano informados
        $nome_mes    = get_nome_mes($mes);
        $diacorrente = 0;
        
        $diasemana = jddayofweek( cal_to_jd(CAL_GREGORIAN, $mes, "01", $ano), 0); // Função que descobre o dia da semana

        $retorno = 
              "<table id='tb-calendario' class='table table-bordered' style='vertical-align: middle;'> \n"
//            . "  <tr>"
//            . "     <td colspan = 7><h4>{$nome_mes}</h4></td>"
//            . "  </tr>"
            . "  <tr>"
            . montar_tr_linha_semana()	// função que mostra as semanas aqui
            . "  </tr>";
        
        for( $linha = 0; $linha < 6; $linha++ ) {
            for( $coluna = 0; $coluna < 7; $coluna++ ) {
                // (Início) Rotina par destacar as células que possuem dias com agendamentos
                $dt_agenda = str_pad($diacorrente + 1, 2, "0", STR_PAD_LEFT) . "/" . str_pad($mes, 2, "0", STR_PAD_LEFT) . "/" . $ano;
                $id_search = search($marcadores, $dt_agenda);
                if ($id_search !== -1) {
                    
                }
                // (Fim)
                
                $retorno .= "<td height = 50 style='font-size: 18px;' "; // width = 30 class='bg-gray-light' 

                if ( ($diacorrente + 1 === (date('d') - 1) && date('m') === $mes) ) {
                    $retorno .= " id = 'td-dia_atual' ";
                } else {
                    if (($diacorrente + 1) <= $numero_dias) {
                        if ( ($coluna < $diasemana) && ($linha === 0) ) {
                            $retorno .= " id = 'td-dia_{$linha}_{$coluna}' class='' ";
                        } else {
                            $retorno .= " id = 'td-dia_{$linha}_{$coluna}' ";
                            if (($coluna === 0) || ($coluna === 6)) {
                                $retorno .= " class='bg-gray' ";
                            }
                        }
                    } else {
                        $retorno .= " ";
                    }
                }

                $retorno .= " align = 'center' valign = 'center'>";

                /* TRECHO IMPORTANTE: A PARTIR DESTE TRECHO É MOSTRADO UM DIA DO CALENDÁRIO (MUITA ATENÇÃO NA HORA DA MANUTENÇÃO) */
                if( $diacorrente + 1 <= $numero_dias ) {
                    if( ($coluna < $diasemana) && ($linha === 0) ) {
                        $retorno .= " ";
                    } else {
                        $id = "id='dia_" . ($diacorrente + 1) . "_{$mes}_{$ano}'";
                        //$retorno .= "<input type='button' class='btn btn-primary' id='btn-dia" . ($diacorrente + 1) . "'  value = '" . ++$diacorrente . "' onclick='acao(this.value)' style='width: 100%;'";
                        //$retorno .= "<input type = 'button' id = 'dia_comum' name = 'dia" . ($diacorrente+1) . "'  value = '" . ++$diacorrente . "' onclick = 'acao(this.value)' style='width: 100%;'>";
                        $retorno .= "<a href = '#' {$id} onclick='montar_agendamentos(this.id, this)'>". ++$diacorrente . "</a>";
                    }
                } else {
                    break;
                }
                /* FIM DO TRECHO MUITO IMPORTANTE */

                $retorno .= "</td>";
            }   
            $retorno .= "  </tr>";
        }
        
        $retorno .= "</table>";
        
        return $retorno;
    }
    
    /*
function MostreCalendarioCompleto()
{
	    echo "<table border='0' align = 'center'>";
	    $cont = 1;
	    for( $j = 0; $j < 4; $j++ )
	    {
		  echo "<tr>";
		for( $i = 0; $i < 3; $i++ )
		{
		 
		  echo "<td>";
			MostreCalendario( ($cont < 10 ) ? "0".$cont : $cont );  
 
		        $cont++;
		  echo "</td>";
 
	 	}
		echo "</tr>";
	   }
	   echo "</table>";
}
 
    MostreCalendario('04');
    echo "<br/>";
    MostreCalendarioCompleto();
     */
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'montar_calendario' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $mes = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['mes'])));
                        $ano = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['ano'])));
                    
                        $pdo = Conexao::getConnection();
//                        $sql = 
//                              "Select "
//                            . "    day(a.dt_agenda) as nr_dia "
//                            . "  , convert(varchar(12), a.dt_agenda, 103) as dt_agenda "
//                            . "  , sum(a.st_agenda) as nr_agendamentos "
//                            . "from dbo.tbl_agenda a "
//                            . "where (a.id_empresa      = '{$id_empresa}') "
//                            . "  and year(a.dt_agenda)  = {$ano} "
//                            . "  and month(a.dt_agenda) = {$mes} "
//                            . "group by "
//                            . "    day(a.dt_agenda) "
//                            . "  , convert(varchar(12), a.dt_agenda, 103) "
//                            . "order by "
//                            . "    day(a.dt_agenda) ";
                        $sql  = "exec dbo.getAgendaQtdeAtendimento {$ano}, {$mes}, N'{$id_empresa}'";
                        $qry  = $pdo->query($sql);
                        $dias = $qry->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Fechar conexão PDO
                        unset($qry);
                        unset($pdo);
                        
                        $retorno = montar_calendario($mes, $ano, $dias);
                        
                        echo $retorno;
                    } catch (Exception $ex) {
                        echo $ex . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                    } 
                } break;
            
                case 'pesquisar_agendamentos' : {
                    try {
                        $id_empresa       = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $cd_especialidade = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['especialidade'])));
                        $cd_profissional  = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['profissional'])));
                        $tp_atendimento   = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['atendimento'])));
                        $nr_dia      = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['dia'])));
                        $nr_mes      = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['mes'])));
                        $nr_ano      = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['ano'])));
                        $ds_filtro   = ""; //strip_tags( strtoupper(trim($_POST['filtro'])) );
                        $qt_registro = (int)preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['qt_registro'])));
                        
                        if ($qt_registro === 0) {
                            $qt_registro = 10; // Quantidade padrão de registros por paginação nas tabelas
                        }

                        if ($nr_dia === 0) $nr_dia = date('d');
                        if ($nr_mes === 0) $nr_mes = date('m');
                        if ($nr_ano === 0) $nr_ano = date('Y');
                        
                        $dia = str_pad($nr_dia, 2, "0", STR_PAD_LEFT);
                        $mes = str_pad($nr_mes, 2, "0", STR_PAD_LEFT);
                        $ano = str_pad($nr_ano, 4, "0", STR_PAD_LEFT);
                                
                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $file_cookie = '../../logs/cookies/agendamento_' . $cookieID . '.json';
                        if (file_exists($file_cookie)) {
                            unlink($file_cookie);
                        }
                        
                        $registros = array('filtro' => array());
                        $registros['filtro'][0]['qt_registro']      = $qt_registro;
                        $registros['filtro'][0]['cd_especialidade'] = $cd_especialidade;
                        $registros['filtro'][0]['cd_profissional']  = $cd_profissional;
                        $registros['filtro'][0]['tp_atendimento']   = $tp_atendimento;
                        
                        $json = json_encode($registros);
                        file_put_contents($file_cookie, $json);
                        // Gravar as configurações do filtro utilizado pelo usuário -- (FINAL)
                        
                        $retorno = 
                              "<table id='tb-agendamentos' class='table table-bordered table-hover'> \n"
                            . "  <thead>                    \n"
                            . "    <tr>                     \n"
                            . "      <th>Horário</th>       \n" // A legenda está junto com o horário <span>
                            . "      <th>Paciente</th>      \n"
                            . "      <th>Contato</th>       \n"
                            . "      <th>Atendimento</th>   \n" // Tipo do atendimento
                            . "      <th>Especialidade</th> \n" 
                            //. "      <th></th>              \n" // Botão de ações
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $pdo = Conexao::getConnection();
                        $pdo->beginTransaction();
                        $qry = $pdo->query("exec dbo.setHorariosAgenda N'{$dia}/{$mes}/{$ano}', N'{$id_empresa}', {$cd_especialidade}, {$cd_profissional}");
                        $pdo->commit();

                        unset($qry);
                        
                        $filtro = "";
                        
                        if ($cd_especialidade !== 0) $filtro .= "  and (a.cd_especialidade = {$cd_especialidade})";
                        if ($cd_profissional  !== 0) $filtro .= "  and (a.cd_profissional  = {$cd_profissional})";
                        if ($tp_atendimento   !== 0) $filtro .= "  and (a.tp_atendimento   = {$tp_atendimento})";
                        
                        $sql = 
                              "Select   "
                            . "    a.*  "
                            . "  , convert(varchar(12), a.dt_agenda, 103) as data_agenda  "
                            . "  , convert(varchar(8),  a.hr_agenda, 108) as hora_agenda  "
                            . "  , coalesce(p.nm_paciente, a.nm_paciente, '...') as paciente        "
                            . "  , coalesce(nullif(a.nr_celular, ''), nullif(a.nr_telefone, ''), nullif(p.nr_celular, ''), nullif(p.nr_telefone, ''), '...') as contato "
                            . "  , t.ds_tipo as ds_atendimento  "
                            . "  , s.ds_situacao                "
                            . "  , coalesce(e.ds_especialidade, '...') as ds_especialidade  "
                            . "  , coalesce(m.nm_profissional,  '...') as nm_profissional   "
                            . "from dbo.tbl_agenda a  "
                            . "  inner join dbo.vw_situacao_agenda s on (s.cd_situacao = a.st_agenda)    "
                            . "  inner join dbo.vw_tipo_atendimento t on (t.cd_tipo = a.tp_atendimento)  "
                            . "  left join dbo.tbl_paciente p on (p.cd_paciente = a.cd_paciente)                "
                            . "  left join dbo.tbl_especialidade e on (e.cd_especialidade = a.cd_especialidade) "
                            . "  left join dbo.tbl_profissional m on (m.cd_profissional = a.cd_profissional)    "
                            . "where (a.id_empresa = '{$id_empresa}')  "
                            . "  and (a.dt_agenda  = convert(date, '{$dia}/{$mes}/{$ano}', 103))  "
                            . $filtro . "    "
                            . "order by      "
                            . "	   (case when a.st_agenda = 4 then 1 else 0 end) " // Deixar os agendamentos cancelados no final
                            . "	 , a.hr_agenda "; 
                            
                        $qry = $pdo->query($sql);
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $ds_horario = substr($obj->hora_agenda, 0, 5);
                            $retorno   .= tr_table($obj->id_agenda, $obj->cd_agenda, $ds_horario, $obj->st_agenda, $obj->paciente, $obj->contato, $obj->ds_atendimento, $obj->ds_especialidade);
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
            
                case 'historico_atendimento' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_agenda   = strip_tags( strtoupper(trim($_POST['agenda'])) );
                        $dt_agenda   = strip_tags( strtoupper(trim($_POST['data'])) );
                        $cd_paciente = preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['paciente'])));
                        
                        $retorno = 
                              "<table id='tb-historico' class='table table-bordered table-hover table-striped'> \n"
                            . "  <thead>                    \n"
                            . "    <tr>                     \n"
                            . "      <th>Data</th>          \n" 
                            . "      <th>Hora</th>          \n"
                            . "      <th>Atendimento</th>   \n"
                            . "      <th>Especialidade</th> \n" 
                            . "      <th>Médico</th>        \n" 
                            . "      <th>Situação</th>      \n" 
                            . "    </tr>  \n"
                            . "  </thead> \n"
                            . "  <tbody>  \n";

                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query("exec dbo.getHistoricoAtendimento N'{$id_agenda}', N'{$id_empresa}', N'{$dt_agenda}', {$cd_paciente}");
                        while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $hora_agenda = str_replace(":", "h", substr($obj->hora_agenda, 0, 5));
                            $style = "text-black";
                            switch ($obj->st_agenda) {
                                case '3' : { // Atendido
                                    $style = "text-primary";
                                } break;
                                case '4' : { // Cancelado
                                    $style = "text-red";
                                } break;
                            }
                            $retorno .= 
                                "    <tr class='{$style}'>              \n"
                              . "      <td>{$obj->data_agenda}</td>     \n"
                              . "      <td>{$hora_agenda}</td>          \n"
                              . "      <td>{$obj->tipo}</td>            \n"
                              . "      <td>{$obj->especialidade}</td>   \n"
                              . "      <td>{$obj->profissional}</td>    \n"
                              . "      <td>{$obj->situacao}</td>        \n"
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
            
                case 'carregar_agendamento' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_agenda  = "{" . strip_tags( strtoupper(trim($_POST['codigo'])) ) . "}";
                        
                        $file = '../../logs/json/agendamento_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
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
                            . "from dbo.tbl_agenda a  "
                            . "  inner join dbo.vw_situacao_agenda s on (s.cd_situacao = a.st_agenda)    "
                            . "  inner join dbo.vw_tipo_atendimento t on (t.cd_tipo = a.tp_atendimento)  "
                            . "  left join dbo.tbl_paciente p on (p.cd_paciente = a.cd_paciente)                "
                            . "  left join dbo.tbl_especialidade e on (e.cd_especialidade = a.cd_especialidade) "
                            . "  left join dbo.tbl_profissional m on (m.cd_profissional = a.cd_profissional)    "
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
                            $registro['registro'][0]['convenio']      = $obj->cd_convenio;
                            $registro['registro'][0]['atendimento']   = $obj->tp_atendimento;
                            $registro['registro'][0]['especialidade'] = $obj->cd_especialidade;
                            $registro['registro'][0]['profissional']  = $obj->cd_profissional;
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
            
                case 'ultimo_agendamento' : {
                    try {
                        $id_empresa  = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_agenda   = "{" . strip_tags( strtoupper(trim($_POST['codigo'])) ) . "}";
                        $dt_agenda   = strip_tags( strtoupper(trim($_POST['data'])) );
                        $cd_paciente = preg_replace("/[^0-9]/", "", "0" . trim(strtoupper($_POST['paciente'])));
                        
                        $file = '../../logs/json/ultimo_agendamento_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query("Exec dbo.getUltimoAtendimento N'{$id_agenda}', N'{$id_empresa}', N'{$dt_agenda}', {$cd_paciente}");
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][0]['id']      = $obj->id_agenda;
                            $registro['registro'][0]['codigo']  = $obj->cd_agenda;
                            $registro['registro'][0]['data']    = $obj->data;
                            $registro['registro'][0]['hora']    = $obj->hora;
                            $registro['registro'][0]['tabela']  = $obj->cd_tabela;
                            $registro['registro'][0]['servico'] = $obj->cd_servico;
                            $registro['registro'][0]['especialidade'] = $obj->cd_especialidade;
                            $registro['registro'][0]['valor']   = number_format((isset($obj->vl_servico)?$obj->vl_servico:0), 2, ",", ".");
                        } else {
                            $registro['registro'][0]['id']      = "";
                            $registro['registro'][0]['codigo']  = "0";
                            $registro['registro'][0]['data']    = "";
                            $registro['registro'][0]['hora']    = "";
                            $registro['registro'][0]['tabela']  = "0";
                            $registro['registro'][0]['servico'] = "0";
                            $registro['registro'][0]['especialidade'] = "0";
                            $registro['registro'][0]['valor']   = "0,00";
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
            
                case 'disponibilidade_agenda' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $dt_agenda  = strip_tags( strtoupper(trim($_POST['data'])) );
                        
                        $file = '../../logs/json/agenda_disponivel_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query("Exec dbo.getDisponibilidadeAgenda N'{$id_empresa}', N'{$dt_agenda}'");
                        
                        $registro = array('registro' => array());
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $registro['registro'][0]['horarios']     = $obj->qt_horarios;
                            $registro['registro'][0]['agendados']    = $obj->qt_agendados;
                            $registro['registro'][0]['confirmados']  = $obj->qt_confirmados;
                            $registro['registro'][0]['atendidos']    = $obj->qt_atendidos;
                            $registro['registro'][0]['cancelados']   = $obj->qt_cancelados;
                            $registro['registro'][0]['bloqueados']   = $obj->qt_bloqueados;
                            $registro['registro'][0]['disponivel']   = $obj->qt_disponivel;
                            $registro['registro'][0]['agendamentos'] = $obj->qt_agendamentos;
                            $registro['registro'][0]['confirmacoes'] = $obj->qt_confirmacoes;
                        } else {
                            $registro['registro'][0]['horarios']     = "0";
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
            
                case 'salvar_agendamento' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $referencia = strip_tags( strtoupper(trim($_POST['agenda'])) );
                        //$id_agenda  = "{" . strip_tags( strtoupper(trim($_POST['codigo'])) ) . "}";
                        $id_agenda = strip_tags( strtoupper(trim($_POST['id_agenda'])) );
                        $cd_agenda = (float)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_agenda']));
                        $dt_agenda = strip_tags( trim($_POST['dt_agenda']) );
                        $hr_agenda = strip_tags( trim($_POST['hr_agenda']) ) . ":00";
                        $st_agenda = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['st_agenda']));
                        $cd_paciente = (float)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_paciente']));
                        $nm_paciente = strip_tags( strtoupper(trim($_POST['nm_paciente'])) );
                        $nr_celular  = strip_tags( trim($_POST['nr_celular']) );
                        $nr_telefone = strip_tags( trim($_POST['nr_telefone']) );
                        $ds_email    = strip_tags(strtolower(trim($_POST['ds_email'])) );
                        $tp_atendimento   = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['tp_atendimento']));
                        $cd_convenio      = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_convenio']));
                        $cd_tabela        = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_tabela']));
                        $cd_especialidade = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_especialidade']));
                        $cd_profissional  = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_profissional']));
                        $cd_servico       = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['cd_servico']));
                        $vl_servico       = strip_tags( trim($_POST['vl_servico']) );
                        $ds_observacao    = strip_tags( trim($_POST['ds_observacao']) );
                        $sn_avulso        = (int)preg_replace("/[^0-9]/", "", "0" . trim($_POST['sn_avulso']));
                        
                        $file = '../../logs/json/agendamento_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        
                        $sql = 
                              "Select  "
                            . "    a.* "
                            . "from dbo.tbl_agenda a   "
                            . "where (a.id_agenda = '{$id_agenda}')";                    
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) === false) {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Insert Into dbo.tbl_agenda ("
                                . "    id_agenda	 "
                                . "  , cd_configuracao	 "
                                . "  , dt_agenda	 "
                                . "  , hr_agenda	 "
                                . "  , st_agenda	 "
                                . "  , tp_atendimento	 "
                                . "  , cd_paciente	 "
                                . "  , nm_paciente	 "
                                . "  , nr_celular	 "
                                . "  , nr_telefone	 "
                                . "  , ds_email		 "
                                . "  , ds_observacao	 "
                                . "  , cd_convenio	 "
                                . "  , cd_especialidade	 "
                                . "  , cd_profissional	 "
                                . "  , cd_tabela	 "
                                . "  , cd_servico	 "
                                . "  , vl_servico	 "
                                . "  , dh_insercao	 "
                                . "  , us_insercao	 "
                                . "  , id_empresa	 "
                                . "  , id_atendimento	 "
                                . "  , sn_avulso	 "
                                . ") "
                                . "    OUTPUT             "
                                . "    INSERTED.id_agenda "
                                . "  , INSERTED.cd_agenda "
                                . "  , coalesce(nullif(INSERTED.nr_celular, ''), nullif(INSERTED.nr_telefone, ''), nullif(INSERTED.nr_celular, ''), nullif(INSERTED.nr_telefone, ''), '...') as contato "
                                . "values (               "
                                . "    dbo.ufnGetGuidID() "
                                . "  , NULL               "
                                . "  , " . ($dt_agenda !== ""?"convert(date, '{$dt_agenda}', 103) ":"NULL")
                                . "  , " . ($hr_agenda !== ""?"convert(date, '{$hr_agenda}', 108) ":"NULL")
                                . "  , :st_agenda	  "
                                . "  , :tp_atendimento	  "
                                . "  , " . ($cd_paciente !== 0.0?$cd_paciente:"NULL") . "  "
                                . "  , :nm_paciente	  "
                                . "  , :nr_celular	  "
                                . "  , :nr_telefone	  "
                                . "  , :ds_email		  "
                                . "  , :ds_observacao	  "
                                . "  , " . ($cd_convenio !== 0?$cd_convenio:"NULL") . "  "
                                . "  , " . ($cd_especialidade !== 0?$cd_especialidade:"NULL") . "  "
                                . "  , " . ($cd_profissional !== 0?$cd_profissional:"NULL") . "  "
                                . "  , " . ($cd_tabela  !== 0?$cd_tabela:"NULL") . "  "
                                . "  , " . ($cd_servico !== 0?$cd_servico:"NULL") . "  "
                                . "  , :vl_servico	  "
                                . "  , getdate()	  "
                                . "  , :us_insercao	  "
                                . "  , :id_empresa	  "
                                . "  , NULL               "
                                . "  , 1                  "
                                . ")");                        

                            $stm->execute(array(
                                  ':st_agenda'      => $st_agenda
                                , ':tp_atendimento' => $tp_atendimento
                                , ':nm_paciente'    => $nm_paciente
                                , ':nr_celular'     => $nr_celular
                                , ':nr_telefone'    => $nr_telefone
                                , ':ds_email'       => $ds_email
                                , ':ds_observacao'  => $ds_observacao
                                , ':vl_servico'     => $vl_servico
                                , ':us_insercao'    => $user->getCodigo()
                                , ':id_empresa'     => $id_empresa
                            ));
                            
                            $registro = array('registro' => array());

                            if (($obj = $stm->fetch(PDO::FETCH_OBJ)) !== false) {
                                $ds_horario = substr($hr_agenda, 0, 5);
                                $ds_atendimento   = get_atendimento($pdo, $tp_atendimento);
                                $ds_especialidade = get_especialidade($pdo, $cd_especialidade); 
                                $tr_table = tr_table($obj->id_agenda, $obj->cd_agenda, $ds_horario, $st_agenda, $nm_paciente, $obj->contato, $ds_atendimento, $ds_especialidade);

                                $legenda  = "";
                                $situacao = "Horário sem atendimento agendado.";
                                
                                switch ($st_agenda) {
                                    case 1 : { // Agendado
                                        $legenda .= " text-bold bg-yellow";
                                        $situacao = "O atendimento já está <strong>agendado</strong>.";
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
                                $registro['registro'][0]['codigo']        = $obj->cd_agenda;
                                $registro['registro'][0]['referencia']    = $referencia;
                                $registro['registro'][0]['paciente']      = $nm_paciente;
                                $registro['registro'][0]['contato']       = $obj->contato;
                                $registro['registro'][0]['atendimento']   = $ds_atendimento;
                                $registro['registro'][0]['especialidade'] = $ds_especialidade;
                                $registro['registro'][0]['situacao']      = $st_agenda;
                                $registro['registro'][0]['tag_legenda']   = $legenda;
                                $registro['registro'][0]['tag_situacao']  = $situacao;
                                $registro['registro'][0]['tr_table']      = $tr_table;
                            }
                            
                            $pdo->commit();

                            $json = json_encode($registro);
                            file_put_contents($file, $json);
                        } else {
                            $pdo->beginTransaction();
                            $stm = $pdo->prepare(
                                  "Update dbo.tbl_agenda Set "
                                . "    st_agenda	= :st_agenda        "
                                . "  , tp_atendimento	= :tp_atendimento   "
                                . "  , cd_paciente	= " . ($cd_paciente !== 0.0?$cd_paciente:"NULL") . "  "
                                . "  , nm_paciente	= :nm_paciente      "
                                . "  , nr_celular	= :nr_celular       "
                                . "  , nr_telefone	= :nr_telefone      "
                                . "  , ds_email		= :ds_email         "
                                . "  , ds_observacao	= :ds_observacao    "
                                . "  , cd_convenio	= " . ($cd_convenio !== 0?$cd_convenio:"NULL") . "  "
                                . "  , cd_especialidade	= " . ($cd_especialidade !== 0?$cd_especialidade:"NULL") . "  "
                                . "  , cd_profissional	= " . ($cd_profissional !== 0?$cd_profissional:"NULL") . "  "
                                . "  , cd_tabela	= " . ($cd_tabela  !== 0?$cd_tabela:"NULL") . "  "
                                . "  , cd_servico	= " . ($cd_servico !== 0?$cd_servico:"NULL") . "  "
                                . "  , vl_servico	= :vl_servico   "
                                . "  , dh_alteracao	= getdate()     "
                                . "  , us_alteracao	= :us_alteracao "
                                . "  , id_empresa	= :id_empresa   "
                                . "where id_agenda = :id_agenda         "); 

                            $stm->execute(array(
                                  ':id_agenda'      => $id_agenda
                                , ':st_agenda'      => $st_agenda
                                , ':tp_atendimento' => $tp_atendimento
                                , ':nm_paciente'    => $nm_paciente
                                , ':nr_celular'     => $nr_celular
                                , ':nr_telefone'    => $nr_telefone
                                , ':ds_email'       => $ds_email
                                , ':ds_observacao'  => $ds_observacao
                                , ':vl_servico'     => $vl_servico
                                , ':us_alteracao'   => $user->getCodigo()
                                , ':id_empresa'     => $id_empresa
                            ));
                            
                            $registro = array('registro' => array());

                            $contato    = ($nr_celular !== ""?$nr_celular:($nr_telefone !== ""?$nr_telefone:"..."));
                            $ds_horario = substr($hr_agenda, 0, 5);
                            $ds_atendimento   = get_atendimento($pdo, $tp_atendimento);
                            $ds_especialidade = get_especialidade($pdo, $cd_especialidade); 
                            $tr_table = tr_table($id_agenda, $cd_agenda, $ds_horario, $st_agenda, $nm_paciente, $contato, $ds_atendimento, $ds_especialidade);

                            $legenda  = "";
                            $situacao = "";

                            switch ($st_agenda) {
                                case 1 : { // Agendado
                                    $legenda .= "text-bold bg-yellow";
                                    $situacao = "O atendimento já está <strong>agendado</strong>.";
                                } break;
                                case 2 : { // Confirmado
                                    $legenda .= "text-bold bg-green";
                                    $situacao = "O atendimento selecionado já foi <strong>confirmado</strong>.";
                                } break;
                                case 3 : { // Atendido
                                    $legenda .= "text-bold bg-primary";
                                    $situacao = "O atendimento selecionado já foi <strong>finalizado</strong>.";
                                } break;
                                case 4 : { // Cancelado
                                    $legenda .= "text-bold bg-red";
                                    $situacao = "O atendimento selecionado está <strong>cancelado</strong>.";
                                } break;
                                case 9 : { // Bloqueado
                                    $legenda .= "text-bold bg-lime-active";
                                    $situacao = "O horário selecionado está <strong>bloqueado</strong>.";
                                } break;
                            }

                            $registro['registro'][0]['id']            = $id_agenda;
                            $registro['registro'][0]['codigo']        = $cd_agenda;
                            $registro['registro'][0]['referencia']    = $referencia;
                            $registro['registro'][0]['paciente']      = $nm_paciente;
                            $registro['registro'][0]['contato']       = $contato;
                            $registro['registro'][0]['atendimento']   = $ds_atendimento;
                            $registro['registro'][0]['especialidade'] = $ds_especialidade;
                            $registro['registro'][0]['situacao']      = $st_agenda;
                            $registro['registro'][0]['tag_legenda']   = $legenda;
                            $registro['registro'][0]['tag_situacao']  = $situacao;
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
            
                case 'excluir_agendamento' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_agenda  = '{' . strip_tags( strtoupper(trim($_POST['agenda'])) ) . '}';
                        
                        $sql = 
                              "Select  "
                            . "    a.* "
                            . "from dbo.tbl_agenda a "
                            . "where (a.id_agenda = '{$id_agenda}')";
                        
                        $pdo = Conexao::getConnection();
                        $qry = $pdo->query($sql);
                        
                        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                            $pdo->beginTransaction();
                            if ( ((int)$obj->st_agenda > 0) && ((int)$obj->sn_avulso !== 1) ) {
                                $stm = $pdo->prepare(
                                      "Update dbo.tbl_agenda Set "
                                    . "    st_agenda        = 0 "
                                    . "  , tp_atendimento   = 0 "
                                    . "  , cd_paciente      = NULL "
                                    . "  , nm_paciente      = NULL "
                                    . "  , nr_celular       = NULL "
                                    . "  , nr_telefone      = NULL "
                                    . "  , ds_email         = NULL "
                                    . "  , ds_observacao    = NULL "
                                    . "  , cd_convenio      = NULL "
                                    . "  , cd_especialidade = NULL "
                                    . "  , cd_profissional  = NULL "
                                    . "  , cd_tabela        = NULL "
                                    . "  , cd_servico       = NULL "
                                    . "  , vl_servico       = NULL "
                                    . "  , dh_alteracao     = NULL "
                                    . "  , us_alteracao     = NULL "
                                    . "where id_agenda  = :id_agenda  "
                                    . "  and id_empresa = :id_empresa "); 

                                $stm->execute(array(
                                      ':id_agenda'  => $id_agenda
                                    , ':id_empresa' => $id_empresa
                                ));
                            } else {
                                $pdo->beginTransaction();
                                $stm = $pdo->prepare(
                                      "Delete from dbo.tbl_agenda     "
                                    . "where id_agenda  = :id_agenda  "
                                    . "  and id_empresa = :id_empresa "); 

                                $stm->execute(array(
                                      ':id_agenda'  => $id_agenda
                                    , ':id_empresa' => $id_empresa
                                ));
                            }
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
                
                case 'set_situacao_agendamento' : {
                    try {
                        $id_empresa = strip_tags( strtoupper(trim($_POST['empresa'])) );
                        $id_agenda  = '{' . strip_tags( strtoupper(trim($_POST['agenda'])) ) . '}';
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