<!DOCTYPE html>
<?php
    // Buscar Número de Registro ANS pelo CNPJ (Sem formatação)
    // http://www.ans.gov.br/planos-de-saude-e-operadoras/informacoes-e-avaliacoes-de-operadoras/consultar-dados

    ini_set('default_charset', 'UTF-8');
    ini_set('display_errors', true);
    error_reporting(E_ALL);
    date_default_timezone_set('America/Belem');
    
    require '../dist/php/constantes.php';
    require '../dist/dao/conexao.php';
    require '../dist/php/usuario.php';

    $cd_profissional = 0;
    $is_medico       = false;
    
    // Carregar as configurações de filtro do objeto "cookie"
    session_start();
    $user = new Usuario();
    if ( isset($_SESSION['user']) ) {
        $user = unserialize($_SESSION['user']);
        $cd_profissional = $_SESSION['profissional'];
        $is_medico       = $_SESSION['is_medico'];
    } else {
        header('location: ../index.php');
        exit;
    }
    
    $id_estacao  = md5($_SERVER["REMOTE_ADDR"]);
    $cd_convenio = "0";
    $dt_hoje     = date('d/m/Y');
   
    $pdo = Conexao::getConnection();
    
    // Carregar dados da empresa
    $qry = $pdo->query(
          "Select  "
        . "    e.* "
        . "  , p.cd_profissional  "
        . "  , p.nm_profissional  "
        . "  , p.nm_apresentacao  "
        . "  , p.ds_conselho      "
        . "  , p.ft_assinatura    "
        . "  , p.id_usuario       "
        . "from dbo.sys_empresa e "
        . "  inner join dbo.sys_usuario_empresa u on (u.id_empresa = e.id_empresa and u.id_usuario = '" . $user->getCodigo() . "')"
        . "  left  join dbo.tbl_profissional    p on (p.id_empresa = u.id_empresa and p.id_usuario = u.id_usuario)");
    $dados   = $qry->fetchAll(PDO::FETCH_ASSOC);
    $empresa = null;
    foreach($dados as $item) {
        $empresa = $item;
    }
    
    // Identificar o convênio
    $qry = $pdo->query("Select min(cd_convenio) as cd_convenio from dbo.tbl_convenio where sn_ativo = 1");
    if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
        $cd_convenio = $obj->cd_convenio;
    }

    // Tabela de Cobrança (Tipo de Atendimento)
    $qry = $pdo->query(
          "Select "
        . "    t.* "
        . "from dbo.tbl_tabela_cobranca t "
        . "  inner join dbo.tbl_profissional_especialidade e on (e.cd_profissional = " . ($cd_profissional !== 0 ? $cd_profissional : $empresa['cd_profissional'] ) ." and e.cd_especialidade = t.cd_especialidade) "
        . "where (t.id_empresa = '{$empresa['id_empresa']}') "
        . "  and (t.sn_ativo = 1) ");
    $tabelas = $qry->fetchAll(PDO::FETCH_ASSOC);;

    // Tabela de Exames
    $qry = $pdo->query(
          "Select * "
        . "from dbo.tbl_exame e "
        . "where (e.sn_ativo = 1) "
        . "  and (e.id_empresa = '{$empresa['id_empresa']}')"
        . "order by e.nm_exame");
    $exames = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    // Tabela de Evoluções de Medidas
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
    
    // Fechar conexão PDO
    unset($qry);
    unset($pdo);
    
    $qtde = 10;
    $tipo = 1;
    $file = "../logs/cookies/atendimento_hoje_" . sha1($user->getCodigo()) . ".json";
    if (file_exists($file)) {
        $file_cookie = file_get_contents($file);
        $json = json_decode($file_cookie);
        if (isset($json->filtro[0])) {
            $qtde = (int)$json->filtro[0]->qt_registro;
            $tipo = (int)$json->filtro[0]->tp_filtro;
        }
    }
    
    // Forçar a renovação dos arquivos no cache do navegador
    $versao = "v=". time();
?>
<html>
    <body class="hold-transition skin-blue sidebar-mini">

        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h1>
            Atendimentos
            <small>Relação de pacientes agendados para atendimento</small>
            <input type="hidden" id="estacaoID" value="<?php echo $id_estacao;?>">
          </h1>
          <ol class="breadcrumb">
              <li><a href="#"><i class="fa fa-home"></i> Home</a></li>
              <li><a href="#">Atendimentos</a></li>
              <li class="active" id="page-click" onclick="preventDefault()">Hoje</li>
          </ol>
        </section>

        <!-- Main content -->
        <section class="content">

          <!-- Painel de Pesquisa -->
          <div class="modal fade" id="modal-default_pesquisa">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Configurar Filtro</h4>
                    </div>
                    <div class="modal-body">
                        <div class="box-body form-horizontal">
                            <div class="col-md-12">
                                <div class="form-group" style="margin: 2px;">
                                    <label for="dt_hoje" class="col-sm-3 control-label padding-label">Data</label>
                                    <div class="col-sm-4 padding-field">
                                        <div class="input-group">
                                            <div class="input-group-addon">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                            <input type="hidden" id="cd_profissional" value="<?php echo $empresa['cd_profissional'];?>">
                                            <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask id="dt_hoje" value="<?php echo $dt_hoje;?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group" style="margin: 2px;">
                                    <label for="cd_tipo_filtro" class="col-sm-3 control-label padding-label">Agendamentos</label>
                                    <div class="col-sm-9 padding-field">
                                        <select class="form-control select2"  id="cd_tipo_filtro" style="width: 100%;">
                                            <option value='0'>Todos</option>
                                            <option value='1'>Apenas Confirmados</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group" style="margin: 2px;">
                                    <!--<button class='btn btn-primary' id='btn_sumit_pesquisa' name='btn_sumit_pesquisa' onclick='fechar_filtro(true)' title="Executar pesquisa"><i class='fa fa-search'></i></button>-->
                                    <label for="qtde-registros-atend" class="col-sm-3 control-label padding-label">Registros</label>
                                    <div class="col-sm-9 padding-field">
                                        <select class="form-control select2"  id="qtde-registros-atend" style="width: 70px;">
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
                                            <option value='50'>50</option>
                                        </select>
                                        <span>&nbsp; Quantidade de registros por paginação</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default pull-left" data-dismiss="modal" id="default_close_pesquisa">Fechar</button>
                        <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="fechar_filtro(true)">Confirmar</button>
                    </div>
                </div>
            </div>
          </div>

          <!-- Painel Novo Atendimento -->
          <div class="modal fade" id="modal-default_novo_atendimento">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Atendimento Avulso</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <div class="row">
                            <input type="hidden" id="cd_profissional_avulso" value="<?php echo $empresa['cd_profissional'];?>">
                            <input type="hidden" id="cd_convenio_avulso" value="<?php echo $cd_convenio;?>">
                            
                            <div class="form-group" style="margin: 2px;">
                                <label for="dt_agenda_avulso" class="col-sm-3 control-label padding-label">Data/Hora</label>
                                <div class="col-sm-6 padding-field">
                                    <div class="input-group">
                                        <div class="input-group-addon">
                                            <i class="fa fa-calendar"></i>
                                        </div>
                                        <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask id="dt_agenda_avulso" value="00/00/0000" readonly>
                                        <div class="input-group-addon">
                                            <i class="fa fa-clock-o"></i>
                                        </div>
                                        <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'hh:mi'" data-mask id="hr_agenda_avulso" value="00:00:00" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group" style="margin: 2px;">
                                <label for="cd_tabela_avulso" class="col-sm-3 control-label padding-label">Atendimento</label>
                                <div class="col-sm-8 padding-field">
                                    <select class='form-control select2'  id='cd_tabela_avulso' style='width: 100%;'>
                                        <?php if ($is_medico === false): ?>
                                        <option value='0'>Selecione o tipo de atendimento</option>
                                        <?php endif; ?>
                                        
                                        <?php
                                            foreach($tabelas as $item) {
                                                echo "<option value='{$item['cd_tabela']}'>{$item['nm_tabela']}</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" style="margin: 2px;">
                                <label for="cd_paciente_avulso" class="col-sm-3 control-label padding-label">Paciente</label>
                                <div class="col-sm-8 padding-field" id="div-cd_paciente_avulso">
                                    <select class='form-control select2'  id='cd_paciente_avulso' style='width: 100%;'>
                                        <option value='0'>Selecione o paciente</option>
                                        <?php
//                                            foreach($pacientes as $item) {
//                                                echo "<option value='{$item['cd_paciente']}'>{$item['nm_paciente']}</option>";
//                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default pull-left" data-dismiss="modal" id="btn_fechar_modal-default_novo_atendimento">Fechar</button>
                        <button type="button" class="btn btn-primary" onclick="gerar_atendimento_avulso()">Confirmar</button>
                    </div>
                </div>
            </div>
          </div>

          <!-- Painel de Resultados -->
          <div class="box box-info" id="box-pesquisa">
              <div class="box-header with-border">
                  <h3 class="box-title">Pacientes</h3>
                  <div class="box-tools pull-right">
                      <button type="button" class="btn btn-primary" title="Configurar Filtro" data-toggle="modal" data-target="#modal-default_pesquisa" id="btn_default_pesquisa"><i class="fa fa-filter"></i></button>
                      <button type="button" class="btn btn-primary" title="Atualizar" onclick="pesquisar_atendimentos_hoje()" id="btn-atualizar-pesquisa"><i class="fa fa-refresh"></i></button>
                      <button type="button" class="btn btn-primary" title="Atendimento Avulso" onclick="novo_atendimento_avulso()" data-toggle="modal" data-target="#modal-default_novo_atendimento" id="btn-novo-cadastro"><i class="fa fa-file-o"></i></button>
                  </div>
              </div>

              <div class="box-body" id="box-tabela">
                <p>Lista de registros resultantes da pesquisa</p>
              </div>
          </div>

          <!-- Painel de Cadastro -->
          <div class="box box-primary" id="box-cadastro">
            <div class="box-header with-border">
                <h3 class="box-title" id="nm_paciente">Cadastro</h3>
                <div class="box-tools pull-right">
                    <input type="hidden" id="id_linha">
                    <input type="hidden" id="operacao">
                    <input type="hidden" id="referencia">
                    <button type="button" class="btn btn-primary" title="Salvar Atendimento" onclick="salvar_atendimento()" id="btn-salvar-atendimento"><i class="fa fa-save"></i></button>
                    <button type="button" class="btn btn-primary" title="Fechar (Voltar à pesquisa)" onclick="fechar_atendimento()"><i class="fa fa-close"></i></button>
                </div>
            </div>

            <div class="row-border">
                <div class="col-md-12">
<!--                            
                    <div class="col-md-4 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a href='javascript:preventDefault();'>Prontuário <span class="col-md-4 pull-right badge bg-blue">0002120212</span></a></li>
                            <li><a href='javascript:preventDefault();'>Data de Nascimento <span class="col-md-4 pull-right badge bg-blue">22/02/1980</span></a></li>
                            <li><a href='javascript:preventDefault();'>Idade <span class="col-md-4 pull-right badge bg-blue">39a3m</span></a></li>
                        </ul>
                    </div>
-->                            
                    <div class="col-md-2 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding" href='javascript:preventDefault();'>Prontuário <br><label style="font-size: 18px;" id="cd_paciente">0002120212</label></a></li>
                        </ul>
                    </div>
                    <div class="col-md-2 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding"  href='javascript:preventDefault();'>Data de Nascimento <br><label style="font-size: 18px;" id="dt_nascimento">22/02/1980</label></a></li>
                        </ul>
                    </div>
                    <div class="col-md-1 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding"  href='javascript:preventDefault();'>Idade <br><label style="font-size: 18px;" id="ds_idade">39a3m</label></a></li>
                        </ul>
                    </div>
                    <div class="col-md-5 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding"  href='javascript:preventDefault();'>Tipo do atendimento <br><label style="font-size: 18px;" id="ds_servico">...</label></a></li>
                        </ul>
                    </div>
                    <div class="col-md-2 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding"  href='javascript:preventDefault();'>Situação <br><label class="badge" style="width: 100%; font-size: 18px;" id="ds_situacao">Pedente</label></a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-12">
                    <div class="col-md-10 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding" href='javascript:preventDefault();'>Endereço <br><label style="font-size: 18px;" id="ds_endereco">...</label></a></li>
                        </ul>
                    </div>
                    <div class="col-md-2 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding" href='javascript:preventDefault();'>Data/Hora <br><label style="font-size: 18px;" id="dh_agenda">01/01/2015 às 12h30</label></a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-12">
                    <div class="col-md-5 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding" href='javascript:preventDefault();'>Telefone(s) <br><label style="font-size: 18px;" id="nr_contatos">...</label></a></li>
                        </ul>
                    </div>
                    <div class="col-md-5 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding" href='javascript:preventDefault();'>E-mail <br><label style="font-size: 18px;" id="ds_email">...</label></a></li>
                        </ul>
                    </div>
                    <div class="col-md-2 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding" href='javascript:preventDefault();'>Valor (R$) <br><label style="font-size: 18px; text-align: right; width: 100%; padding-right: 20px;" id="vl_servico">0,00</label></a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-12">
                    <div class="col-md-10 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding" href='javascript:preventDefault();'>Profissão <br><label style="font-size: 18px;" id="ds_profissao">...</label></a></li>
                        </ul>
                    </div>
                    <div class="col-md-2 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding" href='javascript:preventDefault();'>Código do atendimento: <br><label class="badge" style="width: 100%; font-size: 18px;" id="cd_atendimento">0</label></a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-12">
                    <div class="col-md-5 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding" href='javascript:preventDefault();'>Acompanhante <br><label style="font-size: 18px;" id="nm_acompanhante">...</label></a></li>
                        </ul>
                    </div>
                    <div class="col-md-5 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding" href='javascript:preventDefault();'>Indicação <br><label style="font-size: 18px;" id="nm_indicacao">...</label></a></li>
                        </ul>
                    </div>
                    <div class="col-md-2 box-footer no-padding">
                        <ul class="nav nav-stacked">
                            <li><a class="no-padding" href='javascript:preventDefault();'>Paciente desde: <br><label style="font-size: 18px;" id="dt_cadastro">01/01/2015</label></a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-12">
                    <p style="font-size: 3px;">&nbsp;</p>
                </div>
                
                <div class="col-md-12">
                    <input type="hidden" id="id_agenda"        value="">
                    <input type="hidden" id="cd_agenda"        value="0">
                    <input type="hidden" id="st_agenda"        value="0">
                    <input type="hidden" id="dt_agenda"        value="">
                    <input type="hidden" id="hr_agenda"        value="">
                    <input type="hidden" id="cd_convenio"      value="<?php echo $cd_convenio;?>">
                    <input type="hidden" id="cd_especialidade" value="0">
                    <input type="hidden" id="id_atendimento" value="">
                    <input type="hidden" id="dt_atendimento" value="<?php echo date('d/m/Y');?>">
                    <input type="hidden" id="hr_atendimento" value="<?php echo date('H:i:s');?>">
                    <input type="hidden" id="st_atendimento" value="0">
                    <input type="hidden" id="sn_todos_exames"  value="0">
                    <input type="hidden" id="sn_todas_medidas" value="0">
                    
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active" id="tab_1a"><a href="#tab_1" data-toggle="tab">Prescrição</a></li>
                            <li id="tab_2a"><a href="#tab_2" data-toggle="tab">Exames</a></li>
                            <li id="tab_3a"><a href="#tab_3" data-toggle="tab">Evoluções de Medidas</a></li>
                            <li id="tab_4a"><a href="#tab_4" data-toggle="tab">Observações</a></li>
                            <li id="tab_5a"><a href="#tab_5" data-toggle="tab" onclick="carregar_historico()">Histórico</a></li>
                            <li id="tab_6a"><a href="#tab_6" data-toggle="tab" onclick="carregar_arquivos()">Arquivos</a></li>
                        </ul>

                        <div class="tab-content">

                            <div class="tab-pane active" id="tab_1">
                                <div class="box no-border">
                                    <div class="row-border">
                                        <select class="form-control select2"  id="cd_modelo_precricao" style='width: 250px;' disabled>
                                            <option value='0'>Selecione o modelo de prescrição</option>
                                        </select>
                                        <button type="button" class="btn btn-primary" title="Usar modelo de abscrição selecionada" onclick="xxx()" id="btn-editar-prescricao" disabled><i class="fa fa-edit"></i></button>
                                        <div class="box-tools pull-right">
                                            <button type="button" class="btn btn-primary" title="Salvar Histórica e Prescrição" onclick="salvar_atendimento()" id="btn-salvar-prescricao"><i class="fa fa-save"></i></button>
                                            <button type="button" class="btn btn-primary" title="Imprimir Prescrição" onclick="imprimir_prescricao(null)" id="btn-imprimir-prescricao"><i class="fa fa-print"></i></button>
                                        </div>
                                    </div>
                                    <div class="box-body no-padding">
                                        <p style='font-size: 3px;'>&nbsp;</p>
                                        <label class="col-sm-12 label-info text-center padding-label text-uppercase">História clínica</label>
                                        <font face='courier new'>
                                            <textarea class="form-control text-uppercase" rows="14" id="ds_historia" placeholder="Descreva aqui a história clínica do paciente referente a este atendimento..." style="width: 100%;"></textarea>
                                        </font>
                                        <p style='font-size: 1px;'>&nbsp;</p>
                                        <label class="col-sm-12 label-info text-center padding-label text-uppercase">Prescrição / Receituário</label>
                                        <font face='courier new'>
                                            <textarea class="form-control text-uppercase" rows="14" id="ds_prescricao" placeholder="Descreva aqui a prescrição para o paciente referente a este atendimento..." style="width: 100%;"></textarea>
                                        </font>
                                        <div class="form-group" style="margin: 2px;">
                                            <div class="col-sm-8 padding-field">
                                                <div class="checkbox icheck">
                                                  <label>
                                                      <input class="proximo_campo" type="checkbox" id="sn_avulso" value="1" disabled> Atendimento avulso
                                                  </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div><!--Fim da TAB 1-->

                            <div class="tab-pane" id="tab_2">
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
                                        <table id='tb-exames' class='table table-bordered table-striped table-hover'>
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Exame</th>
                                                    <th class='text-center'>05/08/2018</th>
                                                    <th class='text-center'>10/10/2018</th>
                                                    <th class='text-center'>01/01/2019</th>
                                                    <th class='text-center'>30/01/2019</th>
                                                    <th class='text-center'>20/02/2019</th>
                                                    <th class='text-center'>31/05/2019</th>
                                                    <!--<th class='text-center bg-gray' colspan='2'>10/06/2019</th>-->
                                                    <th class='text-center no-padding' colspan='2'>
                                                        <div class="input-group">
                                                            <div class="input-group-addon">
                                                                <i class="fa fa-calendar"></i>
                                                            </div>
                                                            <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask id="dt_exame" value="<?php echo date('d/m/Y');?>">
                                                        </div>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                foreach($exames as $item) {
                                                    $referencia = substr($item['id_exame'], 1, strlen($item['id_exame']) - 2);
                                                    $tr = "<tr id='reg-linha_exame_{$referencia}'>"
                                                        . "     <td>" . str_pad($item['cd_exame'], 2, "0", STR_PAD_LEFT) . "</td>"
                                                        . "     <td>{$item['nm_exame']}</td>"
                                                        . "     <td class='text-right'>0.32</td>"
                                                        . "     <td class='text-right'>0.32</td>"
                                                        . "     <td class='text-right'>0.32</td>"
                                                        . "     <td class='text-right'>0.32</td>"
                                                        . "     <td class='text-right'>0.32</td>"
                                                        . "     <td class='text-right'>0.32</td>"
                                                        . "     <td class='text-right no-padding' style='width: 10%;'>"
                                                        . "         <input type='text' class='form-control text-right proximo_campo' maxlength='10' id='vl_exame_{$referencia}'>"
                                                        . "     </td>"
                                                        . "     <td>{$item['un_exame']}</td>"
                                                        . "</tr>";
                                                    echo $tr;
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div><!--Fim da TAB 2-->
                            
                            <div class="tab-pane" id="tab_3">
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
                                        <table id='tb-evolucoes' class='table table-bordered table-striped table-hover' style='width: 100%;'>
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Medida</th>
                                                    <th class='text-center'>05/08/2018</th>
                                                    <th class='text-center'>10/10/2018</th>
                                                    <th class='text-center'>01/01/2019</th>
                                                    <th class='text-center'>30/01/2019</th>
                                                    <th class='text-center'>20/02/2019</th>
                                                    <th class='text-center'>31/05/2019</th>
                                                    <th class='text-center no-padding' colspan='2'>
                                                        <div class="input-group">
                                                            <div class="input-group-addon">
                                                                <i class="fa fa-calendar"></i>
                                                            </div>
                                                            <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask id="dt_evolucao_medida" value="<?php echo date('d/m/Y');?>">
                                                        </div>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                foreach($evolucoes as $item) {
                                                    $referencia = substr($item['id_evolucao'], 1, strlen($item['id_evolucao']) - 2);
                                                    $tr = "<tr id='reg-linha_evolucao_{$referencia}'>"
                                                        . "     <td>" . str_pad($item['cd_evolucao'], 2, "0", STR_PAD_LEFT) . "</td>"
                                                        . "     <td>{$item['ds_evolucao']}</td>"
                                                        . "     <td class='text-right'>0.32</td>"
                                                        . "     <td class='text-right'>0.32</td>"
                                                        . "     <td class='text-right'>0.32</td>"
                                                        . "     <td class='text-right'>0.32</td>"
                                                        . "     <td class='text-right'>0.32</td>"
                                                        . "     <td class='text-right'>0.32</td>"
                                                        . "     <td class='text-right no-padding' style='width: 10%;'>"
                                                        . "         <input type='text' class='form-control text-right proximo_campo' maxlength='10' id='vl_evolucao_{$referencia}'>"
                                                        . "     </td>"
                                                        . "     <td>{$item['un_evolucao']}</td>"
                                                        . "</tr>";
                                                    echo $tr;
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div><!--Fim da TAB 3-->
                            
                            <div class="tab-pane" id="tab_4">
                                <div class="box-body form-horizontal">
                                    <div class="col-md-12">
                                      <div class="form-group" style="margin: 2px;">
                                          <label for="ds_alergias" class="col-sm-1 control-label padding-label">Alergias</label>
                                          <div class="col-sm-11 padding-field">
                                              <textarea class="form-control" rows="7" id="ds_alergias" placeholder="Descreva as alergias do paciente caso tenha..." style="width: 100%;"></textarea>
                                          </div>
                                      </div>

                                      <div class="form-group" style="margin: 2px;">
                                          <label for="ds_observacoes" class="col-sm-1 control-label padding-label">Observações</label>
                                          <div class="col-sm-11 padding-field">
                                              <textarea class="form-control" rows="7" id="ds_observacoes" placeholder="Observações em geral..." style="width: 100%;"></textarea>
                                          </div>
                                      </div>
                                    </div>
                                </div>
                            </div><!--Fim da TAB 4-->
                            
                            <div class="tab-pane active" id="tab_5">
                                <div class="box no-border">
                                    <div class="box-body no-padding" id="box-tabela_historicos">
                                        <table id='tb-historicos' class='table table-bordered table-striped table-hover'>
                                            <thead>
                                                <tr>
                                                    <th>Data</th>
                                                    <th>História Clínica</th>
                                                    <th>Prescrição</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <th>
                                                    <td>17/06/2019</td>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                </th>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div><!--Fim da TAB 5-->
                            
                            <div class="tab-pane" id="tab_6">
                                <div class="box no-border">
                                    <div class="box-body no-padding">
                                        <div class="col-md-5" id="box-tabela_arquivos">
                                            <!-- Carregar a tabela de arquivos do paciente aqui -->
                                            <table id='tb-arquivos_paciente' class='table table-bordered table-hover'>
                                                <thead>
                                                    <tr>
                                                        <th>Grupo</th>
                                                        <th>Data</th>
                                                        <th>Descrição</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Exames</td>
                                                        <td>10/05/2021</td>
                                                        <td>Resultado de hemograma</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="col-md-7" id="panel-arquivo_paciente" style="height: 512px;">
                                            <!-- Carregar o arquivo selecionado aqui -->
                                            <!-- https://developer.mozilla.org/en-US/docs/Web/Media/Formats/Image_types -->
                                            <embed src="./logs/storage/paciente/empty.png?<?= $versao; ?>" type='image/png' width='100%' style='height : auto;'>
                                            <!--<embed src="./logs/storage/paciente/empty.pdf" type='application/pdf' width='100%' height='100%'>-->
                                            <!--<embed src="./logs/storage/paciente/empty.jpg" type='image/jpeg' width='100%' height='100%'>-->
                                        </div>  
                                        <br>
                                    </div>
                                </div>
                            </div><!--Fim da TAB 6-->
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <button type="button" class="btn btn-default pull-left"  id="btn_form_close" onclick="voltar_pesquisa()">Fechar</button>
                <button type="button" class="btn btn-primary pull-right" id="btn_form_save"  onclick="encerrar_atendimento(null)">Encerrar atendimento</button>
            </div>
          </div>
          
          <button type="button" class="btn btn-sm" data-toggle="modal" data-target="#modal-visualizar-arquivo" id="btn_visualizar-arquivo"></button>
          
          <!-- Painel para Visualizar Arquivos -->
          <div class="modal fade" id="modal-visualizar-arquivo">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="visualizar-arquivo_title">Visualizar Arquivo</h4>
                    </div>
                    <div class="modal-body" id="visualizar-arquivo_body">
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default pull-left" data-dismiss="modal" id="visualizar-arquivo_close">Fechar</button>
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
            $('#box-cadastro').on('keyup', '.proximo_campo', function(e) {
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
            
            $(function () {
                $.fn.dataTable.moment('DD/MM/YYYY');
                
                $("#btn_msg_padrao").fadeOut(1);
                $("#btn_msg_alerta").fadeOut(1);
                $("#btn_msg_erro").fadeOut(1);
                $("#btn_msg_informe").fadeOut(1);
                $("#btn_msg_primario").fadeOut(1);
                $("#btn_msg_sucesso").fadeOut(1);
                $("#btn_visualizar-arquivo").fadeOut(1);

                $('#qtde-registros-atend').val(<?php echo $qtde;?>);
                $('#qtde-registros-atend').select2();
                $('#cd_tipo_filtro').val(<?php echo $tipo;?>);
                $('#cd_tipo_filtro').select2();
                
                configurar_checked();
                configurar_tabela('#tb-pacientes');
                
                $('#modal-default_pesquisa').on('shown.bs.modal', function(event) {
                    $('#dt_hoje').focus();
                });
                
                $('#modal-default_novo_atendimento').on('shown.bs.modal', function(event) {
                    $('#dt_agenda_avulso').focus();
                });
                
                // $('.textarea').wysihtml5(); <-- Está funcionando, mas optou-se por usar um editor de texto simples
                
                <?php
                include './../dist/js/pages/_modal.js';
                ?>
            });
            
            $('#modal-visualizar-arquivo').on('shown.bs.modal', function(event) {
                $('.close').focus();
            });
            
            $('#box-filtro').hide();
            $('#box-pesquisa').show();
            $('#box-cadastro').hide();

            $(".select2").select2();      // Ativar o CSS nos "Select"
            $('[data-mask]').inputmask(); // Ativar as máscaras nas "Input"

            pesquisar_atendimentos_hoje();
            
            function configurar_checked() {
                $('input').iCheck({
                    checkboxClass: 'icheckbox_square-blue',
                    radioClass   : 'iradio_square-blue',
                    increaseArea : '20%' // optional
                });
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
            
            function configurar_tabela(id) {
                if (typeof($(id)) !== "undefined") {
                    var qt_registros = parseInt('0' + $('#qtde-registros-atend').val());
                    $(id).DataTable({
                        "paging": true,
                        "pageLength": qt_registros, // Quantidade de registrs para paginação
                        "lengthChange": false,
//                        "lengthMenu": [
//                            [10, 11, 12, 13, 14, 15, 20, 25, 50, -1],
//                            ['10', '11', 12', '13', '14', '15', '20', '25', '50', 'Todos']
//                        ],
                        "searching": false,
                        "ordering": false,
                        "info": true,
                        "autoWidth": true,
                        "processing": true,
                        "columns": [
                            { "width": "30px" },  // 0. Horário
                            { "width": "10px" },  // 1. Prontuário
                            null,                 // 2. Paciente
                            { "width": "10px" },  // 3. Idade
                            null,                 // 4. Tipo
                            null                  // 5. Especialiade
                        ],
//                        "columnDefs": [
//                            {"orderable": false, "targets": 0}, // Horário
//                            {"orderable": false, "targets": 1}, // Prontuário
//                            {"orderable": false, "targets": 2}, // Paciente
//                            {"orderable": false, "targets": 3}, // Idade
//                            {"orderable": false, "targets": 4}, // Tipo
//                            {"orderable": false, "targets": 5}  // Especialidade
//                        ],
//                        "order": [], // "order": [] <-- Ordenação indefinida (Nome)
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
                                "zeroRecords": "Sem registro(s) para exibição",
                                "lengthMenu": "Exibindo _MENU_ registro(s)",
                                "loadingRecords": "Por favor, aguarde - carregando...",
                                "processing": "Processando...",
                                "search": "Localizar:"
                        }
                    });

                    $(id + '_filter input').focus();
                }
            }
            
            function configurar_tabela_arquivos(id) {
                if (typeof($(id)) !== "undefined") {
                    $(id).DataTable({
                        "paging": true,
                        "pageLength": 15, 
                        "lengthChange": false,
                        "searching": false,
                        "ordering": true,
                        "info": true,
                        "autoWidth": true,
                        "processing": true,
                        "columns": [
                            null, // 0. Grupo
                            null, // 1. Data
                            null, // 2. Descrição
                            { "width": "5px"  }, // 3. <Opções>
                            { "width": "5px"  }  // 4. <Visualizar/Carregar>
                        ],
                        "columnDefs": [
                            {"orderable": false, "targets": 0},  // Grupo
                            {"orderable": false, "targets": 3, "visible": false},  // <Opções>
                            {"orderable": false, "targets": 4}   // <Visualizar/Carregar>
                        ],
                        "order": [[1, 'desc']], // Ordenação por Data
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
                                "zeroRecords": "Sem registro(s) para exibição",
                                "lengthMenu": "Exibindo _MENU_ registro(s)",
                                "loadingRecords": "Por favor, aguarde - carregando...",
                                "processing": "Processando...",
                                "search": "Localizar:"
                        }
                    });

                    $(id + '_filter input').focus();
                }
            }
            
//            function abrir_filtro() { 
//                $('#box-filtro').fadeIn(); 
//                //$('#btn-configurar-pesquisa').fadeOut(); 
//            } 
//            
//            function abrir_pesquisa() { 
//                $('#box-pesquisa').fadeIn(); 
//            } 
            
            function novo_cadastro(usuario) {
                var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso = "0100";
                get_allow_user(usuario, rotina, acesso, function(){
                    show_informe("Atendimento Avulso", "Recurso não disponível nesta versão do sistema");
                    /*
                    $('#operacao').val("inserir");
                    
                    // Identificação
                    //$('#sn_ativo').prop('checked', true).iCheck('update');
                    
                    $('.select2').select2();
                    ativar_guia('#tab_1');
                    
                    $('#box-filtro').hide();
                    $('#box-pesquisa').hide();
                    $('#box-cadastro').show();

                    $('#box-cadastro').fadeIn(); 
                    */
                });
            }

            function novo_atendimento_avulso() {
                var agora = new Date();
                
                $('#cd_paciente_avulso').val("0");
                <?php if ($is_medico === false): ?>
                $('#cd_tabela_avulso').val("0");
                <?php endif; ?>
                $('#dt_agenda_avulso').val(zero_esquerda(agora.getDate(), 2)  + "/" + zero_esquerda(agora.getMonth()+1, 2) + "/" + agora.getFullYear());
                $('#hr_agenda_avulso').val(zero_esquerda(agora.getHours(), 2) + ":" + zero_esquerda(agora.getMinutes(), 2) + ":" + zero_esquerda(agora.getSeconds(), 2));
                
                atualizar_lista_paciente_avulso('select', 'cd_paciente_avulso', function (listagem){
                    $('#div-cd_paciente_avulso').html(listagem);
                    $('.select2').select2();
                });
            }

            function gerar_atendimento_avulso() {
                var requedido = "";
                
                if (parseInt($('#cd_tabela_avulso').val()) === 0)        requedido += "<li>Tipo do Atendimento</li>";
                if (parseFloat($('#cd_paciente_avulso').val()) === 0.0) requedido += "<li>Paciente</li>";

                if (requedido !== "") {
                    show_campos_requeridos("Alerta", "Atendimento Avulso", requedido);
                } else {
                    var observacoes = "Agendamento avulso gerado em consultório.\n<?php echo $empresa['nm_apresentacao'];?>\n"
                      + "Data : " + $('#dt_agenda_avulso').val() + "\n"
                      + "Hora : " + $('#hr_agenda_avulso').val();
                    gerar_agendamento(observacoes, function (retorno){
                        $('#btn_fechar_modal-default_novo_atendimento').trigger('click');
                        $('#btn_abrir_cadastro_' + retorno.registro[0].referencia).trigger('click');
                    });
                }
            }
            
            function abrir_cadastro(handler, situacao) {
                // Descarcar registro selecionado
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
                
                carregar_registro_atendimento(registro, function(data){
                    $('#operacao').val("editar");
                    $('#referencia').val(registro);
                    
                    if ($('#ds_situacao').hasClass('text-bold'))  $('#ds_situacao').removeClass('text-bold');
                    if ($('#ds_situacao').hasClass('bg-green'))   $('#ds_situacao').removeClass('bg-green');
                    if ($('#ds_situacao').hasClass('bg-primary')) $('#ds_situacao').removeClass('bg-primary');
                    if ($('#ds_situacao').hasClass('bg-yellow'))  $('#ds_situacao').removeClass('bg-yellow');
                    if ($('#ds_situacao').hasClass('bg-red'))     $('#ds_situacao').removeClass('bg-red');
                    if ($('#ds_situacao').hasClass('bg-gray'))    $('#ds_situacao').removeClass('bg-gray');

                    // Agenda
                    $('#id_agenda').val(data.registro[0].id);
                    $('#cd_agenda').val(data.registro[0].codigo);
                    $('#st_agenda').val( $('#st_agenda_' + registro).val() );
                    $('#dt_agenda').val(data.registro[0].data);
                    $('#hr_agenda').val(data.registro[0].hora);
                    $('#cd_convenio').val(data.registro[0].convenio); 
                    $('#cd_especialidade').val(data.registro[0].especialidade);
                    $('#sn_avulso').prop('checked', (parseInt(data.registro[0].avulso) === 1)).iCheck('update');
                    
                    if ( parseInt($('#cd_convenio').val()) === 0 ) {
                        $('#cd_convenio').val('<?php echo $cd_convenio?>');
                    }
                    
                    // Atendimento
                    $('#id_atendimento').val(data.registro[0].id); // Mesmo ID da agenda será o ID do atendimento
                    $('#cd_atendimento').html( zero_esquerda(get_value(data.registro[0].codigo_atendimento, '0'), 7) );
                    $('#dt_atendimento').val(data.registro[0].data_atendimento);
                    $('#hr_atendimento').val(data.registro[0].hora_atendimento);
                    $('#st_atendimento').val(get_value(data.registro[0].status, '0'));
                    $('#ds_historia').val( get_value(data.registro[0].historia, '') );
                    $('#ds_prescricao').val( get_value(data.registro[0].prescricao, '') );
                     
                    // Identificação
                    $('#cd_paciente').html(zero_esquerda(data.registro[0].prontuario, 7));
                    $('#nm_paciente').html('<strong>' + data.registro[0].paciente + '</strong>');
                    $('#dt_nascimento').html(data.registro[0].nascimento);
                    $('#ds_idade').html(data.registro[0].idade);
                    $('#ds_situacao').html(data.registro[0].descricao_situacao);
                    $('#ds_situacao').addClass( $('#tg_legenda_' + registro).val().trim() );
                    $('#ds_servico').html(data.registro[0].descricao_servico);
                    $('#vl_servico').html( get_value(data.registro[0].valor, '0,00') );
                    $('#dh_agenda').html(data.registro[0].data + ' às ' + data.registro[0].hora);
                    $('#ds_endereco').html( get_value(data.registro[0].endereco, '...') );
                    $('#nr_contatos').html( get_value(data.registro[0].contatos, '...'));
                    $('#ds_email').html( get_value(data.registro[0].email, '...') );
                    $('#ds_profissao').html( get_value(data.registro[0].profissao, '...') );
                    $('#dt_cadastro').html( get_value(data.registro[0].cadastro, '...') );
                    $('#nm_acompanhante').html( get_value(data.registro[0].acompanhante, '...') );
                    $('#nm_indicacao').html( get_value(data.registro[0].indicacao, '...') );
                    $('#ds_alergias').html( get_value(data.registro[0].paciente_alergias, '') );
                    $('#ds_observacoes').html( get_value(data.registro[0].paciente_observacoes, '') );
                    
//                    if ((get_value(data.registro[0].historia, '') !== '') || (get_value(data.registro[0].prescricao, '') !== '')) {
//                        if ((situacao === null) && (parseInt($('#st_agenda').val()) === 2)) {
//                            situacao = '22'; // Em atendimento
//                        }
//                    }
                    if ($('#profissionalMedico').val() === "S") {
                        if (parseInt($('#st_agenda').val()) === 2) {
                            situacao = '22'; // Em atendimento
                        }
                    }
                    
                    if (situacao !== null) {
                        $('#st_agenda').val( situacao );
                        $('#ds_situacao').html('Em atendimento');
                    }
                    
                    var editar_atendimento = (parseInt($('#st_agenda').val()) === 22); // Em atendimento
                    if ($('#profissionalMedico').val() !== "S") editar_atendimento = false;
                    
                    $('#ds_historia').prop('readonly', !editar_atendimento);
                    $('#ds_prescricao').prop('readonly', !editar_atendimento);
                    $('#ds_alergias').prop('readonly', !editar_atendimento);
                    $('#ds_observacoes').prop('readonly', !editar_atendimento);
                    $('#btn-salvar-atendimento').prop('disabled', !editar_atendimento);
                    $('#btn-salvar-prescricao').prop('disabled', !editar_atendimento);
                    $('#btn-salvar-exames').prop('disabled', !editar_atendimento);
                    $('#btn-salvar-evolucoes').prop('disabled', !editar_atendimento);
                    $('#btn_form_save').prop('disabled', !editar_atendimento);
                    
                    $('#id_exame').prop('disabled', !editar_atendimento);
                    $('#btn-adcionar-exame').prop('disabled', !editar_atendimento);
                    $('#id_evolucao').prop('disabled', !editar_atendimento);
                    $('#btn-adicionar-evolucao').prop('disabled', !editar_atendimento);
                    
                    $('.select2').select2();
                    ativar_guia('#tab_1');
                    
                    $('#box-filtro').hide();
                    $('#box-pesquisa').hide();
                    $('#box-cadastro').show();
                    
                    $('#box-cadastro').fadeIn(); 
                    $('#ds_historia').focus();
                    
                    carregar_exames();
                    carregar_evolucoes();
                }); 
            } 
            
            function iniciar_atendimento(handler) {
                if ($('#profissionalMedico').val() !== "S") {
                    show_informe("Informação", "Apenas usuários com perfil de profissional médico podem iniciar atendimento");
                } else {
                    var tr = $(handler).closest('tr');
                    var rf = $(tr).attr('id').replace("tr-linha_", "");
                    var st = parseInt("0" + $('#st_agenda_' + rf).val());
                    var msg = $('#ds_situacao_' + rf).val();
                    if (st === 2) {
                        abrir_cadastro(handler, '22'); // Atender (Em atendimento)
                    } else {
                        show_informe("Informação", msg);
                    }
                }
            }
            
            function encerrar_atendimento(handler) { 
                var registro = $('#referencia').val();
                if (handler !== null) {
                    var tr = $(handler).closest('tr');
                    registro = $(tr).attr('id').replace("tr-linha_", ""); // Pegar linha TR
                }

                var tr_table  = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                var colunas   = tr_table.getElementsByTagName('td');
                var descricao = colunas[2].firstChild.nodeValue;
                var titulo    = "Atendimento";
                var mensagem  = "Deseja finalizada o atendimento do paciente <strong>" + descricao + "</strong>?" + "\n" + 
                        "Ao fazer isso, você não poderá mais editar este atendimento.";
                var st_agenda = parseInt("0" + $('#st_agenda_' + registro).val());
                var situacao  = $('#ds_situacao_' + registro).val();
                var cd_atendi = parseFloat("0" + $('#cd_atendimento_' + registro).val());

                if (st_agenda !== 0) {
                    if ((st_agenda === 1) || (cd_atendi === 0.0)) {
                        show_informe("Informação", "Apenas atendimentos em andamento podem ser encerrados.");
                    } else 
                    if ((st_agenda === 3) || (st_agenda === 9)) {
                        show_informe("Informação", situacao);
                    } else {
                        st_agenda = 3; // Marcar como atendido
                        set_situacao_atendimento(registro, st_agenda, titulo, mensagem, function(){
                            // Remover legenda do botão
                            var botao = $('#tr-linha_' + registro + ' .btn'); // Pegar botão dentro da TR  
                            remover_legenda_botao(botao);
                            botao.addClass('text-bold bg-primary');

                            $('#st_agenda_'   + registro).val(st_agenda);
                            $('#ds_situacao_' + registro).val("O atendimento selecionado já foi <strong>finalizado</strong>.");
                            
                            $('#btn_form_close').trigger('click');
                        });
                    }
                }
            }
            /*
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
            
            */
            function ativar_guia(id) {
                if ( $('#tab_1a').hasClass("active") ) $('#tab_1a').removeClass("active");
                if ( $('#tab_2a').hasClass("active") ) $('#tab_2a').removeClass("active");
                if ( $('#tab_3a').hasClass("active") ) $('#tab_3a').removeClass("active");
                if ( $('#tab_4a').hasClass("active") ) $('#tab_4a').removeClass("active");
                if ( $('#tab_5a').hasClass("active") ) $('#tab_5a').removeClass("active");
                if ( $('#tab_6a').hasClass("active") ) $('#tab_6a').removeClass("active");
                
                if ( $('#tab_1').hasClass("active") ) $('#tab_1').removeClass("active");
                if ( $('#tab_2').hasClass("active") ) $('#tab_2').removeClass("active");
                if ( $('#tab_3').hasClass("active") ) $('#tab_3').removeClass("active");
                if ( $('#tab_4').hasClass("active") ) $('#tab_4').removeClass("active");
                if ( $('#tab_5').hasClass("active") ) $('#tab_5').removeClass("active");
                if ( $('#tab_6').hasClass("active") ) $('#tab_6').removeClass("active");
                
                if (typeof($(id)) !== "undefined") $(id).addClass("active");
                if (typeof($(id + 'a')) !== "undefined") $(id + 'a').addClass("active");
            }    
            
            function fechar_filtro(pesquisar) {
                //$('#btn-configurar-pesquisa').fadeIn();
                //$('#box-filtro').fadeOut(); <-- Muito lento para o contexto
                $('#box-filtro').hide();
                if (pesquisar === true) pesquisar_atendimentos_hoje();
            } 
            
            function fechar_pesquisa() { 
                $('#box-pesquisa').fadeOut(); 
            } 
            
            function fechar_cadastro() {
                $('#box-cadastro').fadeOut(); 
            } 
            
            function voltar_pesquisa() {
                $('#box-pesquisa').show();
                $('#box-cadastro').hide();
                
                // Setar o foco no botão da linha para fazer com que a página não perca o elemento de "vista"
                var botao = $('#tr-linha_' + $('#referencia').val() + ' .btn'); 
                if (typeof($(botao)) !== "undefined") {
                    botao.focus();
                }    
            }
            
            function fechar_atendimento() {
                if (parseInt($('#st_agenda').val()) === 22) {
                    show_confirmar("Fechar", "Deseja sair do atendimento do paciente?");
                    var botao = document.getElementById("primary_confirm");
                    botao.onclick = function() {
                        $('#btn_msg_primario').trigger('click');
                        voltar_pesquisa();
                    }
                } else {
                    voltar_pesquisa();
                }
            }
            
            function salvar_atendimento() {
                var inserir   = ($('#operacao').val() === "inserir");
                var registro  = $('#referencia').val();
                var requedido = "";
                
                if (parseFloat($('#cd_paciente').html()) === 0.0)  requedido += "<li>Paciente</li>";
                if (parseInt($('#cd_convenio').val()) === 0) requedido += "<li>Convêvio</li>";
                if ($('#cd_especialidade').val() === "0")  requedido += "<li>Especialidade</li>";
                if ($('#cd_profissional').val()  === "0")  requedido += "<li>Médico</li>";
                if (($('#ds_historia').val().trim() === "") && ($('#ds_prescricao').val().trim() === "")) requedido += "<li>História clínica e/ou Prescrição</li>";

                if (requedido !== "") {
                    show_campos_requeridos("Alerta", "Atendimento do Paciente", requedido);
                } else {
                    salvar_registro_atendimento(registro, function(data){
//                        if (inserir) {
//                            $('#cd_paciente').val(data.registro[0].prontuario);
//                            var newRow = $(data.registro[0].tr_table);
//                            $("#tb-pacientes").append(newRow);
//                        } else {
//                            var tr_table = document.getElementById("tr-linha_" + data.registro[0].prontuario); 
//                            var colunas  = tr_table.getElementsByTagName('td');
//                            
//                            colunas[1].firstChild.nodeValue = data.registro[0].nome;
//                            colunas[2].firstChild.nodeValue = data.registro[0].fone;
//                            colunas[3].firstChild.nodeValue = data.registro[0].idade;
//                            colunas[4].firstChild.nodeValue = data.registro[0].rg;
//                            colunas[5].firstChild.nodeValue = data.registro[0].cpf;
//                        }
//                        
//                        var linha = "#tr-linha_" + parseFloat($('#cd_paciente').val());
//                        $('#id_linha').val(linha);
//
//                        // Atualizar status do registro na tabela
//                        var referencia = parseInt("0" + $('#cd_paciente').val());
//                        set_status_fa('#status_paciente_' + referencia, $('#sn_ativo').is(":checked"));
//                        
//                        // Destacar linha na tabela
//                        if ((linha !== '') && (typeof($(linha)) !== 'undefined')) {
//                            $(linha).removeClass("bg-gray-light");
//                            $(linha).addClass("text-bold");
//                            $(linha).addClass("bg-gray");
//
//                            voltar_pesquisa(); 
//                        }
                            var referencia = data.registro[0].referencia;
                            var legenda    = data.registro[0].tag_legenda;
                            var linha      = "#tr-linha_" + referencia;
                            
                            $('#cd_atendimento_' + registro).val(get_value(data.registro[0].codigo_atendimento, '0'));
                            $('#cd_atendimento').html( zero_esquerda(get_value(data.registro[0].codigo_atendimento, '0'), 7) );
                            $('#id_linha').val(linha);
                            $('#st_agenda_'   + referencia).val(data.registro[0].situacao);
                            $('#ds_situacao_' + referencia).val(data.registro[0].tag_situacao);
                            /*
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
                            */
                    });
                }
            }
            
            function imprimir_prescricao(handler) {
                var cd_atendimento = parseFloat("0" + $('#cd_atendimento').html());
                if (cd_atendimento === 0.0) {
                    show_alerta("Imprimir Prescrição", "Não foi iniciado o atendimento deste paciente.");
                } else {
                    var registro = $('#referencia').val();
                    var empresa  = $('#empresaID').val();

                    if (handler !== null) {
                        var tr = $(handler).closest('tr');
                        registro = $(tr).attr('id').replace("tr-linha_", ""); // Pegar linha TR
                    }

                    var tr_table   = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                    var colunas    = tr_table.getElementsByTagName('td');
                    var prontuario = colunas[1].firstChild.nodeValue;

                    window.open("/gcm/views/print/prescricao.php?at={" + registro + "}&ep=" + empresa + "&pac=" + prontuario, '_blank');
                }
            }
            
            function imprimir_prescricao_historico(empresa, atendimento, prontuario) {
                window.open("/gcm/views/print/prescricao.php?at=" + atendimento + "&ep=" + empresa + "&pac=" + prontuario, '_blank');
            }
            
            function inserir_exame() {
                var usuario = "user_<?php echo $user->getCodigo();?>";
                var rotina  = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso  = "0100";
                get_allow_user(usuario, rotina, acesso, function() {
                    var referencia = $('#referencia').val();
                    var descricao  = $('#id_exame option:selected').text();
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
                var usuario = "user_<?php echo $user->getCodigo();?>";
                var rotina  = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso  = "0100";
                get_allow_user(usuario, rotina, acesso, function() {
                    var referencia = $('#referencia').val();
                    var descricao  = $('#id_evolucao option:selected').text();
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
                var referencia = $('#referencia').val();
                //$('#box-tabela_exames').html("<p class='text-center'><br><i class='fa fa-spin fa-refresh'></i>&nbsp; Buscando resultados de exames, <strong>aguarde</strong>!<br></p>");
                carregar_controle_exames(referencia, function(retorno){
                    $('#box-tabela_exames').html(retorno);
                    $('[data-mask]').inputmask();
                });
            }
            
            function carregar_evolucoes() {
                var referencia = $('#referencia').val();
                //$('#box-tabela_evolucoes').html("<p class='text-center'><br><i class='fa fa-spin fa-refresh'></i>&nbsp; Buscando resultados de exames, <strong>aguarde</strong>!<br></p>");
                carregar_controle_evolucoes(referencia, function(retorno){
                    $('#box-tabela_evolucoes').html(retorno);
                    $('[data-mask]').inputmask();
                });
            }
            
            function carregar_historico() {
                var referencia = $('#referencia').val();
                var id_atendimento = $('#cd_atendimento_' + referencia).val();
                var dt_atendimento = $('#dt_atendimento_' + referencia).val();
                var cd_paciente    = $('#cd_paciente_' + referencia).val();
                carregar_historico_clinico(id_atendimento, dt_atendimento, cd_paciente, function(retorno) {
                    $('#box-tabela_historicos').html(retorno);
                });
            }
            
            function carregar_arquivos() {
                var referencia = $('#referencia').val();
                var paciente   = $('#cd_paciente_' + referencia).val();
                $('#box-tabela_arquivos').html("<p class='text-center'><br><i class='fa fa-spin fa-refresh'></i>&nbsp; Buscando arquivos do paciente, <strong>aguarde</strong>!<br></p>");
                if (paciente === 0) {
                    $('#box-tabela_arquivos').html("<p style='font-size: 3px;'>&nbsp;</p><br><p><strong>Paciente não cadastrado ou não informado.</strong></p>");
                    show_alerta("Carregar Arquivos", "Paciente não cadastrado ou não informado.");
                } else {
                    carregar_arquivos_paciente(paciente, function(retorno){
                        $('#box-tabela_arquivos').html(retorno);
                        configurar_tabela_arquivos('#tb-arquivos_paciente');
                    });
                }
            }
            
            function visualizar_arquivo(id, e) {
                var cd_arquivo = id.replace("visualizar_arquivo_", "");
                visualizar_arquivo_paciente(cd_arquivo, function(retorno) {
                    $('#visualizar-arquivo_title').html(retorno.data + ", " + retorno.descricao);
                    $('#visualizar-arquivo_body').html("<embed id='arquivo' src='" + retorno.url + "?<?= $versao ?>' type='" + retorno.tipo + "' width='100%' style='height : auto;'>");
                    $('#btn_visualizar-arquivo').trigger('click');
                    
                    // Descarcar registro selecionado
                    var linha_arquivo = $('#linha_arquivo').val();
                    if ((linha_arquivo !== '') && (typeof($(linha_arquivo)) !== 'undefined')) {
                        $(linha_arquivo).removeClass("text-bold");
                        $(linha_arquivo).removeClass("bg-gray-light");
                        $(linha_arquivo).removeClass("bg-gray");
                    }
                    // Selecionar novo registro
                    var tr_table  = document.getElementById("tr-linhaarquivo_" + cd_arquivo); 
                    var tr = $(tr_table).closest('tr');
                    tr.addClass("text-bold");
                    tr.addClass("bg-gray-light");
                    $('#linha_arquivo').val( '#' + $(tr).attr('id') ); 
                });
            }
            
            function gravar_resultados_exames() {
                var registro = $('#referencia').val();
                var cd_atendimento = parseFloat("0" + $('#cd_atendimento').html());
                
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

                    // Salvar o cabeçalho do atendimento, caso ele não exista
                    if ( (cd_atendimento === 0.0) && ($('#ds_prescricao').val().trim() === "") ) {
                        $('#ds_prescricao').val("...");
                        salvar_registro_atendimento(registro, function(data){
                            var referencia = data.registro[0].referencia;
                            var linha      = "#tr-linha_" + referencia;
                            $('#cd_atendimento').html( zero_esquerda(get_value(data.registro[0].codigo_atendimento, '0'), 7) );
                            $('#id_linha').val(linha);
                            $('#st_agenda_'   + referencia).val(data.registro[0].situacao);
                            $('#ds_situacao_' + referencia).val(data.registro[0].tag_situacao);
                        });
                    }

                    // Salvar os resultados lançados dos exames
                    if ((ids_exames !== '#') && (vls_exames !== '#')) {
                        salvar_resultados_exames(ids_exames, vls_exames, function(){
                            // carregar_exames(); <-- Está causando muita lentidão
                            show_informe("Salvar Exames", "Resultado(s) gravado(s) com sucesso.");
                        });
                    } else {
                        show_alerta("Salvar Exames", "Favor informe o(s) resultado(s) do(s) exame(s)");
                    }
                }
            }
            
            function gravar_resultados_evolucoes() {
                var registro = $('#referencia').val();
                var cd_atendimento = parseFloat("0" + $('#cd_atendimento').html());
                
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

                    // Salvar o cabeçalho do atendimento, caso ele não exista
                    if ( (cd_atendimento === 0.0) && ($('#ds_prescricao').val().trim() === "") ) {
                        $('#ds_prescricao').val("...");
                        salvar_registro_atendimento(registro, function(data){
                            var referencia = data.registro[0].referencia;
                            var linha      = "#tr-linha_" + referencia;
                            $('#cd_atendimento').html( zero_esquerda(get_value(data.registro[0].codigo_atendimento, '0'), 7) );
                            $('#id_linha').val(linha);
                            $('#st_agenda_'   + referencia).val(data.registro[0].situacao);
                            $('#ds_situacao_' + referencia).val(data.registro[0].tag_situacao);
                        });
                    }

                    // Salvar os resultados lançados dos evolucoes
                    if ((ids_evolucoes !== '#') && (vls_evolucoes !== '#')) {
                        salvar_resultados_evolucoes(ids_evolucoes, vls_evolucoes, function(){
                            // carregar_evolucoes();  <-- Está causando muita lentidão
                            show_informe("Salvar Evoluções", "Resultado(s) gravado(s) com sucesso.");
                        });
                    } else {
                        show_alerta("Salvar Evoluções", "Favor informe o(s) resultado(s) da(s) evolução(ões)");
                    }
                }
            }
            
            function imprimir_controle_exames(handler) {
                var cd_atendimento = parseFloat("0" + $('#cd_atendimento').html());
                var qt_controle_exames = parseInt("0" + $('#qt_controle_exames').val());
                if (cd_atendimento === 0.0) {
                    show_alerta("Controle de Exames", "Apenas atendimentos em antamento ou finalizados podem gerar a impressão do controle de exames.");
                } else 
                if (qt_controle_exames === 0) {
                    show_alerta("Controle de Exames", "Não existe exames relacionados para impressão.");
                } else {
                    var registro = $('#referencia').val();
                    var empresa  = $('#empresaID').val();

                    if (handler !== null) {
                        var tr = $(handler).closest('tr');
                        registro = $(tr).attr('id').replace("tr-linha_", ""); // Pegar linha TR
                    }

                    var tr_table   = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                    var colunas    = tr_table.getElementsByTagName('td');
                    var prontuario = colunas[1].firstChild.nodeValue;

                    window.open("/gcm/views/print/controle_exame.php?at={" + registro + "}&ep=" + empresa + "&pac=" + prontuario, '_blank');
                }
            }
            
            function imprimir_controle_evolucoes(handler) {
                var cd_atendimento = parseFloat("0" + $('#cd_atendimento').html());
                var qt_controle_evolucoes = parseInt("0" + $('#qt_controle_evolucoes').val());
                if (cd_atendimento === 0.0) {
                    show_alerta("Controle de Evoluções", "Apenas atendimentos em antamento ou finalizados podem gerar a impressão do controle de evoluções.");
                } else 
                if (qt_controle_evolucoes === 0) {
                    show_alerta("Controle de Evoluções", "Não existem evoluções relacionados para impressão.");
                } else {
                    var registro = $('#referencia').val();
                    var empresa  = $('#empresaID').val();

                    if (handler !== null) {
                        var tr = $(handler).closest('tr');
                        registro = $(tr).attr('id').replace("tr-linha_", ""); // Pegar linha TR
                    }

                    var tr_table   = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                    var colunas    = tr_table.getElementsByTagName('td');
                    var prontuario = colunas[1].firstChild.nodeValue;

                    window.open("/gcm/views/print/controle_evolucao.php?at={" + registro + "}&ep=" + empresa + "&pac=" + prontuario, '_blank');
                }
            }
            /*
            function excluir_registro(id, elemento) {
                var usuario = "user_<?php // echo $user->getCodigo();?>";
                var rotina  = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso  = "0100";
                get_allow_user(usuario, rotina, acesso, function(){
                    var registro  = id.replace("excluir_paciente_", "");
                    var tr_table  = document.getElementById("tr-linha_" + registro); 
                    var colunas   = tr_table.getElementsByTagName('td');
                    var descricao = colunas[1].firstChild.nodeValue;
                    
                    excluir_paciente(registro, descricao, function(){
                        RemoveTableRow(elemento);
                    });
                });
            }    
            */
        </script>
    </body>
</html>
