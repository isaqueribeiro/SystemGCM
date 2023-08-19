<!DOCTYPE html>
<?php
    ini_set('default_charset', 'UTF-8');
    ini_set('display_errors', true);
    error_reporting(E_ALL);
    date_default_timezone_set('America/Belem');
    
    require '../dist/php/constantes.php';
    require '../dist/dao/conexao.php';
    require '../dist/php/usuario.php';
    
    $id_estacao  = md5($_SERVER["REMOTE_ADDR"]);
    $cd_estado   = "15";      // PARÁ
    $nm_estado   = "Pará";
    $cd_cidade   = "1501402"; // BELÉM
    $nm_cidade   = "BELÉM";
    $cd_convenio = "0";
    $cd_profissional = "0";
    
    $pdo = Conexao::getConnection();
    
    // Carregar dados da empresa
    $qry     = $pdo->query("Select * from dbo.sys_empresa");
    $dados   = $qry->fetchAll(PDO::FETCH_ASSOC);
    $empresa = null;
    foreach($dados as $item) {
        $empresa = $item;
    }
    $qry->closeCursor();
    
    $qry = $pdo->query(
          "Select * "
        . "from dbo.tbl_exame e "
        . "where (e.sn_ativo = 1) "
        . "  and (e.id_empresa = '{$empresa['id_empresa']}')"
        . "order by e.nm_exame");
    $exames = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    $qry = $pdo->query(
          "Select * "
        . "from dbo.tbl_evolucao e "
        . "where (e.sn_ativo = 1) "
        . "  and (e.id_empresa = '{$empresa['id_empresa']}')"
        . "order by e.ds_evolucao");
    $evolucoes = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    $qry = $pdo->query("Select min(cd_convenio) as cd_convenio from dbo.tbl_convenio where sn_ativo = 1");
    if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
        $cd_convenio = $obj->cd_convenio;
    }
    
    $qry = $pdo->query("Select * from dbo.tbl_profissional where sn_ativo = 1");
    $profissionais = $qry->fetchAll(PDO::FETCH_ASSOC);

    $qry = $pdo->query("Select * from dbo.tbl_profissional where sn_ativo = 1");
    if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
        $cd_profissional = $obj->cd_profissional;
    }
    
    $qry = $pdo->query("Select * from dbo.tbl_convenio");
    $convenios = $qry->fetchAll(PDO::FETCH_ASSOC);

    $qry = $pdo->query("Select * from dbo.sys_estado");
    $estados = $qry->fetchAll(PDO::FETCH_ASSOC);

    $qry = $pdo->query("Select * from dbo.sys_cidade where cd_estado = {$cd_estado}");
    $cidades = $qry->fetchAll(PDO::FETCH_ASSOC);

    $qry = $pdo->query("Select * from dbo.tbl_profissao");
    $profissoes = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    $qry = $pdo->query("Select * from dbo.tbl_especialidade where sn_ativo = 1");
    $especialidades = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    $qry = $pdo->query("Select * from dbo.tbl_tabela_cobranca where sn_ativo = 1 and id_empresa = '{$empresa['id_empresa']}'");
    $tabelas = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    // Fechar conexão PDO
    unset($qry);
    unset($pdo);
    
    // Carregar as configurações de filtro do objeto "cookie"
    session_start();
    $user = new Usuario();
    if ( isset($_SESSION['user']) ) {
        $user = unserialize($_SESSION['user']);
    } else {
        header('location: ../index.php');
        exit;
    }

    $hoje = date('d/m/Y');
    $dia_corrente = date('d');
    $mes_corrente = date('m');
    $ano_corrente = date('Y');
    
    $mes_anterior = date('m', strtotime('-1 Month'));
    $ano_anterior = date('Y', strtotime('-1 Month'));
    
    $mes_posterior = date('m', strtotime('+1 Month'));
    $ano_posterior = date('Y', strtotime('+1 Month'));
    
    $espec = 0;
    $prof  = 0;
    $atend = 0;
    $qtde  = 10;
    $file  = "../logs/cookies/agendamento_" . sha1($user->getCodigo()) . ".json";
    if (file_exists($file)) {
        $file_cookie = file_get_contents($file);
        $json = json_decode($file_cookie);
        if (isset($json->filtro[0])) {
            $espec = (int)$json->filtro[0]->cd_especialidade;
            $prof  = (int)$json->filtro[0]->cd_profissional;
            $atend = (int)$json->filtro[0]->tp_atendimento;
            $qtde  = (int)$json->filtro[0]->qt_registro;
        }
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
?>
<html>
  <body class="hold-transition skin-blue sidebar-mini">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h1>
            Agendamentos
            <small>Agenda de atendimentos dos pacientes</small>
            <input type="hidden" id="estacaoID" value="<?php echo $id_estacao;?>">
          </h1>
          <ol class="breadcrumb">
              <li><a href="#"><i class="fa fa-home"></i> Home</a></li>
              <li><a href="#">Recepção</a></li>
              <li class="active" id="page-click" onclick="preventDefault()">Agendamentos</li>
          </ol>
        </section>

        <!-- Main content -->
        <section class="content">

          <!-- Painel de Pesquisa (Calendário) -->
          <div class="col-md-3" id="box-calendario" style="padding-left: 1px; padding-right: 1px;">
            <div class="box box-info" style="padding-left: 1px;">
              <div class="box-header with-border">
                <input type="hidden" id="hoje"     value="<?php echo $hoje;?>">    
                <input type="hidden" id="dia_hoje" value="<?php echo $dia_corrente;?>">  
                <input type="hidden" id="mes_hoje" value="<?php echo $mes_corrente;?>">  
                <input type="hidden" id="ano_hoje" value="<?php echo $ano_corrente;?>">  
                
                <input type="hidden" id="cel" value="td-dia">
                <input type="hidden" id="dia" value="<?php echo $dia_corrente;?>">  
                <input type="hidden" id="mes" value="<?php echo $mes_corrente;?>">  
                <input type="hidden" id="ano" value="<?php echo $ano_corrente;?>">  
                
                <h3 class="box-title" id="box-title-calendario"><?php echo get_nome_mes($mes_corrente)  . "/" . $ano_corrente; ?></h3>
                <div class="box-tools pull-right">
                  <!--<button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Ocultar"><i class="fa fa-minus"></i></button>-->
                  <!--<button type="button" class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remover"><i class="fa fa-times"></i></button>-->
                  <button type="button" class="btn btn-primary" title="<?php echo get_nome_mes($mes_anterior)  . "/" . $ano_anterior; ?>" id="btn-mes-anterior"  onclick="montar_calendario_anterior()"><i class="fa fa-angle-left"></i></button>  
                  <button type="button" class="btn btn-primary" title="<?php echo get_nome_mes($mes_corrente)  . "/" . $ano_corrente; ?>" id="btn-mes-atual"     onclick="montar_calendario_atual()"><i class="fa fa-calendar"></i></button>  
                  <button type="button" class="btn btn-primary" title="<?php echo get_nome_mes($mes_posterior) . "/" . $ano_posterior;?>" id="btn-mes-posterior" onclick="montar_calendario_posterior()"><i class="fa fa-angle-right"></i></button>  
                </div>
              </div>
                
              <div class="box-body" id="box-calendario-mes">
                  <table id='tb-calendario' class='table table-bordered cell-hover'>
                      
                  </table>
              </div>  
  <!--            
              <div class="box-body form-horizontal">
                  <div class="col-md-3">
                      <div class="form-group">
                          <label for="cd_tipo_filtro" class="col-sm-2 control-label">Tipo</label>
                          <div class="col-sm-10">
                              <select class="form-control select2"  id="cd_tipo_filtro" style="width: 100%;">
                                  <option value='0'>Todas</option>
                                  <option value='1'>Apenas ativas</option>
                              </select>
                          </div>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="form-group">
                          <label for="ds_filtro" class="col-sm-2 control-label">Descrição</label>
                          <div class="col-sm-10">
                              <input type="text" class="form-control" id="ds_filtro" placeholder="Informe um dado para filtro">
                          </div>
                      </div>
                  </div>
              </div>
  -->
            </div>

            <div class="box" id="box-filtro" style="padding-left: 1px; padding-right: 1px;">
                <div class="box-header with-border">
                    <h3 class="box-title">Filtro(s) da pesquisa</h3>
                    <div class="box-tools pull-right">
                      <button type="button" class="btn btn-primary" title="Fechar (Voltar à pesquisa)" onclick="fechar_filtro(false)"><i class="fa fa-close"></i></button>  
                    </div>
                </div>

                <div class="box-body form">
                    <div class="form-group">
                        <label for="cd_especialidade_filtro" class="control-label">Especialidade</label>
                        <select class="form-control select2"  id="cd_especialidade_filtro" style="width: 100%;">
                            <option value='0'>Todas</option>
                            <?php
                                foreach($especialidades as $item) {
                                    echo "<option value='{$item['cd_especialidade']}'>{$item['ds_especialidade']}</option>";
                                }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="cd_profissional_filtro" class="control-label">Médico</label>
                        <select class="form-control select2"  id="cd_profissional_filtro" style="width: 100%;">
                            <option value='0'>Todos</option>
                            <?php
                                foreach($profissionais as $item) {
                                    echo "<option value='{$item['cd_profissional']}'>{$item['nm_apresentacao']}</option>";
                                }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="cd_atendimento_filtro" class="control-label">Atendimento</label>
                        <select class="form-control select2"  id="cd_atendimento_filtro" style="width: 100%;">
                            <option value='0'>Todos</option>
                            <option value='1'>Consulta</option>
                            <option value='2'>Retorno</option>
                            <option value='3'>Cortesia</option>
                            <!--<option value='9'>Representante</option>-->
                        </select>
                    </div>
                </div>
                
                <div class="box-footer">
                    <button class='btn btn-primary' id='btn_sumit_pesquisa' name='btn_sumit_pesquisa' onclick='fechar_filtro(true)' title="Executar pesquisa"><i class='fa fa-search'></i></button>
                    <select class="form-control select2"  id="qtde-registros-agend" style="width: 70px;">
                        <option value='5'>5</option>
                        <option value='10'>10</option>
                        <option value='11'>11</option>
                        <option value='12'>12</option>
                        <option value='13'>13</option>
                        <option value='14' selected>14</option>
                        <option value='15'>15</option>
                        <option value='20'>20</option>
                        <option value='25'>25</option>
                        <option value='30'>30</option>
                        <option value='35'>35</option>
                        <option value='40'>40</option>
                        <option value='45'>45</option>
                        <option value='50'>50</option>
                    </select>
                    <span>&nbsp; Quantidade de registros por paginação</span>
                </div>
            </div>  
<!--            
            <div class="box" id="box-localizar" style="padding-left: 1px; padding-right: 1px;">
                <div class="box-header with-border">
                    <h3 class="box-title">Localizar</h3>
                </div>

                <div class="box-body">
                    <div class="col-sm-12 form-horizontal">
                        <div class="form-group">
                            <input type="text" class="form-control input-sm" placeholder="Localizar agendamento..." id="ds_localizar">
                        </div>
                    </div>
                </div>
            </div>  
-->              
            <div class="box" id="box-legenda" style="padding-left: 1px; padding-right: 1px;">
                <div class="box-header with-border">
                    <h3 class="box-title">Legendas</h3>
                </div>
                
                <div class="box-body">
                    <table border='0' style='vertical-align: middle; width: 100%;'>
<!--                        
                        <tr>
                            <td width = 40 class='text-bold bg-yellow' align='center'>0</td>
                            <td>&nbsp;</td>
                            <td>Agendados</td>
                            <td>&nbsp;</td>
                            <td width = 40 class='text-bold bg-red' align='center'>0</td>
                            <td>&nbsp;</td>
                            <td>Cancelados</td>
                        </tr>
                        <tr>
                            <td colspan="7" style="font-size: 3px;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td width = 40 class='text-bold bg-green' align='center'>0</td>
                            <td>&nbsp;</td>
                            <td>Confimados</td>
                            <td>&nbsp;</td>
                            <td width = 40 class='text-bold bg-primary' align='center'>0</td>
                            <td>&nbsp;</td>
                            <td>Atendidos</td>
                        </tr>
                        <tr>
                            <td colspan="7" style="font-size: 3px;">&nbsp;</td>
                        </tr>
-->                        
                        <tr>
                            <td width = 40 class='text-bold bg-gray' align='center' id='qt_disponivel'>0</td>
                            <td>&nbsp;</td>
                            <td>Disponibilidade</td>
                        </tr>
                        <tr>
                            <td width = 40 class='text-bold bg-yellow' align='center' id='qt_agendados'>0</td>
                            <td>&nbsp;</td>
                            <td>Agendados</td>
                        </tr>
                        <tr>
                            <td width = 40 class='text-bold bg-green' align='center' id='qt_confirmados'>0</td>
                            <td>&nbsp;</td>
                            <td>Confimados</td>
                        </tr>
                        <tr>
                            <td width = 40 class='text-bold bg-primary' align='center' id='qt_atendidos'>0</td>
                            <td>&nbsp;</td>
                            <td>Atendidos</td>
                        </tr>
                        <tr>
                            <td width = 40 class='text-bold bg-red' align='center' id='qt_cancelados'>0</td>
                            <td>&nbsp;</td>
                            <td>Cancelados</td>
                        </tr>
                    </table>
                </div>
            </div>  
          </div>

          <div class="col-md-9" id="box-pesquisa" style="padding-left: 1px; padding-right: 1px;">
            <!-- Painel de Pesquisa -->
            <div class="box box-info" style="padding-left: 1px; padding-right: 1px;">
                <div class="box-header with-border">
                    <h3 class="box-title" id="box-title-pesquisa">Agenda do dia <strong><?php echo date('d/m/Y');?></strong></h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-primary" title="Configurar Filtro" onclick="abrir_filtro()" id="btn-configurar-pesquisa"><i class="fa fa-filter"></i></button>
                        <button type="button" class="btn btn-primary" title="Atualizar" onclick="pesquisar_agenda_dia()" id="btn-atualizar-pesquisa"><i class="fa fa-refresh"></i></button>
                        <button type="button" class="btn btn-primary" title="Imprimir agendamentos" onclick="imprimir_agendamentos()" id="btn-imprimir-agenda"><i class="fa fa-print"></i></button>
                        <!--<button type="button" class="btn btn-primary" title="Novo Cadastro" onclick="novo_cadastro('user_<?php // echo $user->getCodigo();?>')" id="btn-novo-cadastro"><i class="fa fa-file-o"></i></button>-->
                    </div>
                </div>

                <div class="box-body" id="box-tabela">
                  <p>Lista de registros resultantes da pesquisa</p>
                </div>
            </div>
          </div>
<!--            
          <div class="col-md-12" id="box-espaco" style="padding-left: 1px; padding-right: 1px;">
            <div class="box-body" id="box-tabela">
                <?php
//                for ($i = 0; $i < 30; $i++) {
//                    echo "<p>.</p>";
//                }
                ?>
            </div>
          </div>
-->
          <div class="col-md-12" id="box-cadastro" style="padding-left: 1px; padding-right: 1px;">
              
            <!-- Painel de Cadastro -->
            <div class="box box-primary" style="padding-left: 1px;">
                <div class="box-header with-border">
                    <h3 class="box-title">Atendimento</h3>
                    <div class="box-tools pull-right">
                        <input type="hidden" id="id_linha">
                        <input type="hidden" id="operacao">
                        <input type="hidden" id="referencia">
                        <input type="hidden" id="id_atendimento">
                        <input type="hidden" id="dt_atendimento">
                        <input type="hidden" id="sn_todos_exames"  value="0">
                        <input type="hidden" id="sn_todas_medidas" value="0">
                        <button type="button" class="btn btn-primary" title="Fechar (Voltar à pesquisa)" onclick="voltar_pesquisa()"><i class="fa fa-close"></i></button>
                    </div>
                </div>

                <div class="row-border">
                    <div class="col-md-12">
                        <div class="nav-tabs-custom">
                            <ul class="nav nav-tabs">
                              <li class="active" id="tab_1a"><a href="#tab_1" data-toggle="tab">Agendar</a></li>
                              <li id="tab_2a"><a href="#tab_2" data-toggle="tab" onclick="historico_atendimento()">Histórico</a></li>
                              <li id="tab_3a"><a href="#tab_3" data-toggle="tab" onclick="carregar_exames()">Exames</a></li>
                              <li id="tab_4a"><a href="#tab_4" data-toggle="tab" onclick="carregar_evolucoes()">Evoluções de Medidas</a></li>
                            </ul>
                            
                            <div class="tab-content">
                                <div class="tab-pane active" id="tab_1">
                                    <div class="box-body form-horizontal">
                                        <div class="col-md-12">
                                            <div class="form-group" style="margin: 2px;">
                                                <label for="cd_agenda" class="col-sm-1 control-label padding-label">Código</label>
                                                <div class="col-sm-2 padding-field">
                                                    <input type="hidden" id="id_agenda" value="">
                                                    <input type="text" class="form-control proximo_campo" id="cd_agenda" maxlength="5" placeholder="000" readonly>
                                                </div>
                                                <label for="dt_agenda" class="col-sm-1 control-label padding-label">Data/Hora</label>
                                                <div class="col-sm-3 padding-field">
                                                  <table border="0" style="width: 100%;">
                                                      <tr>
                                                          <td style="width: 60%;">
                                                              <div class="input-group" style="width: 99%;">
                                                                  <div class="input-group-addon">
                                                                      <i class="fa fa-calendar"></i>
                                                                  </div>
                                                                  <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask id="dt_agenda">
                                                              </div>
                                                          </td>
                                                          <td>
                                                              <div class="input-group">
                                                                  <div class="input-group-addon">
                                                                      <i class="fa fa-clock-o"></i>
                                                                  </div>
                                                                  <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_agenda">
                                                              </div>
                                                          </td>
                                                      </tr>
                                                  </table>
                                                </div>
                                                <label for="st_agenda" class="col-sm-1 control-label padding-label">Situação</label>
                                                <div class="col-sm-4 padding-field">
                                                  <select class="form-control select2"  id="st_agenda" style="width: 100%;" disabled>
                                                      <!--<option value='0'>Selecione a situação</option>-->
                                                      <option value='0'>Selecione a situação</option>
                                                      <option value='1'>Agendado</option>
                                                      <option value='2'>Confirmado</option>
                                                      <option value='3'>Atendido</option>
                                                      <option value='4'>Cancelado</option>
                                                      <option value='9'>Bloqueado</option>
                                                      <option value='11'>Agendar</option>
                                                      <option value='12'>Confirmar</option>
                                                  </select>
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                              <label for="cd_paciente_ag" class="col-sm-1 control-label padding-label">Prontuário</label>
                                              <div class="col-sm-11 padding-field">
                                                  <table border="0" style="width: 100%;">
                                                      <tr>
                                                          <td style="width: 150px;">
                                                              <div class="input-group" style="width: 99%;">
                                                                  <input type="hidden" id="cd_prontuario" value="0">
                                                                  <input type="hidden" id="nm_prontuario" value="">
                                                                  <input type="text" class="form-control proximo_campo" id="cd_paciente_ag" maxlength="10">
                                                                  <div class="input-group-addon" style="padding: 0px;">
                                                                      <button type="button" class="btn btn-sm btn-primary proximo_campo" title="Buscar Paciente pelo Prontuário" id="btn_buscar_paciente" onclick="buscar_paciente('#cd_paciente_ag')"><i class="fa fa-search"></i></button>
                                                                  </div>
                                                              </div>
                                                          </td>
                                                          <td>
                                                              <div class="input-group">
                                                                <div class="input-group-addon" style="padding: 0px;">
                                                                    <button type="button" class="btn btn-sm btn-default" title="Cadastro do Paciente" id="btn_editar_paciente" onclick="abrir_cadastro_paciente()"><i class="fa fa-edit"></i></button>
                                                                </div>
                                                                <input type="text" class="form-control proximo_campo" id="nm_paciente_ag" maxlength="150" placeholder="Nome do paciente" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                                              </div>
                                                          </td>
                                                      </tr>
                                                  </table>
                                              </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <label for="nr_celular_ag" class="col-sm-1 control-label padding-label">Contatos</label>
                                                <div class="col-sm-4 padding-field">
                                                  <table border="0" style="width: 100%;">
                                                      <tr>
                                                          <td style="width: 150px;">
                                                              <div class="input-group" style="width: 99%;">
                                                                <div class="input-group-addon">
                                                                    <i class="fa fa-mobile-phone"></i>
                                                                </div>
                                                                <!--<input type="text" class="form-control proximo_campo" data-inputmask='"mask": "(99)99999-9999"' data-mask id="nr_celular_ag">-->
                                                                <input type="text" class="form-control proximo_campo" id="nr_celular_ag">  
                                                              </div>
                                                          </td>
                                                          <td>
                                                              <div class="input-group">
                                                                <div class="input-group-addon">
                                                                    <i class="fa fa-phone"></i>
                                                                </div>
                                                                <!--<input type="text" class="form-control proximo_campo" data-inputmask='"mask": "(99)9999-9999"' data-mask id="nr_telefone">-->
                                                                <input type="text" class="form-control proximo_campo" id="nr_telefone_ag">  
                                                              </div>
                                                          </td>
                                                      </tr>
                                                  </table>
                                                </div>
                                                <label for="ds_email_ag" class="col-sm-2 control-label padding-label">E-mail</label>
                                                <div class="col-sm-5 padding-field">
                                                  <div class="input-group">
                                                      <span class="input-group-addon">@</span>
                                                      <input type="email" class="form-control proximo_campo" id="ds_email_ag" placeholder="E-mail(s) do paciente" onkeyup="javascript: this.value = texto_minusculo(this);">
                                                  </div>
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <label for="cd_convenio_ag" class="col-sm-1 control-label padding-label">Categoria</label>
                                                <div class="col-sm-4 padding-field">
                                                    <select class="form-control select2 proximo_campo" id="cd_convenio_ag" style="width: 100%;">
                                                        <option value='0'>Selecione a categoria</option>
                                                        <?php
                                                            foreach($convenios as $item) {
                                                                echo "<option value='{$item['cd_convenio']}'>{$item['nm_resumido']}</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                                <label for="tp_atendimento" class="col-sm-2 control-label padding-label">Atendimento</label>
                                                <div class="col-sm-5 padding-field">
                                                    <select class="form-control select2"  id="tp_atendimento" style="width: 100%;">
                                                        <option value='0'>Selecione o tipo de atendimento</option>
                                                        <option value='1'>Consulta</option>
                                                        <option value='2'>Retorno</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <label for="cd_servico" class="col-sm-1 control-label padding-label"></label>
                                                <div class="col-sm-4 padding-field">
                                                    <input type="hidden" id="cd_servico" value="0">
                                                </div>
                                                <label for="cd_tabela" class="col-sm-2 control-label padding-label">Serviço (R$)</label>
                                                <div class="col-sm-5 padding-field">
                                                  <table border="0" style="width: 100%;">
                                                      <tr>
                                                          <td style="width: 60%;">
                                                            <select class="form-control select2"  id="cd_tabela" style="width: 99%;" onchange="buscar_tabela()">
                                                                <option value='0'>Selecione a tabela de valoração</option>
                                                                <?php
                                                                    foreach($tabelas as $item) {
                                                                        echo "<option value='{$item['cd_tabela']}'>{$item['nm_tabela']}</option>";
                                                                    }
                                                                ?>
                                                            </select>
                                                          </td>
                                                          <td>
                                                            <div class="input-group">
                                                                <div class="input-group-addon">
                                                                    <i class="fa fa-money"></i>
                                                                </div>
                                                                <input type="text" class="form-control proximo_campo" id="vl_servico" maxlength="7"  style="text-align: right;" onkeypress="return somente_numero_decimal(event);">
                                                            </div>
                                                          </td>
                                                      </tr>
                                                  </table>
                                                </div>
                                                <input type="hidden" id="cd_especialidade" value="0">
                                                <input type="hidden" id="cd_profissional"  value="0">
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                              <label for="ds_observacao" class="col-sm-1 control-label padding-label">Obs.</label>
                                              <div class="col-sm-11 padding-field">
                                                  <textarea class="form-control" rows="5" id="ds_observacao" placeholder="Observações gerais sobre o agendamento..." style="width: 100%;"></textarea>
                                              </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <div class="col-sm-1 padding-field"></div>
                                                <div class="col-sm-8 padding-field">
                                                    <div class="checkbox icheck">
                                                      <label>
                                                          <input class="proximo_campo" type="checkbox" id="sn_avulso" value="1" disabled> Agendamento avulso
                                                      </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="tab-pane" id="tab_2">
                                    
                                </div>
                                
                                <div class="tab-pane" id="tab_3">
                                    <div class="box no-border">
                                        <div class="row-border">
                                            <select class="form-control select2 no-padding"  id="id_exame" style='width: 250px;'>
                                                <option value=''>Selecione novo exame</option>
                                                <?php
                                                foreach($exames as $item) {
                                                    echo "<option value='{$item['id_exame']}'>{$item['nm_exame']}</option>";
                                                }
                                                ?>
                                            </select>
                                            <button type="button" class="btn btn-primary" title="Novo Exame" onclick="inserir_exame()" id="btn-adcionar-exame"><i class="fa fa-plus"></i></button>
                                            <div class="box-tools pull-right">
                                                <button type="button" class="btn btn-primary" title="Salvar resultado de exames" onclick="gravar_resultados_exames()" id="btn-salvar-exames"><i class="fa fa-save"></i></button>
                                                <button type="button" class="btn btn-primary" title="Atualizar lista de Exames"  onclick="carregar_exames()" id="btn-atualizar-exames"><i class="fa fa-refresh"></i></button>
                                                <button type="button" class="btn btn-primary" title="Imprimir Controle de Exames"  onclick="imprimir_controle_exames(null)" id="btn-imprimir-exames"><i class="fa fa-print"></i></button>
                                            </div>
                                        </div>

                                        <div class="box-body no-padding" id="box-tabela_exames">
                                            <p style='font-size: 3px;'>&nbsp;</p>

                                        </div>
                                    </div>
                                </div>
                                
                                <div class="tab-pane" id="tab_4">
                                    <div class="box no-border">
                                        <div class="row-border">
                                            <select class="form-control select2 no-padding"  id="id_evolucao" style='width: 250px;'>
                                                <option value=''>Selecione nova evolução</option>
                                                <?php
                                                foreach($evolucoes as $item) {
                                                    echo "<option value='{$item['id_evolucao']}'>{$item['ds_evolucao']}</option>";
                                                }
                                                ?>
                                            </select>
                                            <button type="button" class="btn btn-primary" title="Nova Evolução de Medida" onclick="inserir_evolucao()" id="btn-adicionar-evolucao"><i class="fa fa-plus"></i></button>
                                            <div class="box-tools pull-right">
                                                <button type="button" class="btn btn-primary" title="Salvar evoluções de medicas" onclick="gravar_resultados_evolucoes()" id="btn-salvar-evolucoes"><i class="fa fa-save"></i></button>
                                                <button type="button" class="btn btn-primary" title="Atualizar lista de Evoluções"  onclick="carregar_evolucoes()" id="btn-atualizar-evolucoes"><i class="fa fa-refresh"></i></button>
                                                <button type="button" class="btn btn-primary" title="Imprimir Evolução de Medidas"  onclick="imprimir_controle_evolucoes(null)" id="btn-imprimir-evolucoes"><i class="fa fa-print"></i></button>
                                            </div>
                                        </div>

                                        <div class="box-body no-padding" id="box-tabela_evolucoes">
                                            <p style='font-size: 3px;'>&nbsp;</p>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="box-footer">
                    <button type="button" class="btn btn-default pull-left"  data-dismiss="modal" id="btn_form_close" onclick="voltar_pesquisa()">Fechar</button>
                    <button type="button" class="btn btn-primary pull-right" data-dismiss="modal" id="btn_form_save"  onclick="salvar_cadastro()">Salvar</button>
                </div>
            </div>
          </div>
          
          <button type="button" class="btn btn-sm" data-toggle="modal" data-target="#modal-pesquisa_paciente" id="btn_pesquisar_paciente"></button>
          <button type="button" class="btn btn-sm" data-toggle="modal" data-target="#modal-cadastro_paciente" id="btn_cadastrar_paciente"></button>
          
          <div class="modal fade" id="modal-pesquisa_paciente">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Buscar Paciente</h4>
                    </div>
                    <div class="modal-body">
                        <div class="col-md-12 padding-field">
                            <div class="form-group">
                                <input type="hidden" id="id_linha_paciente">
                                <label for="ds_filtro_paciente" class="control-label padding-label">Localizar:</label>
                                <table border="0" style="width: 100%;">
                                    <tr>
                                        <td style="width: 99%;">
                                            <input type="text" class="form-control proximo_campo" id="ds_filtro_paciente" placeholder="Informe nome ou data de nascimento...">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary proximo_campo" title="Executar busca" onclick="executar_pesquisa_paciente()"><i class="fa fa-search"></i></button>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="box-body padding-field" id="box-tabela-pacientes">
                            <p>Execute a busca de registros dos pacientes cadastrados pelo <strong>nome</strong> ou <strong>data de nascimento</strong>...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default pull-left" data-dismiss="modal" id="find_close">Fechar</button>
                        <!--<button type="button" class="btn btn-primary pull-right" data-dismiss="modal" id="new_paciente" onclick="$('#cd_prontuario').val('0');abrir_cadastro_paciente();"><i class="fa fa-file-o"></i> Novo</button>-->
                    </div>
                </div>
            </div>
          </div>

          <div class="modal fade" id="modal-cadastro_paciente">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><i class="fa fa-edit"></i> Cadastro Paciente</h4>
                    </div>
                    <div class="modal-body">
                        <div class="col-md-12">
                            <div class="nav-tabs-custom">
                                <ul class="nav nav-tabs">
                                    <li class="active" id="tab_11a"><a href="#tab_11" data-toggle="tab">Identificação</a></li>
                                    <li id="tab_12a"><a href="#tab_12" data-toggle="tab">Outras informações</a></li>
                                </ul>
                                
                                <div class="tab-content">
                                    <div class="tab-pane active" id="tab_11">
                                        <div class="box-body form-horizontal">
                                            <div class="form-group" style="margin: 2px;">
                                                <label for="cd_paciente" class="col-sm-2 control-label padding-label">Prontuário</label>
                                                <div class="col-sm-2 padding-field">
                                                    <input type="text" class="form-control" id="cd_paciente" maxlength="10" placeholder="0000000" readonly>
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <label for="nm_paciente" class="col-sm-2 control-label padding-label">Nome</label>
                                                <div class="col-sm-10 padding-field">
                                                    <input type="text" class="form-control proximo_campo" id="nm_paciente" maxlength="200" placeholder="Informe nome completo do paciente" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                                </div>
                                            </div>
                                            
                                            <div class="form-group" style="margin: 2px;">
                                                <label for="dt_nascimento" class="col-sm-2 control-label padding-label">D.Nascimento</label>
                                                <div class="col-sm-3 padding-field">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">
                                                            <i class="fa fa-calendar"></i>
                                                        </div>
                                                        <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask id="dt_nascimento">
                                                    </div>
                                                </div>
                                                <label for="tp_sexo" class="col-sm-1 control-label padding-label">Sexo</label>
                                                <div class="col-sm-2 padding-field">
                                                    <select class="form-control select2 proximo_campo"  id="tp_sexo" style="width: 100%;">
                                                        <option value='0'>Informe o sexo</option>
                                                        <option value='M'>Masculino</option>
                                                        <option value='F'>Feminino</option>
                                                        <!--<option value='N'>Não declarado</option>-->
                                                        <!--<option value='I'>Indefinido</option>-->
                                                    </select>
                                                </div>
                                                <label for="ds_profissao" class="col-sm-1 control-label padding-label">Profissão</label>
                                                <div class="col-sm-3 padding-field">
                                                    <input type="hidden" id="cd_profissao" value="0">
                                                    <input type="text" class="form-control proximo_campo" id="ds_profissao" maxlength="150" placeholder="Descreva as profissões...">
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <label for="nr_rg" class="col-sm-2 control-label padding-label">RG</label>
                                                <div class="col-sm-3 padding-field">
                                                    <input type="text" class="form-control proximo_campo" id="nr_rg" maxlength="10" placeholder="Registro Geral">
                                                </div>
                                                <label for="ds_orgao_rg" class="col-sm-1 control-label padding-label">Orgão</label>
                                                <div class="col-sm-2 padding-field">
                                                    <input type="text" class="form-control proximo_campo" id="ds_orgao_rg" maxlength="10" placeholder="Orgão/UF" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                                </div>
                                                <input type="hidden" id="dt_emissao_rg" value="">
                                                <label for="nr_cpf" class="col-sm-1 control-label padding-label">CPF</label>
                                                <div class="col-sm-3 padding-field">
                                                    <input type="text" class="form-control proximo_campo" data-inputmask='"mask": "999.999.999-99"' data-mask id="nr_cpf">
                                                </div>
                                            </div>

                                            <input type="hidden" id="nm_pai" value="">
                                            <input type="hidden" id="nm_mae" value="">

                                            <div class="form-group" style="margin: 2px;">
                                                <label for="nm_acompanhante" class="col-sm-2 control-label padding-label">Acompanhante</label>
                                                <div class="col-sm-10 padding-field">
                                                    <input type="text" class="form-control proximo_campo" id="nm_acompanhante" maxlength="150" placeholder="Nome do acompanhante" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <label for="nm_indicacao" class="col-sm-2 control-label padding-label">Indicado por</label>
                                                <div class="col-sm-10 padding-field">
                                                    <input type="text" class="form-control proximo_campo" id="nm_indicacao" maxlength="150" placeholder="Nome de quem indicou" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <div class="col-sm-12 padding-field">
                                                    <label class="col-sm-12 label-info text-center padding-label">Informações de contato</label>
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <label for="nr_celular" class="col-sm-2 control-label padding-label">Celular</label>
                                                <div class="col-sm-3 padding-field">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">
                                                            <i class="fa fa-mobile-phone"></i>
                                                        </div>
                                                        <input type="text" class="form-control proximo_campo" maxlength="15" id="nr_celular" placeholder="Número do celular">
                                                    </div>
                                                </div>
                                                <label for="nr_telefone" class="col-sm-1 control-label padding-label">Tefefone</label>
                                                <div class="col-sm-3 padding-field">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">
                                                            <i class="fa fa-phone"></i>
                                                        </div>
                                                        <input type="text" class="form-control proximo_campo" maxlength="15" id="nr_telefone" placeholder="Telefone fixo">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <label for="ds_contatos" class="col-sm-2 control-label padding-label">Outros contatos</label>
                                                <div class="col-sm-10 padding-field">
                                                    <input type="text" class="form-control proximo_campo" id="ds_contatos" maxlength="150" placeholder="Informe aqui outros números para contato...">
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <label for="ds_email" class="col-sm-2 control-label padding-label">E-mail(s)</label>
                                                <div class="col-sm-10 padding-field">
                                                    <div class="input-group">
                                                        <span class="input-group-addon">@</span>
                                                        <input type="email" class="form-control proximo_campo" id="ds_email" placeholder="Informe o(s) e-mail(s) do paciente" onkeyup="javascript: this.value = texto_minusculo(this);">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <div class="col-sm-12 padding-field">
                                                    <label class="col-sm-12 label-info text-center padding-label">Endereço</label>
                                                </div>
                                            </div>

                                            <input type="hidden" id="cd_estado"      value="0">
                                            <input type="hidden" id="cd_cidade"      value="0">
                                            <input type="hidden" id="tp_endereco"    value="0">
                                            <input type="hidden" id="ds_endereco"    value="">
                                            <input type="hidden" id="nr_endereco"    value="">
                                            <input type="hidden" id="nm_bairro"      value="">
                                            <input type="hidden" id="ds_complemento" value="">
                                            
                                            <div class="form-group" style="margin: 2px;">
                                                <label for="end_logradouro" class="col-sm-2 control-label padding-label">Endereço</label>
                                                <div class="col-sm-6 padding-field">
                                                    <input type="text" class="form-control proximo_campo" id="end_logradouro" placeholder="Descrição completa do endereço" maxlength="150">
                                                </div>
                                                <label for="end_bairro" class="col-sm-1 control-label padding-label">Bairro</label>
                                                <div class="col-sm-3 padding-field">
                                                    <input type="text" class="form-control proximo_campo" id="end_bairro" placeholder="Nome do bairro..."  maxlength="50"onkeyup="javascript: this.value = texto_maiusculo(this);">
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <label for="end_cidade" class="col-sm-2 control-label padding-label">Cidade</label>
                                                <div class="col-sm-3 padding-field">
                                                    <input type="text" class="form-control proximo_campo" id="end_cidade" placeholder="Nome da cidade/município..."  maxlength="100"onkeyup="javascript: this.value = texto_maiusculo(this);">
                                                </div>
                                                <label for="end_estado" class="col-sm-1 control-label padding-label">Estado</label>
                                                <div class="col-sm-2 padding-field">
                                                    <input type="text" class="form-control proximo_campo" id="end_estado" placeholder="Nome do estado..."  maxlength="50"><!--onkeyup="javascript: this.value = texto_maiusculo(this);"-->
                                                </div>
                                                <label for="nr_cep" class="col-sm-1 control-label padding-label">Cep</label>
                                                <div class="col-sm-3 padding-field">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control proximo_campo" data-inputmask='"mask": "99999-999"' data-mask id="nr_cep">
                                                        <div class="input-group-addon" style="padding: 0px;">
                                                            <button type="button" class="btn btn-sm btn-primary proximo_campo" title="Buscar Endereço pelo CEP" onclick="buscar_endereco_custom('#nr_cep')"><i class="fa fa-search"></i></button>
                                                            <button type="button" class="btn btn-sm btn-default" title="Limpar informações de endereço" onclick="limpar_endereco_custom('#nr_cep')"><i class="fa fa-file-o"></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group" style="margin: 2px;">
                                                <div class="col-sm-2 padding-field"></div>
                                                <div class="col-sm-7 padding-field">
                                                    <div class="checkbox icheck">
                                                      <label>
                                                          <input class="proximo_campo" type="checkbox" id="sn_ativo" value="1" disabled> Cadastro ativo
                                                      </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="tab-pane" id="tab_12">
                                        <div class="box-body form-horizontal">
                                            <input type="hidden" id="cd_convenio"  value="0">
                                            <input type="hidden" id="nr_matricula" value="">

                                            <div class="form-group" style="margin: 2px;">
                                                <label for="ds_alergias" class="col-sm-2 control-label padding-label">Alergias</label>
                                                <div class="col-sm-10 padding-field">
                                                    <textarea class="form-control" rows="7" id="ds_alergias" placeholder="Descreva as alergias do paciente caso tenha..." style="width: 100%;"></textarea>
                                                </div>
                                            </div>

                                            <div class="form-group" style="margin: 2px;">
                                                <label for="ds_observacoes" class="col-sm-2 control-label padding-label">Observações</label>
                                                <div class="col-sm-10 padding-field">
                                                    <textarea class="form-control" rows="7" id="ds_observacoes" placeholder="Observações em geral..." style="width: 100%;"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default pull-left" data-dismiss="modal" id="fechar_form_paciente" onclick="ativar_guia('#tab_1');$('#cd_paciente_ag').focus();">Fechar</button>
                        <button type="button" class="btn btn-primary pull-right" id="salvar_form_paciente" onclick="salvar_cadastro_paciente()">Salvar</button>
                    </div>
                </div>
            </div>
          </div>
          
          <?php
          include './modal.php';
          ?>
        </section>
        <!-- /.content -->

        <!--
        <div class='alert alert-danger alert-dismissible'>
            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
            <h4><i class='icon fa fa-ban'></i> Alerta!</h4>
            Danger alert preview. This alert is dismissable. A wonderful serenity has taken possession of my entire
            soul, like these sweet mornings of spring which I enjoy with my whole heart.
        </div>
        -->

        <script type="text/javascript">
            /* Ao pressionar uma tecla em um campo que seja de class="proximo_campo" */ 
            $('#box-cadastro, #modal-pesquisa_paciente, #modal-cadastro_paciente').on('keyup', '.proximo_campo', function(e) {
                /* 
                 * Verifica se o evento é Keycode (para IE e outros browsers)
                 * se não for pega o evento Which (Firefox)
                 */
                var tecla = (e.keyCode?e.keyCode:e.which);
                if (tecla === 13) {
                    try {
                        /* Guarda o seletor do campo que foi pressionado Enter */
                        var campo =  $('.proximo_campo');
                        /* Pega o indice do elemento */
                        var indice = campo.index(this);
                        /*
                         * Soma mais um ao indice e verifica se não é null
                         * se não for é porque existe outro elemento
                         */
                        if (campo[indice + 1] !== null) {
                            /* Adiciona mais 1 no valor do indice */
                            var proximo = campo[indice + 1];
                            /* Passa o foco para o proximo elemento */
                            proximo.focus();
                        }
                    } catch (e) {
                        ;
                    }
                }
                /* Impede o sumbit caso esteja dentro de um form */
                e.preventDefault(e);
                return false;
            });
            
            // Colocar o foco dentro do campo da pesquisa modal
            $('#modal-pesquisa_paciente').on('shown.bs.modal', function(event) {
                $('#ds_filtro_paciente').focus();
            });
            
            $('#modal-cadastro_paciente').on('shown.bs.modal', function(event) {
                $('#nm_paciente').focus();
            });
            
            <?php
            include './../dist/js/pages/_modal.js';
            ?>
            
            $(function () {
                $.fn.dataTable.moment('DD/MM/YYYY');
                
                $('#btn_pesquisar_paciente').fadeOut(1);
                $('#btn_cadastrar_paciente').fadeOut(1);
                $("#btn_msg_padrao").fadeOut(1);
                $("#btn_msg_alerta").fadeOut(1);
                $("#btn_msg_erro").fadeOut(1);
                $("#btn_msg_informe").fadeOut(1);
                $("#btn_msg_primario").fadeOut(1);
                $("#btn_msg_sucesso").fadeOut(1);

//                // Carregar as configurações de filtro do objeto "cookie"
//                try {
//                    var file_cookie = "./logs/cookies/cep_" + $('#cookieID').val() + ".json";
//                    $.getJSON(file_cookie, function(data){
//                        var qtde = data.filtro.length;
//                        if (qtde === 1) {
//                            $('#qtde-registros-agend').val(data.filtro[qtde - 1].qt_registro);
//                            $('#qtde-registros-agend').select2();
//                        }
//                    });
//                } catch (er) {
//                }
                $('#cd_especialidade_filtro').val(<?php echo $espec;?>);
                $('#cd_especialidade_filtro').select2();
                $('#cd_atendimento_filtro').val(<?php echo $atend;?>);
                $('#cd_atendimento_filtro').select2();
                $('#qtde-registros-agend').val(<?php echo $qtde;?>);
                $('#qtde-registros-agend').select2();
                
                configurar_checked();
                configurar_tabela('#tb-agendamentos');
//                
//                // Autocomplete para Estados
//                var estadosTags = [
//                    'Outros'
//                    <?php
//                        foreach($estados as $item) {
//                            echo ", '{$item['nm_estado']}'";
//                        }
                    ?>//
//                ];
//                $('#end_estado').autocomplete({
//                    source: estadosTags
//                });    
//                    
//                // Autocomplete para Cidades
//                var cidadesTags = [
//                    'Outras'
//                    <?php
//                        foreach($cidades as $item) {
//                            echo ", '{$item['nm_cidade']}'";
//                        }
                    ?>//
//                ];
//                $('#end_cidade').autocomplete({
//                    source: cidadesTags
//                });    
//                    
//                // Autocomlete para Profissões
//                var profissoesTags = [
//                    'Outras'
//                    <?php
//                        foreach($profissoes as $item) {
//                            echo ", '{$item['ds_profissao']}'";
//                        }
                    ?>//
//                ];
//                $('#ds_profissao').autocomplete({
//                    source: profissoesTags
//                });    
            });
            
            $('#box-filtro').hide();
            $('#box-pesquisa').show();
            $('#box-cadastro').hide();

            $('#cd_tipo_filtro').val("1");
            $(".select2").select2();      // Ativar o CSS nos "Select"
            $('[data-mask]').inputmask(); // Ativar as máscaras nas "Input"

            montar_calendario();
            pesquisar_agenda_dia();
            
            if (document.getElementById("box-filtro").style.display === 'block') { $('#btn-configurar-pesquisa').fadeOut() };

            function montar_hint_botoes(elemento, mes, ano) {
                if (mes === 0) {
                    mes = 12
                    ano = ano - 1;
                } else
                if (mes === 13) {
                    mes = 1
                    ano = ano + 1;
                } 
                if (typeof($(elemento)) !== "undefined") {
                    $(elemento).prop("title", get_mes(mes) + "/" + ano);
                }
            }
            
            function montar_titulo(dia) {
                // Mantar título da Agenda
                var title = "Agenda do dia <strong>" + zero_esquerda(dia[1], 2) + "/" + zero_esquerda(dia[2], 2) + "/" + dia[3] + "</strong>";
                if ($('#cd_especialidade_filtro').val() !== "0") title += " (" + $('#cd_especialidade_filtro option:selected').text() + ")";
                $('#box-title-pesquisa').html(title);
            }    
            
            function montar_calendario_atual() {
                $('#mes').val( $('#mes_hoje').val() );
                $('#ano').val( $('#ano_hoje').val() );
                
                var mes = parseInt($('#mes').val()),
                    ano = parseInt($('#ano').val());
                
                $('#box-title-calendario').html(get_mes(mes) + "/" + ano);
                
                montar_hint_botoes('#btn-mes-anterior',  mes - 1, ano);
                montar_hint_botoes('#btn-mes-posterior', mes + 1, ano);
                montar_calendario();
            }
            
            function montar_calendario_anterior() {
                var mes = parseInt($('#mes').val()),
                    ano = parseInt($('#ano').val());
            
                if (mes === 1) {
                    mes = 12;
                    ano = ano - 1;
                } else {
                    mes = mes - 1;
                }
                
                $('#mes').val( mes );
                $('#ano').val( ano );
                
                $('#box-title-calendario').html(get_mes(mes) + "/" + ano);
                
                montar_hint_botoes('#btn-mes-anterior',  mes - 1, ano);
                montar_hint_botoes('#btn-mes-posterior', mes + 1, ano);
                montar_calendario();
            }
            
            function montar_calendario_posterior() {
                var mes = parseInt($('#mes').val()),
                    ano = parseInt($('#ano').val());
            
                if (mes === 12) {
                    mes = 1;
                    ano = ano + 1;
                } else {
                    mes = mes + 1;
                }
                
                $('#mes').val( mes );
                $('#ano').val( ano );
                
                $('#box-title-calendario').html(get_mes(mes) + "/" + ano);
                
                montar_hint_botoes('#btn-mes-anterior',  mes - 1, ano);
                montar_hint_botoes('#btn-mes-posterior', mes + 1, ano);
                montar_calendario();
            }
            
            function pesquisar_agenda_dia() {
                var dt_agenda = zero_esquerda($('#dia').val(), 2) + "/" + zero_esquerda($('#mes').val(), 2) + "/" + zero_esquerda($('#ano').val(), 4);
                pesquisar_agendamentos();
                buscar_disponibilidade_agenda(dt_agenda, function(data) {
                    $('#qt_disponivel').html( get_value(data.registro[0].disponivel, '0') );
                    $('#qt_agendados').html( get_value(data.registro[0].agendados, '0') );
                    $('#qt_confirmados').html( get_value(data.registro[0].confirmados, '0') );
                    $('#qt_atendidos').html( get_value(data.registro[0].atendidos, '0') );
                    $('#qt_cancelados').html( get_value(data.registro[0].cancelados, '0') );
                });
            }
            function montar_agendamentos(id, elemento) {
                var dia = id.split("_");
                var ant = $('#cel').val();
                var td  = $(elemento).closest('td');
                
                // Remover destaque da célular
                if (typeof($(ant)) !== "undefined") {
                    var col = ant.split("_");
                    
                    if ( $(ant).hasClass("text-bold") ) $(ant).removeClass("text-bold");
                    if ( $(ant).hasClass("bg-primary") ) $(ant).removeClass("bg-primary");
                    if ( $(ant).hasClass("bg-gray") ) $(ant).removeClass("bg-gray");
                    if ((parseInt(col[2]) === 0) || (parseInt(col[2]) === 6)) {
                        $(ant).addClass("bg-gray");
                    }
                }    
                // Destacar célula
                $('#cel').val('#' + td.attr('id'));
                if ( $(td).hasClass("bg-gray") ) $(td).removeClass("bg-gray");
                $(td).addClass("text-bold");
                $(td).addClass("bg-primary");
                
                // Armazenar referências para pesquisa
                $('#dia').val(dia[1]);
                $('#mes').val(dia[2]);
                $('#ano').val(dia[3]);
                
                pesquisar_agenda_dia();
            }   
            
            function buscar_paciente(id) {
                var codigo = $(id).val().trim();
                if (parseFloat("0" + codigo) !== 0) {
                    carregar_registro_paciente(codigo, function(data){
                        if (parseInt("0" + data.registro.length) === 0) {
                            show_alerta("Prontuário", "Número de Pronturário não localizado.");
                        } else 
                        if (parseInt(data.registro[0].ativo) === 0) {
                            show_alerta("Alerta", "Cadastros de pacientes inativos não podem ser usados.");
                        } else {
                            // Identificação
                            $('#cd_prontuario').val(data.registro[0].prontario);
                            $('#nm_prontuario').val(data.registro[0].nome);
                            $('#cd_paciente_ag').val(zero_esquerda(data.registro[0].prontario, 7));
                            $('#nm_paciente_ag').val(data.registro[0].nome);
                            // Contatos
                            $('#nr_celular_ag').val(data.registro[0].celular);
                            $('#nr_telefone_ag').val(data.registro[0].fone);
                            $('#ds_email_ag').val(data.registro[0].email);
                            // Outras informações
                            $('#cd_convenio_ag').val(data.registro[0].convenio);
                            
                            if ((parseInt($('#st_agenda').val()) === 0) || (parseInt($('#st_agenda').val()) === 11)) ultimo_agendamento();
                        }
                    });
                } else {
                    $('#btn_pesquisar_paciente').trigger('click');
                    
                    $('#ds_filtro_paciente').val("");
                }
            }

            function limpar_endereco_custom(id) {
                $(id).val("");
                $('#end_logradouro').val("");
                $('#end_bairro').val("");
                $('#end_cidade').val("");
                $('#end_estado').val("");
            }
            
            function buscar_endereco_custom(id) {
                var cep = $(id).val().trim();
                if (parseInt("0" + cep) !== 0) {
                    buscar_cep(cep, function(data){
                        if (parseInt("0" + data.registro.length) === 0) {
                            show_alerta("CEP", "Número de Cep não localizado.");
                        } else {
                            // Campos internos de controle para normalização dos dados
                            $('#tp_endereco').val(data.registro[0].tipo);
                            $('#ds_endereco').val(data.registro[0].logradouro);
                            $('#nm_bairro').val(data.registro[0].bairro);

                            $('#cd_estado').val(data.registro[0].estado);
                            $('#cd_cidade').val(data.registro[0].cidade);
                            
                            // Campos editáveis (Customizados)
                            if ($('#end_logradouro').val().trim() === "") $('#end_logradouro').val(data.registro[0].descricao_tipo + " " + data.registro[0].logradouro + ", S/N");
                            $('#end_bairro').val(data.registro[0].bairro);
                            $('#end_cidade').val(data.registro[0].nome_cidade);
                            $('#end_estado').val(data.registro[0].uf);
                        }    
                    });
                }
            }

            function executar_pesquisa_paciente(){
                buscar_pacientes($('#ds_filtro_paciente').val(), '#box-tabela-pacientes', function(){
                    $('#tb-pacientes').DataTable({
                        "paging": true,
                        "pageLength": 10,
                        "lengthChange": false,
                        "searching": false, // Localizar
                        "ordering": true,
                        "info": true,
                        "autoWidth": true,
                        "processing": true,
                        "columns": [
                            { "width": "5px" }, // 0. Prontuário
                            null,               // 1. Nome
                            null,               // 2. Nascimento
                            { "width": "5px" }, // 3. Idade
                            { "width": "5px" }  // 5. Ativo
                        ],
                        "columnDefs": [
                            {"orderable": false, "targets": 0}, // Prontuário
                            {"orderable": false, "targets": 3}, // Idade
                            {"orderable": false, "targets": 4}  // Ativo
                        ],
                        "order": [[1, 'asc']], 
                        "language": {
                                "paginate": {
                                    "first"  : "<<", // Primeira página
                                    "last"   : ">>", // Última página
                                    "next"    : ">", // Próxima página
                                    "previous": "<"  // Página anterior
                                },
                                "aria": {
                                    "sortAscending" : ": ativar para classificação ascendente na coluna",
                                    "sortDescending": ": ativar para classificação descendente na coluna"
                                },
                                "info": "Exibindo _PAGE_ / _PAGES_",
                                "infoEmpty": "Sem dados para exibição",
                                "infoFiltered":   "(Filtrada a partir de _MAX_ registros no total)",
                                "zeroRecords": "Sem registro(s) para exibição", /*"<span class='text-uppercase'>Horários da agenda não liberados para esta data</span>",*/
                                "lengthMenu": "Exibindo _MENU_ registro(s)",
                                "loadingRecords": "Por favor, aguarde - carregando...",
                                "processing": "Processando...",
                                "search": "Localizar:"
                        }
                    });
                    $('#ds_filtro_paciente').focus();
                });
            }
            
            function buscar_valor(id) {
                $(id).val("0,00");
                var tabela        = $('#cd_tabela').val();
                var convenio      = $('#cd_convenio').val();
                var especialidade = $('#cd_especialidade').val();
                var atendimento   = $('#tp_atendimento').val();
                
                buscar_tabela_valor(tabela, convenio, especialidade, atendimento, function(data){
                    $('#vl_servico').val(data.registro[0].valor);
                    $('#cd_tabela').val(data.registro[0].codigo);
                    $('#cd_tabela').select2();
                });
            }
            
            function ultimo_agendamento() {
                var agenda   = $('#id_agenda').val();
                var data     = $('#dt_agenda').val();
                var paciente = $('#cd_paciente_ag').val();
                buscar_ultimo_agendamento(agenda, data, paciente, function(data){
                    $('#cd_tabela').val( get_value(data.registro[0].tabela, 0) );
                    $('#cd_especialidade').val( get_value(data.registro[0].especialidade, 0) );
                    $('#vl_servico').val( get_value(data.registro[0].valor, '0,00') );
                    $('.select2').select2();
                });
            }
            
            function buscar_tabela() {
                var tabela = $('#cd_tabela').val();
                $('#vl_servico').val("0,00");
                
                carregar_registro_tabela_preco(tabela, function(data){
                    try {
                        $('#cd_especialidade').val( get_value(data.registro[0].especialidade, '0') );
                        $('#vl_servico').val( get_value(data.registro[0].valor, '0,00') );
                    } catch(err) {
                        $('#cd_especialidade').val('0');
                        $('#vl_servico').val('0,00');
                    }
                });
            }
            
            function configurar_checked() {
                $('input').iCheck({
                    checkboxClass: 'icheckbox_square-blue',
                    radioClass   : 'iradio_square-blue',
                    increaseArea : '20%' // optional
                });
            }
            
            function configurar_tabela(id) {
                if (typeof($(id)) !== "undefined") {
                    var qt_registros = parseInt('0' + $('#qtde-registros-agend').val());
                    if (qt_registros > 10) {
                        var tam = (qt_registros * 48) + (7 * 48);
                        content_wrapper_sizer_starter(tam);
                    }
                    $(id).DataTable({
                        "paging": true,
                        "pageLength": qt_registros, // Quantidade de registrs para paginação
                        "lengthChange": false,
//                        "lengthMenu": [
//                            [10, 11, 12, 13, 14, 15, 20, 25, 50, -1],
//                            ['10', '11', 12', '13', '14', '15', '20', '25', '50', 'Todos']
//                        ],
                        "searching": true, // Localizar
                        "ordering": true,
                        "info": true,
                        "autoWidth": true,
                        "processing": true,
                        "columns": [
                            { "width": "5px" },   // 0. Horários (junto com a Legendas <span>)
                            null,                 // 1. Paciente
                            null,                 // 2. Contato
                            null,                 // 3. Atendimento
                            null//,                 // 4. Especialidade
                            //{ "width": "5px"  }   // 5. <Botões>
                        ],
                        "columnDefs": [
                            {"orderable": false, "targets": 0}, // Horários
                            {"orderable": false, "targets": 1}, // Paciente
                            {"orderable": false, "targets": 2}, // Contato
                            {"orderable": false, "targets": 3}, // Atendimento
                            {"orderable": false, "targets": 4}//, // Especialidade
                            //{"orderable": false, "targets": 5}  // <Botões>
                        ],
                        "order": [], 
                        "language": {
                                "paginate": {
                                    "first"  : "<<", // Primeira página
                                    "last"   : ">>", // Última página
                                    "next"    : ">", // Próxima página
                                    "previous": "<"  // Página anterior
                                },
                                "aria": {
                                    "sortAscending" : ": ativar para classificação ascendente na coluna",
                                    "sortDescending": ": ativar para classificação descendente na coluna"
                                },
                                "info": "Exibindo _PAGE_ / _PAGES_",
                                "infoEmpty": "Sem dados para exibição",
                                "infoFiltered":   "(Filtrada a partir de _MAX_ registros no total)",
                                "zeroRecords": "Sem registro(s) para exibição", /*"<span class='text-uppercase'>Horários da agenda não liberados para esta data</span>",*/
                                "lengthMenu": "Exibindo _MENU_ registro(s)",
                                "loadingRecords": "Por favor, aguarde - carregando...",
                                "processing": "Processando...",
                                "search": "Localizar:"
                        }
                    });

                    $(id + '_filter input').focus();
                    
//                    // FUNCIONA PERFEITAMENTE
//                    // Filtro (Search) personalizado
//                    if (typeof($('#ds_localizar')) !== "undefined") {
//                        $('#ds_localizar').val("");
//                        $('#ds_localizar').focus();
//                        
//                        var table = $(id).DataTable();
//                        $('#ds_localizar').on('keyup', function () {
//                            table.search( this.value ).draw();
//                        });
//                    }
                }
            }
            
            function ativar_guia(id) {
                if ( $('#tab_1a').hasClass("active") ) $('#tab_1a').removeClass("active");
                if ( $('#tab_2a').hasClass("active") ) $('#tab_2a').removeClass("active");
                if ( $('#tab_3a').hasClass("active") ) $('#tab_3a').removeClass("active");
                if ( $('#tab_4a').hasClass("active") ) $('#tab_4a').removeClass("active");
                
                if ( $('#tab_1').hasClass("active") ) $('#tab_1').removeClass("active");
                if ( $('#tab_2').hasClass("active") ) $('#tab_2').removeClass("active");
                if ( $('#tab_3').hasClass("active") ) $('#tab_3').removeClass("active");
                if ( $('#tab_4').hasClass("active") ) $('#tab_4').removeClass("active");
                
                if (typeof($(id)) !== "undefined") $(id).addClass("active");
                if (typeof($(id + 'a')) !== "undefined") $(id + 'a').addClass("active");
            }    

            function remover_legenda_botao(botao) {
                if (botao !== null) {
                    if (botao.hasClass("text-bold"))  botao.removeClass("text-bold");
                    if (botao.hasClass("bg-yellow"))  botao.removeClass("bg-yellow");
                    if (botao.hasClass("bg-green"))   botao.removeClass("bg-green");
                    if (botao.hasClass("bg-primary")) botao.removeClass("bg-primary");
                    if (botao.hasClass("bg-red"))     botao.removeClass("bg-red");
                    if (botao.hasClass("bg-lime-active")) botao.removeClass("bg-lime-active");
                }
            }
            
            function ativar_guia_paciente(id) {
                if ( $('#tab_11a').hasClass("active") ) $('#tab_11a').removeClass("active");
                if ( $('#tab_12a').hasClass("active") ) $('#tab_12a').removeClass("active");
                
                if ( $('#tab_11').hasClass("active") ) $('#tab_11').removeClass("active");
                if ( $('#tab_12').hasClass("active") ) $('#tab_12').removeClass("active");
                
                if (typeof($(id)) !== "undefined") $(id).addClass("active");
                if (typeof($(id + 'a')) !== "undefined") $(id + 'a').addClass("active");
            }    
            
            function abrir_filtro() { 
                $('#box-legenda').fadeOut(); 
                $('#box-filtro').fadeIn(); 
                $('#btn-configurar-pesquisa').fadeOut(); 
            } 
            
            function abrir_pesquisa() { 
                $('#box-pesquisa').fadeIn(); 
            } 
            
            function abrir_cadastro(handler, situacao) {
                var linha = $('#id_linha').val();
                if ((linha !== '') && (typeof($(linha)) !== 'undefined')) {
                    $(linha).removeClass("text-bold");
                    $(linha).removeClass("bg-gray-light");
                    $(linha).removeClass("bg-gray");
                    
                    var botao = $(linha + ' .btn'); // Pegar botão dentro da TR  
                    botao.removeClass("text-bold");
                }
                var tr = $(handler).closest('tr');
                tr.addClass("text-bold");
                tr.addClass("bg-gray-light");
                $('#id_linha').val( '#' + $(tr).attr('id') );    
                
                var registro = $(tr).attr('id').replace("tr-linha_", ""); // Pegar linha TR
                var botao    = $($('#id_linha').val() + ' .btn');         // Pegar botão dentro da TR  
                botao.addClass("text-bold");
                
                carregar_registro_agendamento(registro, function(data){
                    if (parseInt($('#st_agenda_' + registro).val()) === 9) {
                        show_informe("Informação", "O Horário de agendamento selecionado está bloqueado!");
                    } else {
                        $('#operacao').val("editar");
                        $('#referencia').val(registro);

                        $('#id_agenda').val(data.registro[0].id);
                        $('#cd_agenda').val(zero_esquerda(data.registro[0].codigo, 10));
                        $('#dt_agenda').val(data.registro[0].data);
                        $('#hr_agenda').val(data.registro[0].hora);

                        if ( parseFloat("0" + get_value(data.registro[0].prontuario, 0)) !== 0) { 
                            $('#cd_prontuario').val(data.registro[0].prontuario);
                            $('#nm_prontuario').val(data.registro[0].paciente.replace("...", ""));
                            $('#cd_paciente_ag').val(zero_esquerda(data.registro[0].prontuario, 7)); 
                        } else {
                            $('#cd_prontuario').val("0");
                            $('#nm_prontuario').val("");
                            $('#cd_paciente_ag').val(""); 
                        }

                        if ( get_value(situacao, '0') !== '0') {
                            $('#st_agenda').val( situacao );
                        } else {
                            if ($('#st_agenda_' + registro).val() !== '0') {
                                $('#st_agenda').val( $('#st_agenda_' + registro).val() );
                            } else {
                                $('#st_agenda').val('11'); // Agendar
                            }
                        }

                        $('#nm_paciente_ag').val(data.registro[0].paciente.replace("...", ""));
                        $('#nr_celular_ag').val( get_value(data.registro[0].celular, '') );
                        $('#nr_telefone_ag').val( get_value(data.registro[0].telefone, '') );
                        $('#ds_email_ag').val( get_value(data.registro[0].email, '') );

                        if ( parseInt("0" + get_value(data.registro[0].convenio, 0)) !== 0) { 
                            $('#cd_convenio_ag').val(data.registro[0].convenio); 
                        } else {
                            $('#cd_convenio_ag').val(<?php echo $cd_convenio;?>); 
                        }

                        if ( parseInt("0" + get_value(data.registro[0].atendimento, 0)) !== 0) { 
                            $('#tp_atendimento').val(data.registro[0].atendimento); 
                        } else {
                            $('#tp_atendimento').val("1"); 
                        }

                        $('#cd_especialidade').val( get_value(data.registro[0].especialidade, 0) );
                        
//                        $('#cd_profissional').val( get_value(data.registro[0].profissional, 0) );
                        
                        if ( parseInt("0" + get_value(data.registro[0].profissional, 0)) !== 0) { 
                            $('#cd_profissional').val(data.registro[0].profissional); 
                        } else {
                            $('#cd_profissional').val(<?php echo $cd_profissional;?>); 
                        }

                        $('#cd_tabela').val( get_value(data.registro[0].tabela, 0) );
                        $('#cd_servico').val( get_value(data.registro[0].servico, 0) );
                        $('#vl_servico').val( data.registro[0].valor );

                        $('#ds_observacao').val(data.registro[0].observacao);
                        $('#sn_avulso').prop('checked', (parseInt(data.registro[0].avulso) === 1)).iCheck('update');

                        $('#dt_agenda').prop('readonly', true);
                        $('#hr_agenda').prop('readonly', true);

                        $('#cd_paciente_ag').prop('readonly', parseInt($('#st_agenda_' + registro).val()) > 1);
                        $('#nm_paciente_ag').prop('readonly', parseInt($('#st_agenda_' + registro).val()) > 1);
                        $('#nr_celular_ag').prop('readonly', parseInt($('#st_agenda_' + registro).val()) > 1);
                        $('#nr_telefone_ag').prop('readonly', parseInt($('#st_agenda_' + registro).val()) > 1);
                        $('#ds_email_ag').prop('readonly', parseInt($('#st_agenda_' + registro).val()) > 1);
                        $('#cd_convenio_ag').prop('disabled', parseInt($('#st_agenda_' + registro).val()) > 1);
                        $('#tp_atendimento').prop('disabled', parseInt($('#st_agenda_' + registro).val()) > 1);
                        $('#cd_tabela').prop('disabled',  parseInt($('#st_agenda_' + registro).val()) > 1);
                        $('#vl_servico').prop('readonly', parseInt($('#st_agenda_' + registro).val()) > 1);
                        $('#ds_observacao').prop('readonly', parseInt($('#st_agenda_' + registro).val()) > 2);

                        $('#btn_buscar_paciente').prop('disabled', parseInt($('#st_agenda_' + registro).val()) > 2);
                        $('#btn_form_save').prop('disabled', parseInt($('#st_agenda_' + registro).val()) > 2);
                        $('#salvar_form_paciente').prop('disabled', parseInt($('#st_agenda_' + registro).val()) > 2);

                        $('.select2').select2();
                        ativar_guia('#tab_1');

                        $('#box-calendario').hide();
                        $('#box-filtro').hide();
                        $('#box-pesquisa').hide();
                        $('#box-cadastro').show();

                        $('#box-cadastro').fadeIn(); 

                        $('#cd_paciente_ag').focus();
                    }
                });
            } 
            
            function historico_atendimento(){
                var id_agenda   = $('#id_agenda').val();
                var dt_agenda   = $('#dt_agenda').val();
                var cd_paciente = $('#cd_prontuario').val();
                $('#tab_2').html("<i class='fa fa-spin fa-refresh'></i>&nbsp; Buscando histórico de atendimentos anteriores ao dia " + dt_agenda + ", <strong>aguarde</strong>!");
                carregar_historico_atendimento(id_agenda, dt_agenda, cd_paciente, function(data){
                    $('#tab_2').html(data);
                    if (typeof($('#tb-historico')) !== "undefined") {
                        $('#tb-historico').DataTable({
                            "paging": true,
                            "pageLength": 10, // Quantidade de registrs para paginação
                            "lengthChange": false,
                            "searching": false, // Localizar
                            "ordering": true,
                            "info": true,
                            "autoWidth": true,
                            "processing": true,
                            "columns": [
                                { "width": "5px" },   // 0. Data
                                { "width": "5px" },   // 1. Hora
                                null,                 // 2. Atendimento
                                null,                 // 3. Especialidade
                                null,                 // 4. Médico
                                { "width": "5px"  }   // 5. Situação
                            ],
                            "columnDefs": [
                                {"orderable": false, "targets": 5}  // Situação
                            ],
                            "order": [[0, 'desc']], 
                            "language": {
                                    "paginate": {
                                        "first"  : "<<", // Primeira página
                                        "last"   : ">>", // Última página
                                        "next"    : ">", // Próxima página
                                        "previous": "<"  // Página anterior
                                    },
                                    "aria": {
                                        "sortAscending" : ": ativar para classificação ascendente na coluna",
                                        "sortDescending": ": ativar para classificação descendente na coluna"
                                    },
                                    "info": "Exibindo _PAGE_ / _PAGES_",
                                    "infoEmpty": "Sem dados para exibição",
                                    "infoFiltered":   "(Filtrada a partir de _MAX_ registros no total)",
                                    "zeroRecords": "Sem registro(s) para exibição", /*"<span class='text-uppercase'>Horários da agenda não liberados para esta data</span>",*/
                                    "lengthMenu": "Exibindo _MENU_ registro(s)",
                                    "loadingRecords": "Por favor, aguarde - carregando...",
                                    "processing": "Processando...",
                                    "search": "Localizar:"
                            }
                        });
                    }
                });
            }
            
            function iniciar_agendamento(handler) {
                var tr = $(handler).closest('tr');
                var rf = $(tr).attr('id').replace("tr-linha_", "");
                var st = parseInt("0" + $('#st_agenda_' + rf).val());
                var msg = $('#ds_situacao_' + rf).val();
                if (st === 0) {
                    abrir_cadastro(handler, '11');
                } else {
                    show_informe("Informação", msg);
                }
            }
            
            function confirmar_agendamento(handler) {
                var tr = $(handler).closest('tr');
                var rf = $(tr).attr('id').replace("tr-linha_", "");
                var st = parseInt("0" + $('#st_agenda_' + rf).val());
                var msg = $('#ds_situacao_' + rf).val();
                if (st === 1) {
                    abrir_cadastro(handler, '12'); // Confirmar
                } else {
                    show_informe("Informação", msg);
                }
            }
            
            function marcar_agend_atendido(handler) {
                var tr = $(handler).closest('tr');
                var registro = $(tr).attr('id').replace("tr-linha_", ""); // Pegar linha TR

                var tr_table  = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                var colunas   = tr_table.getElementsByTagName('td');
                var descricao = colunas[1].firstChild.nodeValue;
                var titulo    = "Agendamento";
                var mensagem  = "Deseja marcar como atendido (finalizado) o agendamento de <strong>" + descricao + "</strong>?";
                var st_agenda = parseInt("0" + $('#st_agenda_' + registro).val());
                var situacao  = $('#ds_situacao_' + registro).val();

                if (st_agenda !== 0) {
                    if (st_agenda === 1) {
                        show_informe("Informação", "Apenas agendamentos confirmados podem ser marcados como atendidos.");
                    } else 
                    if ((st_agenda === 3) || (st_agenda === 9)) {
                        show_informe("Informação", situacao);
                    } else {
                        st_agenda = 3; // Marcar como atendido
                        set_situacao_agendamento(registro, st_agenda, titulo, mensagem, function(){
                            // Remover legenda do botão
                            var botao = $('#tr-linha_' + registro + ' .btn'); // Pegar botão dentro da TR  
                            remover_legenda_botao(botao);
                            botao.addClass('text-bold bg-primary');

                            $('#st_agenda_'   + registro).val(st_agenda);
                            $('#ds_situacao_' + registro).val("O atendimento selecionado já foi <strong>finalizado</strong>.");
                        });
                    }
                }
            }
            
            function cancelar_atendimento(handler) {
                var tr = $(handler).closest('tr');
                var registro = $(tr).attr('id').replace("tr-linha_", ""); // Pegar linha TR

                var tr_table  = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                var colunas   = tr_table.getElementsByTagName('td');
                var descricao = colunas[1].firstChild.nodeValue;
                var titulo    = "Cancelar Agendamento";
                var mensagem  = "Deseja cancelar o agendamento de <strong>" + descricao + "</strong>?";
                var st_agenda = parseInt("0" + $('#st_agenda_' + registro).val());
                var situacao  = $('#ds_situacao_' + registro).val();

                if (st_agenda !== 0) {
                    if (st_agenda === 3) {
                        show_informe("Informação", "Agendamentos finalizados não podem mais ser cancelados.");
                    } else 
                    if ((st_agenda === 4) || (st_agenda === 9)) {
                        show_informe("Informação", situacao);
                    } else {
                        st_agenda = 4; // Cancelar
                        set_situacao_agendamento(registro, st_agenda, titulo, mensagem, function(){
                            // Remover legenda do botão
                            var botao = $('#tr-linha_' + registro + ' .btn'); // Pegar botão dentro da TR  
                            remover_legenda_botao(botao);
                            botao.addClass('text-bold bg-red');

                            $('#st_agenda_'   + registro).val(st_agenda);
                            $('#ds_situacao_' + registro).val("O atendimento selecionado está <strong>cancelado</strong>.");
                        });
                    }
                }
            }
            
            function reagendar_atendimento(handler) {
                var tr = $(handler).closest('tr');
                var registro = $(tr).attr('id').replace("tr-linha_", ""); // Pegar linha TR

                var tr_table  = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                var colunas   = tr_table.getElementsByTagName('td');
                var descricao = colunas[1].firstChild.nodeValue;

                show_alerta("Alerta", "Opção ainda não disponível nesta versão do sistema!");
            }
            
            function imprimir_agendamentos() {
                var empresa = $('#empresaID').val();
                var dia = $('#dia').val();
                var mes = $('#mes').val();
                var ano = $('#ano').val();
                
                window.open("/gcm/views/print/agenda.php?ep=" + empresa + "&dia=" + dia + "&mes=" + mes + "&ano=" + ano, '_blank');
            }
            
            function imprimir_atendimento(handler) {
                var tr = $(handler).closest('tr');
                var registro = $(tr).attr('id').replace("tr-linha_", ""); // Pegar linha TR
                var empresa  = $('#empresaID').val();

                var tr_table  = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                var colunas   = tr_table.getElementsByTagName('td');
                var descricao = colunas[1].firstChild.nodeValue;

                window.open("/gcm/views/print/fatura.php?ag={" + registro + "}&ep=" + empresa + "&pac=" + descricao, '_blank');
            }
            
            function bloquear_atendimento(handler) {
                var tr = $(handler).closest('tr');
                var registro = $(tr).attr('id').replace("tr-linha_", ""); // Pegar linha TR

                var tr_table  = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                var colunas   = tr_table.getElementsByTagName('td');
                var descricao = colunas[1].firstChild.nodeValue;
                var titulo    = "Bloquear Agendamento";
                var mensagem  = "Deseja bloquear agendamento no horário selecionado?";
                var st_agenda = parseInt("0" + $('#st_agenda_' + registro).val());
                var situacao  = $('#ds_situacao_' + registro).val();

                if (st_agenda === 9) {
                    show_informe("Informação", situacao);
                } else 
                if (st_agenda !== 0) {
                    show_informe("Informação", "Apenas Horários livres de agendamento podem ser bloqueados.");
                } else {
                    st_agenda = 9; // Bloquear
                    set_situacao_agendamento(registro, st_agenda, titulo, mensagem, function(){
                        // Remover legenda do botão
                        var botao = $('#tr-linha_' + registro + ' .btn'); // Pegar botão dentro da TR  
                        remover_legenda_botao(botao);
                        botao.addClass('text-bold bg-lime-active');

                        colunas[1].firstChild.nodeValue = '... BLOQUEADO';
                        colunas[2].firstChild.nodeValue = 'BLOQUEADO';
                        colunas[3].firstChild.nodeValue = 'BLOQUEADO';
                        colunas[4].firstChild.nodeValue = 'BLOQUEADO';
                        
                        $('#st_agenda_'   + registro).val(st_agenda);
                        $('#ds_situacao_' + registro).val("O horário selecionado está <strong>bloqueado</strong>..");
                    });
                }
            }
            
            function selecinar_paciente(handler, id) {
                // Descarcar registro selecionado
                var linha = $('#id_linha_paciente').val();
                if ((linha !== '') && (typeof($(linha)) !== 'undefined')) {
                    $(linha).removeClass("text-bold");
                    $(linha).removeClass("bg-gray-light");
                    $(linha).removeClass("bg-gray");
                }
                // Selecionar novo registro
                var tr = $(handler).closest('tr');
                tr.addClass("text-bold");
                tr.addClass("bg-gray-light");
                $('#id_linha_paciente').val( '#' + $(tr).attr('id') );
                
                var referencia  = id.replace("reg-paciente_", "");
                var cd_paciente = $('#cd_paciente_' + referencia).val();
                var nm_paciente = $('#nm_paciente_' + referencia).val();
                var nr_celular  = $('#nr_celular_'  + referencia).val();
                var nr_telefone = $('#nr_telefone_' + referencia).val();
                var ds_email    = $('#ds_email_'    + referencia).val();
                var sn_ativo    = $('#sn_ativo_'    + referencia).val();
                
                if (parseInt(sn_ativo) === 0) {
                    show_alerta("Alerta", "Cadastros de pacientes inativos não podem ser usados.");
                } else {
                    $('#cd_prontuario').val( cd_paciente );
                    $('#nm_prontuario').val( nm_paciente );
                    $('#cd_paciente_ag').val( zero_esquerda(cd_paciente, 7) );
                    $('#nm_paciente_ag').val(nm_paciente);
                    $('#nr_celular_ag').val(nr_celular);
                    $('#nr_telefone_ag').val(nr_telefone);
                    $('#ds_email_ag').val(ds_email);
                    
                    $('#find_close').trigger('click');
                    if ((parseInt($('#st_agenda').val()) === 0) || (parseInt($('#st_agenda').val()) === 11)) ultimo_agendamento();
                }
            } 
            
            function abrir_cadastro_paciente() {
                var registro = "0" + $('#cd_prontuario').val();
                carregar_registro_paciente(registro, function(data){
                    if (get_value(data.registro[0].prontario, '0') === '0') {
                        $('#operacao').val("inserir");
                    } else {
                        $('#operacao').val("editar");
                    }
                    
                    // Identificação
                    $('#cd_paciente').val( zero_esquerda(get_value(data.registro[0].prontario, '0'), 7) );
                    $('#nm_paciente').val( get_value(data.registro[0].nome, $('#nm_paciente_ag').val()) );
                    $('#dt_nascimento').val(data.registro[0].nascimento);
                    $('#tp_sexo').val( get_value(data.registro[0].sexo, '0') );
                    $('#cd_profissao').val( get_value(data.registro[0].codigo_profissao, '0') );
                    $('#ds_profissao').val( data.registro[0].profissao );
                    $('#nr_rg').val(data.registro[0].rg);
                    $('#ds_orgao_rg').val(data.registro[0].orgao);
                    $('#dt_emissao_rg').val(data.registro[0].emissao);
                    $('#nr_cpf').val(data.registro[0].cpf);
                    $('#nm_acompanhante').val(data.registro[0].acompanhante);
                    $('#nm_pai').val(data.registro[0].pai);
                    $('#nm_mae').val(data.registro[0].mae);
                    
                    // Endereço
                    if (typeof($('#end_logradouro')) !== "undefined") {
                        // Customizado
                        $('#cd_estado').val( get_value(data.registro[0].estado, '0') );
                        $('#cd_cidade').val( get_value(data.registro[0].cidade, '0') );
                        $('#end_logradouro').val(data.registro[0].end_logradouro);
                        $('#end_bairro').val(data.registro[0].end_bairro);
                        $('#end_estado').val(data.registro[0].end_estado);
                        $('#end_cidade').val(data.registro[0].end_cidade);
                    } else {
                        // Normalização
                        if ( get_value(data.registro[0].estado, 0) !== parseInt("0" + $('#cd_estado').val()) ) {
                            $('#cd_estado').val( get_value(data.registro[0].estado, 0) );
                        }
                        if ( get_value(data.registro[0].cidade, 0) !== parseInt("0" + $('#cd_cidade').val()) ) {
                            listar_cidades_cadastro('cidade_' + get_value(data.registro[0].cidade, 0));
                        }    
                    }
                    $('#tp_endereco').val( get_value(data.registro[0].tipo, '0') );
                    $('#ds_endereco').val(data.registro[0].endereco);
                    $('#nr_endereco').val(data.registro[0].numero);
                    $('#ds_complemento').val(data.registro[0].complemento);
                    $('#nm_bairro').val(data.registro[0].bairro);
                    $('#nr_cep').val(data.registro[0].cep);
                    
                    // Contatos
                    $('#nr_telefone').val( get_value(data.registro[0].fone, $('#nr_telefone_ag').val()) );
                    $('#nr_celular').val( get_value(data.registro[0].celular, $('#nr_celular_ag').val()) );
                    $('#ds_contatos').val(data.registro[0].contatos);
                    $('#ds_email').val( get_value(data.registro[0].email, $('#ds_email_ag').val()) );
                    // Outras informações
                    $('#cd_convenio').val( get_value(data.registro[0].convenio, '<?php echo $cd_convenio;?>') );
                    $('#nr_matricula').val(data.registro[0].matricula);
                    $('#nm_indicacao').val(data.registro[0].indicacao);
                    $('#ds_alergias').val(data.registro[0].alergias);
                    $('#ds_observacoes').val(data.registro[0].observacoes);
                    //$('#sn_ativo').prop('checked', (parseInt(data.registro[0].ativo) === 1)).iCheck('update');
                    $('#sn_ativo').prop('checked', true).iCheck('update');
                    
                    $('#modal-cadastro_paciente .select2').select2();
                    ativar_guia_paciente('#tab_11');
                    
                    $('#btn_cadastrar_paciente').trigger('click');
                });
            }
            
            function novo_cadastro(usuario) {
                var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso = "0100";
                get_allow_user(usuario, rotina, acesso, function(){
                    $('#operacao').val("inserir");
                    $('#referencia').val("");
                    
                    $('#st_agenda').val("11"); // Agendar
                    $('#cd_prontuario').val("0");
                    $('#nm_prontuario').val("");
                    $('#cd_paciente_ag').val("");
                    $('#nm_paciente_ag').val("");
                    $('#nr_celular_ag').val("");
                    $('#nr_telefone_ag').val("");
                    $('#ds_email_ag').val("");
                    $('#cd_convenio_ag').val("<?php echo $cd_convenio;?>");
                    $('#tp_atendimento').val("1");
                    $('#cd_especialidade').val("0");
                    $('#cd_profissional').val("<?php echo $cd_profissional;?>");
                    $('#cd_tabela').val("0");
                    $('#cd_servico').val("0");
                    $('#vl_servico').val("0,00");
                    $('#sn_avulso').prop('checked', true).iCheck('update');
                    
                    $('#box-calendario').hide();
                    $('#box-filtro').hide();
                    $('#box-pesquisa').hide();
                    $('#box-cadastro').show();

                    $('#box-cadastro').fadeIn(); 
                });
            }
                
            function fechar_filtro(pesquisar) {
                $('#btn-configurar-pesquisa').fadeIn();
                $('#box-filtro').hide();
                $('#box-legenda').show();
                if (pesquisar === true) pesquisar_agenda_dia();
            } 
            
            function fechar_pesquisa() { 
                $('#box-pesquisa').fadeOut(); 
            } 
            
            function fechar_cadastro() {
                $('#box-cadastro').fadeOut(); 
            } 
            
            function voltar_pesquisa() {
                $('#box-calendario').show();
                $('#box-pesquisa').show();
                $('#box-cadastro').hide();
                
                // Setar o foco no botão da linha para fazer com que a página não perca o elemento de "vista"
                var botao = $('#tr-linha_' + $('#referencia').val() + ' .btn'); 
                if (typeof($(botao)) !== "undefined") {
                    botao.focus();
                }    
            }
                
            function salvar_cadastro() {
                if ( $('#nm_prontuario').val().trim() !== $('#nm_paciente_ag').val().trim() ) {
                    $('#cd_prontuario').val("0");
                    $('#nm_prontuario').val("");
                }

                var registro   = $('#referencia').val();
                var requerido  = "";
                var st_agenda  = parseInt("0" + $('#st_agenda').val());
                var prontuario = parseFloat("0" + $('#cd_prontuario').val());

                if (st_agenda ===  0) requerido += "<li>Situação</li>";
                
                if (($('#dt_agenda').val() === "") || ($('#hr_agenda').val() === "")) requerido += "<li>Data/Hora</li>";
                if ((st_agenda === 11) && ($('#nm_paciente_ag').val() === "")) requerido += "<li>Paciente</li>";
                if ((st_agenda === 11) && ($('#nr_celular_ag').val()  === "")) requerido += "<li>Contato (Número do Celular)</li>";
                if ($('#cd_convenio_ag').val() === "0") requerido += "<li>Categoria</li>";
                if ($('#tp_atendimento').val() === "0") requerido += "<li>Atendimento</li>";
                if ($('#cd_tabela').val() === "0") requerido += "<li>Serviço/Especialidade</li>";

                if (requerido !== "") {
                    show_campos_requeridos("Alerta", "Agendar Atendimento", requerido);
                } else {    
                    if (((st_agenda === 2) || (st_agenda === 12)) && (prontuario === 0)) { // 2. Confirmar / Confirmado
                        show_alerta("Confirmar Agendamento", "O agendamento não está vinculado ao cadastro do paciente.<br>Caso este não esteja cadastrado, favor efetue seu cadastro para confirmar o agendamento.");
                    } else {
                        if ((st_agenda === 0) || (st_agenda === 11)) {
                            $('#st_agenda').val("1"); // Mudar para situação "1. Agendado"
                            $('#st_agenda').select2();
                        } else
                        if (st_agenda === 12) {
                            $('#st_agenda').val("2"); // Mudar para situação "2. Confirmado"
                            $('#st_agenda').select2();
                        }
                        
                        salvar_registro_agendamento(registro, function(data){
                            var referencia = data.registro[0].referencia;
                            var legenda    = data.registro[0].tag_legenda;
                            var linha      = "#tr-linha_" + referencia;
                            $('#id_linha').val(linha);
                            $('#st_agenda_'   + referencia).val(data.registro[0].situacao);
                            $('#ds_situacao_' + referencia).val(data.registro[0].tag_situacao);

                            // Destacar linha na tabela
                            if ((linha !== '') && (typeof($(linha)) !== 'undefined')) {
                                $(linha).removeClass("bg-gray-light");
                                $(linha).addClass("text-bold");
                                $(linha).addClass("bg-gray");

                                voltar_pesquisa(); 
                            }
                            
                            // Montar legenda
                            var botao = $($('#id_linha').val() + ' .btn'); // Pegar botão dentro da TR 
                            remover_legenda_botao(botao);
                            botao.addClass(legenda);
                        });
                    }
                }
            }
            
            function salvar_cadastro_paciente() {
                var registro  = $('#cd_paciente').val();
                var requedido = "";

                if ($('#nr_cpf').val() === "") $('#nr_cpf').val("000.000.000-00");
                
                if ($('#nm_paciente').val() === "")   requedido += "<li>Nome do paciente</li>";
                if (!validar_data($('#dt_nascimento').val())) requedido += "<li>Data de nascimento</li>";
                if ($('#tp_sexo').val() === "0")      requedido += "<li>Sexo</li>";
                if (($('#nr_rg').val() !== "") && ($('#ds_orgao_rg').val() === ""))         requedido += "<li>Orgão/UF do RG</li>";
                //if (($('#nr_rg').val() !== "") && !validar_data($('#dt_emissao_rg').val())) requedido += "<li>Data de emissão do RG</li>";
                if ($('#nr_cpf').val() === "")        requedido += "<li>CPF</li>";
                if (($('#ds_endereco').val() !== "")  && ($('#tp_endereco').val() === "0")) requedido += "<li>Tipo do endereço (Rua, Trav., Entrada, ETC.)</li>";
                if (($('#tp_endereco').val() !== "0") && ($('#ds_endereco').val() === ""))  requedido += "<li>Descrição do endereço</li>";
                if (($('#ds_endereco').val() !== "")  && ($('#nr_endereco').val() === ""))  requedido += "<li>Número do endereço</li>";
                if ($('#cd_convenio').val() === "0")  requedido += "<li>Convêvio</li>";

                if (requedido !== "") {
                    show_campos_requeridos("Alerta", "Cadastro do Paciente", requedido);
                } else {    
                    salvar_registro_paciente(registro, function(data){
                        $('#cd_prontuario').val( data.registro[0].prontuario );
                        $('#nm_prontuario').val( data.registro[0].nome );
                        $('#cd_paciente_ag').val( zero_esquerda($('#cd_prontuario').val(), 7) );
                        $('#nm_paciente_ag').val( $('#nm_prontuario').val() );
                        $('#nr_celular_ag').val( data.registro[0].celular );
                        $('#nr_telefone_ag').val( data.registro[0].telefone );
                        $('#ds_email_ag').val( data.registro[0].email );
                        
                        $('#fechar_form_paciente').trigger('click');
                    });
                }
            }
            
            function excluir_registro(handler) {
                var usuario = "user_<?php echo $user->getCodigo();?>";
                var rotina  = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso  = "0100";
                get_allow_user(usuario, rotina, acesso, function(){
                    var tr = $(handler).closest('tr');
                    var registro = $(tr).attr('id').replace("tr-linha_", ""); // Pegar linha TR
                    
                    var tr_table  = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                    var colunas   = tr_table.getElementsByTagName('td');
                    var descricao = colunas[1].firstChild.nodeValue;
                    var st_agenda = parseInt("0" + $('#st_agenda_' + registro).val());
                    var situacao  = $('#ds_situacao_' + registro).val();
                    
                    if (st_agenda !== 0) {
                        if (st_agenda > 1) {
                            show_alerta("Alerta", "Não foi possível a exclusão.<br>" + situacao);
                        } else {
                            excluir_agendamento(registro, descricao, function(){
                                // Remover legenda do botão
                                var botao = $('#tr-linha_' + registro + ' .btn'); // Pegar botão dentro da TR  
                                remover_legenda_botao(botao);
                                
                                colunas[1].firstChild.nodeValue = '...';
                                colunas[2].firstChild.nodeValue = '...';
                                colunas[3].firstChild.nodeValue = '...';
                                colunas[4].firstChild.nodeValue = '...';
                                
                                $('#cd_prontuario').val("0");
                                $('#nm_prontuario').val("");
                                $('#st_agenda_'   + registro).val("0");
                                $('#ds_situacao_' + registro).val("Horário sem atendimento agendado.");
                                // RemoveTableRow(tr_table);
                            });
                        }
                    }
                });
            }    
            
            function inserir_exame() {
                $('#cd_paciente').val( $('#cd_paciente_ag').val() );
                
                var usuario = "user_<?php echo $user->getCodigo();?>";
                var rotina  = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso  = "0100";
                get_allow_user(usuario, rotina, acesso, function() {
                    var referencia = $('#referencia').val();
                    var descricao  = $('#id_exame option:selected').text();
                    var paciente   = parseFloat("0" + $('#cd_paciente').val());
                    if (paciente === 0) {
                        show_alerta("Novo Exame", "Paciente não cadastrado ou não informado.");
                    } else
                    if ($('#id_exame').val() === '') {
                        show_alerta("Novo Exame", "Seleciono o exame desejado para este seja inserido no controle de exames do paciente.");
                    } else {
                        inserir_exame_atendimento(referencia, descricao, function(){
                            carregar_exames();
                        });
                    }
                });
            }
            
            function inserir_evolucao() {
                $('#cd_paciente').val( $('#cd_paciente_ag').val() );
                
                var usuario = "user_<?php echo $user->getCodigo();?>";
                var rotina  = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso  = "0100";
                get_allow_user(usuario, rotina, acesso, function() {
                    var referencia = $('#referencia').val();
                    var descricao  = $('#id_evolucao option:selected').text();
                    var paciente   = parseFloat("0" + $('#cd_paciente').val());
                    if (paciente === 0) {
                        show_alerta("Nova Evolução de Medida", "Paciente não cadastrado ou não informado.");
                    } else
                    if ($('#id_evolucao').val() === '') {
                        show_alerta("Nova Evolução de Medida", "Seleciono a evolução desejada para esta seja inserida no controle de evoluções de medidas do paciente.");
                    } else {
                        inserir_evolucao_atendimento(referencia, descricao, function(){
                            carregar_evolucoes();
                        });
                    }
                });
            }
            
            function carregar_exames() {
                $('#cd_paciente').val( $('#cd_paciente_ag').val() );
                
                var referencia = $('#referencia').val();
                var paciente   = parseFloat("0" + $('#cd_paciente').val());
                $('#id_atendimento').val(referencia);
                $('#dt_atendimento').val($('#dt_agenda').val());
                $('#box-tabela_exames').html("<p class='text-center'><br><i class='fa fa-spin fa-refresh'></i>&nbsp; Buscando resultados de exames, <strong>aguarde</strong>!<br></p>");
                if (paciente === 0) {
                    $('#box-tabela_exames').html("<p style='font-size: 3px;'>&nbsp;</p><br><p><strong>Paciente não cadastrado ou não informado.</strong></p>");
                    show_alerta("Carregar Exames", "Paciente não cadastrado ou não informado.");
                } else {
                    carregar_controle_exames(referencia, function(retorno){
                        $('#box-tabela_exames').html(retorno);
                        $('[data-mask]').inputmask();
                    });
                }
            }

            function carregar_evolucoes() {
                $('#cd_paciente').val( $('#cd_paciente_ag').val() );
                
                var referencia = $('#referencia').val();
                var paciente   = parseFloat("0" + $('#cd_paciente').val());
                $('#id_atendimento').val(referencia);
                $('#dt_atendimento').val($('#dt_agenda').val());
                $('#box-tabela_exames').html("<p class='text-center'><br><i class='fa fa-spin fa-refresh'></i>&nbsp; Buscando resultados de exames, <strong>aguarde</strong>!<br></p>");
                if (paciente === 0) {
                    $('#box-tabela_evolucoes').html("<p style='font-size: 3px;'>&nbsp;</p><br><p><strong>Paciente não cadastrado ou não informado.</strong></p>");
                    show_alerta("Carregar Evoluções", "Paciente não cadastrado ou não informado.");
                } else {
                    carregar_controle_evolucoes(referencia, function(retorno){
                        $('#box-tabela_evolucoes').html(retorno);
                        $('[data-mask]').inputmask();
                    });
                }
            }

            function gravar_resultados_exames() {
                $('#cd_paciente').val( $('#cd_paciente_ag').val() );
                
                var registro = $('#referencia').val();
                var paciente = parseFloat("0" + $('#cd_paciente').val());
                //var cd_atendimento = parseFloat("0" + $('#cd_atendimento').val());

                if (paciente === 0) {
                    show_alerta("Salvar Exames", "Paciente não cadastrado ou não informado.");
                } else {
                    if (!validar_data($('#dt_exame').val())) {
                        show_alerta("Salvar Exames", "Data de realização do(s) exame(s) não informada");
                    } else {
                        // Varrer a página para recuperar os valores lançados
                        var qt_controle_exames = parseInt("0" + $('#qt_controle_exames').val());
                        var ids_exames = "#";
                        var vls_exames = "#";
                        for (var i = 0; i < qt_controle_exames; i++) {
                            if (typeof($('#ref_controle_exame_' + i)) !== "undefined") {
                                ids_exames += "||" + $('#ref_controle_exame_' + i).val();
                                vls_exames += "||" + $('#vl_exame_texto_' + i).val();
                            }
                        }

                        ids_exames = ids_exames.replace("#||", "");
                        vls_exames = vls_exames.replace("#||", "");

                        // Salvar os resultados lançados dos exames
                        if ((ids_exames !== '#') && (vls_exames !== '#')) {
                            salvar_resultados_exames(ids_exames, vls_exames, function(){
                                carregar_exames();
                                show_informe("Salvar Exames", "Resultado(s) gravado(s) com sucesso.");
                            });
                        } else {
                            show_alerta("Salvar Exames", "Favor informe o(s) resultado(s) do(s) exame(s)");
                        }
                    }
                }
            }
            
            function gravar_resultados_evolucoes() {
                $('#cd_paciente').val( $('#cd_paciente_ag').val() );
                
                var registro = $('#referencia').val();
                var paciente = parseFloat("0" + $('#cd_paciente').val());
                //var cd_atendimento = parseFloat("0" + $('#cd_atendimento').val());

                if (paciente === 0) {
                    show_alerta("Salvar Evoluções", "Paciente não cadastrado ou não informado.");
                } else {
                    if (!validar_data($('#dt_evolucao').val())) {
                        show_alerta("Salvar Evoluções", "Data de medição da(s) evolução(ões) não informada");
                    } else {
                        // Varrer a página para recuperar os valores lançados
                        var qt_controle_evolucoes = parseInt("0" + $('#qt_controle_evolucoes').val());
                        var ids_evolucoes = "#";
                        var vls_evolucoes = "#";
                        for (var i = 0; i < qt_controle_evolucoes; i++) {
                            if (typeof($('#ref_controle_evolucao_' + i)) !== "undefined") {
                                ids_evolucoes += "||" + $('#ref_controle_evolucao_' + i).val();
                                vls_evolucoes += "||" + $('#vl_evolucao_texto_' + i).val();
                            }
                        }

                        ids_evolucoes = ids_evolucoes.replace("#||", "");
                        vls_evolucoes = vls_evolucoes.replace("#||", "");

                        // Salvar os resultados lançados dos evolucoes
                        if ((ids_evolucoes !== '#') && (vls_evolucoes !== '#')) {
                            salvar_resultados_evolucoes(ids_evolucoes, vls_evolucoes, function(){
                                carregar_evolucoes();
                                show_informe("Salvar Evoluções", "Resultado(s) gravado(s) com sucesso.");
                            });
                        } else {
                            show_alerta("Salvar Evoluções", "Favor informe o(s) resultado(s) da(s) evolução(ões)");
                        }
                    }
                }
            }
            
            function imprimir_controle_exames(handler) {
                $('#cd_paciente').val( $('#cd_paciente_ag').val() );
                
                var paciente = parseFloat("0" + $('#cd_paciente').val());
                var qt_controle_exames = parseInt("0" + $('#qt_controle_exames').val());
                if (paciente === 0) {
                    show_alerta("Imprimir Controle de Exames", "Paciente não cadastrado ou não informado.");
                } else {
                    if (qt_controle_exames === 0) {
                        show_alerta("Controle de Exames", "Não existe exames relacionados para impressão.");
                    } else {
                        var registro = $('#referencia').val();
                        var empresa  = $('#empresaID').val();

                        if (handler !== null) {
                            var tr = $(handler).closest('tr');
                            registro = $(tr).attr('id').replace("tr-linha_", ""); // Pegar linha TR
                        }

                        //var tr_table   = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                        //var colunas    = tr_table.getElementsByTagName('td');
                        var prontuario = $('#cd_paciente').val(); //colunas[1].firstChild.nodeValue;

                        window.open("/gcm/views/print/controle_exame.php?at={" + registro + "}&ep=" + empresa + "&pac=" + prontuario, '_blank');
                    }
                }
            }
            
            function imprimir_controle_evolucoes(handler) {
                $('#cd_paciente').val( $('#cd_paciente_ag').val() );
                
                var paciente = parseFloat("0" + $('#cd_paciente').val());
                var qt_controle_evolucoes = parseInt("0" + $('#qt_controle_evolucoes').val());
                if (paciente === 0) {
                    show_alerta("Imprimir Controle de Evoluções", "Paciente não cadastrado ou não informado.");
                } else {
                    if (qt_controle_evolucoes === 0) {
                        show_alerta("Controle de Evoluções", "Não existem evoluções relacionados para impressão.");
                    } else {
                        var registro = $('#referencia').val();
                        var empresa  = $('#empresaID').val();

                        if (handler !== null) {
                            var tr = $(handler).closest('tr');
                            registro = $(tr).attr('id').replace("tr-linha_", ""); // Pegar linha TR
                        }

                        //var tr_table   = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                        //var colunas    = tr_table.getElementsByTagName('td');
                        var prontuario = $('#cd_paciente').val(); //colunas[1].firstChild.nodeValue;

                        window.open("/gcm/views/print/controle_evolucao.php?at={" + registro + "}&ep=" + empresa + "&pac=" + prontuario, '_blank');
                    }
                }
            }
        </script>
  </body>
</html>
