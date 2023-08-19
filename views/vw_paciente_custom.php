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
    
    $id_estacao  = md5($_SERVER["REMOTE_ADDR"]);
    $cd_estado   = "15";      // PARÁ
    $nm_estado   = "Pará";
    $cd_cidade   = "1501402"; // BELÉM
    $nm_cidade   = "BELÉM";
    $cd_convenio = "0";
    
    $pdo = Conexao::getConnection();

    // Carregar dados da empresa
    $qry     = $pdo->query("Select * from dbo.sys_empresa");
    $dados   = $qry->fetchAll(PDO::FETCH_ASSOC);
    $empresa = null;
    foreach($dados as $item) {
        $empresa = $item;
    }
    $qry->closeCursor();
    
    $qry = $pdo->query("Select min(cd_convenio) as cd_convenio from dbo.tbl_convenio where sn_ativo = 1");
    if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
        $cd_convenio = $obj->cd_convenio;
    }
    
    $qry = $pdo->query("Select * from dbo.sys_estado");
    $estados = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    $qry = $pdo->query("Select * from dbo.sys_cidade where cd_estado = {$cd_estado}");
    $cidades = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    $qry = $pdo->query("Select * from dbo.sys_tipo_logradouro");
    $tipos = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    $qry = $pdo->query("Select * from dbo.tbl_convenio");
    $convenios = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    $qry = $pdo->query("Select * from dbo.tbl_profissao");
    $profissoes = $qry->fetchAll(PDO::FETCH_ASSOC);
         
    $agenda_temp = null;
    $qry = $pdo->query("Select dbo.ufnGetGuidID() as id_agenda, convert(varchar(12), getdate(), 103) as dt_agenda");
    $res = $qry->fetchAll(PDO::FETCH_ASSOC);
    foreach($res as $item) {
        $agenda_temp = $item;
    }
    
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
    
    $qry = $pdo->query(
          "Select * "
        . "from dbo.sys_grupo_arquivo g "
        . "where (g.sn_ativo = 1) "
        . "order by g.ds_grupo");
    $grupo_arquivos = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    // Fechar conexão PDO
    unset($res);
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

    $qtde = 10;
    $tipo = 1;
    $file = "../logs/cookies/paciente_" . sha1($user->getCodigo()) . ".json";
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
            Pacientes
            <small>Relação de pacientes cadastrados</small>
            <input type="hidden" id="estacaoID" value="<?php echo $id_estacao;?>">
          </h1>
          <ol class="breadcrumb">
              <li><a href="#"><i class="fa fa-home"></i> Home</a></li>
              <li><a href="#">Central de Cadastros</a></li>
              <li class="active" id="page-click" onclick="preventDefault()">Pacientes</li>
          </ol>
        </section>

        <!-- Main content -->
        <section class="content">

          <!-- Painel de Pesquisa -->
          <div class="box" id="box-filtro">
            <div class="box-header with-border">
              <h3 class="box-title">Filtro(s) da pesquisa</h3>
              <div class="box-tools pull-right">
                <!--<button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Ocultar"><i class="fa fa-minus"></i></button>-->
                <!--<button type="button" class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remover"><i class="fa fa-times"></i></button>-->
                <button type="button" class="btn btn-primary" title="Fechar (Voltar à pesquisa)" onclick="fechar_filtro(false)"><i class="fa fa-close"></i></button>  
              </div>
            </div>

            <div class="box-body form-horizontal">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="cd_tipo_filtro" class="col-sm-2 control-label">Tipo</label>
                        <div class="col-sm-10">
                            <select class="form-control select2"  id="cd_tipo_filtro" style="width: 100%;">
                                <option value='0'>Todos</option>
                                <option value='1'>Apenas pacientes ativos</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="ds_filtro" class="col-sm-2 control-label">Nome</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="ds_filtro" placeholder="Informe um dado para filtro">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                    <button class='btn btn-primary' id='btn_sumit_pesquisa' name='btn_sumit_pesquisa' onclick='fechar_filtro(true)' title="Executar pesquisa"><i class='fa fa-search'></i></button>
                        <select class="form-control select2"  id="qtde-registros-pac" style="width: 70px;">
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
                        </select>
                        <span>&nbsp; Quantidade de registros por paginação</span>
                    </div>
                </div>
            </div>
<!--
            <div class="box-footer">
                    <button class='btn btn-primary' id='btn_sumit_pesquisa' name='btn_sumit_pesquisa' onclick='fechar_filtro(true)' title="Executar pesquisa"><i class='fa fa-search'></i></button>
                    <select class="form-control select2"  id="qtde-registros-pac" style="width: 70px;">
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
                    </select>
                    <span>&nbsp; Quantidade de registros por paginação</span>
            </div>
-->              
          </div>

          <!-- Painel de Resultados -->
          <div class="box box-info" id="box-pesquisa">
              <div class="box-header with-border">
                  <h3 class="box-title">Registros</h3>
                  <div class="box-tools pull-right">
                      <button type="button" class="btn btn-primary" title="Configurar Filtro" onclick="abrir_filtro()" id="btn-configurar-pesquisa"><i class="fa fa-filter"></i></button>
                      <button type="button" class="btn btn-primary" title="Atualizar" onclick="pesquisar_pacientes()" id="btn-atualizar-pesquisa"><i class="fa fa-refresh"></i></button>
                      <button type="button" class="btn btn-primary" title="Novo Cadastro" onclick="novo_cadastro('user_<?php echo $user->getCodigo();?>')" id="btn-novo-cadastro"><i class="fa fa-file-o"></i></button>
                  </div>
              </div>

              <div class="box-body" id="box-tabela">
                <p>Lista de registros resultantes da pesquisa</p>
        <!--                
                <table id='tb-pacientes' class='table table-bordered table-hover'>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Rendering engine</th>
                            <th>Browser</th>
                            <th>Platform(s)</th>
                            <th>Engine version</th>
                            <th>CSS grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id='linha_01'>
                            <td><a href='#' id='cidade_01' onclick='abrir_cadastro(this, this.id);'>01</a></td>
                            <td>Trident</td>
                            <td>Internet Explorer 4.0</td>
                            <td>Win 95+</td>
                            <td> 4</td>
                            <td>X</td>
                        </tr>
                    </tbody>
                </table>
        -->                
              </div>
          </div>

          <!-- Painel de Cadastro -->
          <div class="box box-primary" id="box-cadastro">
              <div class="box-header with-border">
                  <h3 class="box-title">Cadastro</h3>
                  <div class="box-tools pull-right">
                      <input type="hidden" id="id_linha">
                      <input type="hidden" id="operacao">
                      <input type="hidden" id="referencia" value="<?php echo $agenda_temp['id_agenda'];?>">
                      <input type="hidden" id="id_agenda"  value="<?php echo $agenda_temp['id_agenda'];?>">
                      <input type="hidden" id="dt_agenda"  value="<?php echo $agenda_temp['dt_agenda'];?>">
                      <input type="hidden" id="id_atendimento"  value="<?php echo $agenda_temp['id_agenda'];?>">
                      <input type="hidden" id="dt_atendimento"  value="<?php echo $agenda_temp['dt_agenda'];?>">
                      <input type="hidden" id="sn_todos_exames"  value="1">
                      <input type="hidden" id="sn_todas_medidas" value="1">
                      <button type="button" class="btn btn-primary" title="Fechar (Voltar à pesquisa)" onclick="voltar_pesquisa()"><i class="fa fa-close"></i></button>
                  </div>
              </div>

              <div class="row-border">
                  <div class="col-md-12">
                      <div class="nav-tabs-custom">
                          <ul class="nav nav-tabs">
                              <li class="active" id="tab_1a"><a href="#tab_1" data-toggle="tab">Identificação</a></li>
                              <li id="tab_2a"><a href="#tab_2" data-toggle="tab">Outras informações</a></li>
                              <li id="tab_3a"><a href="#tab_3" data-toggle="tab" onclick="historico_atendimento()">Histórico</a></li>
                              <li id="tab_4a"><a href="#tab_4" data-toggle="tab" onclick="carregar_exames()">Exames</a></li>
                              <li id="tab_5a"><a href="#tab_5" data-toggle="tab" onclick="carregar_evolucoes()">Evoluções de Medidas</a></li>
                              <li id="tab_6a"><a href="#tab_6" data-toggle="tab" onclick="carregar_arquivos()">Arquivos</a></li>
                          </ul>

                          <div class="tab-content">
                              
                              <!-- IDENTIFICAÇÃO -->
                              <div class="tab-pane active" id="tab_1">

                                <div class="box-body form-horizontal">
                                  <div class="col-md-12">
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
                                                <option value='0'>Selecione o sexo</option>
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
                                        <div class="col-sm-7 padding-field">
                                            <input type="text" class="form-control proximo_campo" id="nm_acompanhante" maxlength="150" placeholder="Nome do acompanhante" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin: 2px;">
                                        <label for="nm_indicacao" class="col-sm-2 control-label padding-label">Indicado por</label>
                                        <div class="col-sm-7 padding-field">
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
                                        <div class="col-sm-7 padding-field">
                                            <input type="text" class="form-control proximo_campo" id="ds_contatos" maxlength="150" placeholder="Informe aqui outros números para contato...">
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin: 2px;">
                                        <label for="ds_email" class="col-sm-2 control-label padding-label">E-mail(s)</label>
                                        <div class="col-sm-7 padding-field">
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
                                                  <input class="proximo_campo" type="checkbox" id="sn_ativo" value="1"> Cadastro ativo
                                              </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                  </div>
                                </div>

                              </div><!--Fim da TAB 1-->

                              <!-- OUTRAS INFORMAÇÕES -->
                              <div class="tab-pane" id="tab_2">
                                  
                                  <div class="box-body form-horizontal">
                                      <div class="col-md-12">
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
                                  
                              </div><!--Fim da TAB 2-->
                              
                              <!-- HISTÓRICO -->
                              <div class="tab-pane" id="tab_3">
                                    
                              </div><!--Fim da TAB 3-->

                              <!-- EXAMES -->
                              <div class="tab-pane" id="tab_4">
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
                              </div><!--Fim da TAB 4-->

                              <!-- EVOLUÇÕES DE MEDIDAS -->
                              <div class="tab-pane" id="tab_5">
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
                              </div><!--Fim da TAB 5-->
                              
                              <!-- ARQUIVOS -->
                              <div class="tab-pane" id="tab_6">
                                <div class="box no-border">
                                    <div class="row-border">
                                        <div class="form-group" style="margin: 2px;">
                                            <div class="col-sm-2 padding-field">
                                                <select class="form-control select2 no-padding"  id="cd_grupo_arquivo" style='width: 100%;'>
                                                    <option value='0'>Selecione o grupo</option>
                                                    <?php
                                                    foreach($grupo_arquivos as $item) {
                                                        echo "<option value='{$item['cd_grupo']}'>{$item['ds_grupo']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-sm-2 padding-field">
                                                <div class="input-group">
                                                    <div class="input-group-addon">
                                                        <i class="fa fa-calendar"></i>
                                                    </div>
                                                    <input type="hidden" id="cd_arquivo" value="0"/>
                                                    <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask id="dt_arquivo">
                                                </div>
                                            </div>
                                            <div class="col-sm-3 padding-field">
                                                <input type="text" class="form-control proximo_campo" id="ds_arquivo" maxlength="150" placeholder="Informe a descrição do arquivo...">
                                            </div>
                                            <div class="col-sm-3 padding-field">
                                                <input type="hidden" id="tp_arquivo"/>
                                                <!--<input type="file" class="form-control proximo_campo" id="fl_arquivos" multiple/>-->
                                                <form id="form_arquivos">
                                                    <input type="file" class="form-control proximo_campo" accept=".pdf, image/*" name="fl_arquivos[]" id="fl_arquivos" multiple/>
                                                </form>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary" title="Adicionar Arquivo" onclick="inserir_arquivo(event)" id="btn-adicionar-arquivo"><i class="fa fa-plus"></i></button>
                                        <div class="box-tools pull-right">
                                            <button type="button" class="btn btn-primary" title="Atualizar lista de Arquivos"  onclick="carregar_arquivos()" id="btn-atualizar-evolucoes"><i class="fa fa-refresh"></i></button>
                                        </div>
                                    </div>
                                    
                                    <div class="box-body no-padding">
                                        <p class='text-justify'><br><i class='fa fa-spin fa-spinner'></i>&nbsp; 
                                            Você pode fazer upload de arquivos digitalizados do paciente e armazená-los aqui. 
                                            Para isso basta entrar com os dados solicitados acima e adicioná-los a lista.<br>
                                        </p>
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
                  <button type="button" class="btn btn-primary pull-right" id="btn_form_save"  onclick="salvar_cadastro()">Salvar</button>
              </div>
          </div>

            <button type="button" class="btn btn-sm" data-toggle="modal" data-target="#modal-editar-arquivo" id="btn_editar-arquivo"></button>
            
            <div class="modal fade" id="modal-editar-arquivo">
                <div class="modal-dialog"> <!--  modal-lg -->
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="editar-arquivo_title">Editar Dados do Arquivo</h4>
                        </div>
                        <div class="modal-body">
                            <div class="box-body form-horizontal">
                                <div class="col-md-12">
                                    <div class="form-group" style="margin: 2px;">
                                        <label for="ecd_arquivo" class="col-sm-2 control-label padding-label">Controle</label>
                                        <div class="col-sm-3  padding-field">
                                            <input type="hidden" id="linha_arquivo" value=""/>
                                            <input type="hidden" id="eid_arquivo" value=""/>
                                            <input type="text" class="form-control proximo_campo" id="ecd_arquivo" maxlength="10" placeholder="000" readonly/>
                                        </div>
                                        <label for="edt_arquivo" class="col-sm-2 control-label padding-label">Data</label>
                                        <div class="col-sm-5 padding-field">
                                            <div class="input-group">
                                                <div class="input-group-addon">
                                                    <i class="fa fa-calendar"></i>
                                                </div>
                                                <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask id="edt_arquivo"/>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group" style="margin: 2px;">
                                        <label for="ecd_grupo" class="col-sm-2 control-label padding-label">Grupo</label>
                                        <div class="col-sm-10 padding-field">
                                            <select class="form-control select2 no-padding proximo_campo"  id="ecd_grupo" style='width: 100%;'>
                                                <option value='0'>Selecione o grupo</option>
                                                <?php
                                                foreach($grupo_arquivos as $item) {
                                                    echo "<option value='{$item['cd_grupo']}'>{$item['ds_grupo']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group" style="margin: 2px;">
                                        <label for="enm_arquivo" class="col-sm-2 control-label padding-label">Nome</label>
                                        <div class="col-sm-10 padding-field">
                                            <input type="text" class="form-control proximo_campo" id="enm_arquivo" maxlength="150" placeholder="Nome do arquivo..." readonly/>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group" style="margin: 2px;">
                                        <label for="eds_arquivo" class="col-sm-2 control-label padding-label">Descrição</label>
                                        <div class="col-sm-10 padding-field">
                                            <input type="text" class="form-control proximo_campo" id="eds_arquivo" maxlength="150" placeholder="Informe a descrição do arquivo..."/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default pull-left" data-dismiss="modal" id="editar-arquivo_close">Fechar</button>
                            <button type="button" class="btn btn-primary proximo_campo" id="editar-arquivo_confirm" onclick="gravar_dados_arquivo()">Salvar</button>
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
            $('#box-cadastro, #modal-editar-arquivo').on('keyup', '.proximo_campo', function(e) {
                /* 
                 * Verifica se o evento é Keycode (para IE e outros browsers)
                 * se não for pega o evento Which (Firefox)
                 */
                var tecla = (e.keyCode?e.keyCode:e.which);
                if (tecla === 13) {
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
                        try {
                            proximo.focus();
                        } catch (e) {
                        }
                    }
                }
                /* Impede o sumbit caso esteja dentro de um form */
                e.preventDefault(e);
                return false;
            });
            
            <?php
            include '../dist/js/pages/_modal.js';
            ?>

            $('#modal-editar-arquivo').on('shown.bs.modal', function(event) {
                $('#editar-arquivo_close').focus();
            });

            $(function () {
                $.fn.dataTable.moment('DD/MM/YYYY');
                
                $("#btn_msg_padrao").fadeOut(1);
                $("#btn_msg_alerta").fadeOut(1);
                $("#btn_msg_erro").fadeOut(1);
                $("#btn_msg_informe").fadeOut(1);
                $("#btn_msg_primario").fadeOut(1);
                $("#btn_msg_sucesso").fadeOut(1);
                $("#btn_editar-arquivo").fadeOut(1);

                $('#qtde-registros-pac').val(<?php echo $qtde;?>);
                $('#qtde-registros-pac').select2();
                $('#cd_tipo_filtro').val(<?php echo $tipo;?>);
                $('#cd_tipo_filtro').select2();
                
                configurar_checked();
                configurar_tabela('#tb-pacientes');
                
                // Autocomplete para Estados
                var estadosTags = [
                    'Outros'
                    <?php
                        foreach($estados as $item) {
                            echo ", '{$item['nm_estado']}'";
                        }
                    ?>
                ];
                $('#end_estado').autocomplete({
                    source: estadosTags
                });    
                    
                // Autocomplete para Cidades
                var cidadesTags = [
                    'Outras'
                    <?php
                        foreach($cidades as $item) {
                            echo ", '{$item['nm_cidade']}'";
                        }
                    ?>
                ];
                $('#end_cidade').autocomplete({
                    source: cidadesTags
                });    
                    
                // Autocomlete para Profissões
                var profissoesTags = [
                    'Outras'
                    <?php
                        foreach($profissoes as $item) {
                            echo ", '{$item['ds_profissao']}'";
                        }
                    ?>
                ];
                $('#ds_profissao').autocomplete({
                    source: profissoesTags
                });    
            });
            
            $('#box-filtro').hide();
            $('#box-pesquisa').show();
            $('#box-cadastro').hide();
            //$('#box-arquivos').hide();

            $(".select2").select2();      // Ativar o CSS nos "Select"
            $('[data-mask]').inputmask(); // Ativar as máscaras nas "Input"

            pesquisar_pacientes();
            
            if (document.getElementById("box-filtro").style.display === 'block') { $('#btn-configurar-pesquisa').fadeOut() };

            function configurar_checked() {
                $('input').iCheck({
                    checkboxClass: 'icheckbox_square-blue',
                    radioClass   : 'iradio_square-blue',
                    increaseArea : '20%' // optional
                });
            }
            
            function configurar_tabela(id) {
                if (typeof($(id)) !== "undefined") {
                    var qt_registros = parseInt('0' + $('#qtde-registros-pac').val());
                    $(id).DataTable({
                        "paging": true,
                        "pageLength": qt_registros, // Quantidade de registrs para paginação
                        "lengthChange": false,
//                        "lengthMenu": [
//                            [10, 11, 12, 13, 14, 15, 20, 25, 50, -1],
//                            ['10', '11', 12', '13', '14', '15', '20', '25', '50', 'Todos']
//                        ],
                        "searching": true,
                        "ordering": true,
                        "info": true,
                        "autoWidth": true,
                        "processing": true,
                        "columns": [
                            { "width": "30px" },  // 0. Código
                            null,                 // 1. Nome
                            null,                 // 2. Fone
                            null,                 // 3. Idade
                            null,                 // 4. RG
                            null,                 // 5. CPF
                            { "width": "10px" },  // 6. Status
                            { "width": "5px"  }   // 7. <Excluir>
                        ],
                        "columnDefs": [
                            {"orderable": false, "targets": 2}, // Fone
                            {"orderable": false, "targets": 3}, // Idade
                            {"orderable": false, "targets": 6}, // Status
                            {"orderable": false, "targets": 7}  // <Excluir>
                        ],
                        "order": [[1, 'asc']], // "order": [] <-- Ordenação indefinida (Nome)
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
                            {"orderable": false, "targets": 3},  // <Opções>
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
            
            function abrir_filtro() { 
                $('#box-filtro').fadeIn(); 
                $('#btn-configurar-pesquisa').fadeOut(); 
            } 
            
            function abrir_pesquisa() { 
                $('#box-pesquisa').fadeIn(); 
            } 
            
            function abrir_cadastro(handler, id) {
                // Descarcar registro selecionado
                var linha = $('#id_linha').val();
                if ((linha !== '') && (typeof($(linha)) !== 'undefined')) {
                    $(linha).removeClass("text-bold");
                    $(linha).removeClass("bg-gray-light");
                    $(linha).removeClass("bg-gray");
                }
                // Selecionar novo registro
                var tr = $(handler).closest('tr');
                tr.addClass("text-bold");
                tr.addClass("bg-gray-light");
                $('#id_linha').val( '#' + $(tr).attr('id') );    
                var registro = id.replace("paciente_", "");
                
                novo_arquivo();
                carregar_registro_paciente(registro, function(data){
                    $('#operacao').val("editar");

                    // Identificação
                    $('#cd_paciente').val(zero_esquerda(data.registro[0].prontario, 7));
                    $('#nm_paciente').val(data.registro[0].nome);
                    $('#dt_nascimento').val(data.registro[0].nascimento);
                    $('#tp_sexo').val(data.registro[0].sexo);
                    $('#cd_profissao').val( get_value(data.registro[0].codigo_profissao, 0) );
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
                        $('#cd_estado').val( get_value(data.registro[0].estado, 0) );
                        $('#cd_cidade').val( get_value(data.registro[0].cidade, 0) );
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
                    $('#tp_endereco').val( get_value(data.registro[0].tipo, 0) );
                    $('#ds_endereco').val(data.registro[0].endereco);
                    $('#nr_endereco').val(data.registro[0].numero);
                    $('#ds_complemento').val(data.registro[0].complemento);
                    $('#nm_bairro').val(data.registro[0].bairro);
                    $('#nr_cep').val(data.registro[0].cep);

                    // Contatos
                    $('#nr_telefone').val(data.registro[0].fone);
                    $('#nr_celular').val(data.registro[0].celular);
                    $('#ds_contatos').val(data.registro[0].contatos);
                    $('#ds_email').val(data.registro[0].email);
                    // Outras informações
                    $('#cd_convenio').val(data.registro[0].convenio);
                    $('#nr_matricula').val(data.registro[0].matricula);
                    $('#nm_indicacao').val(data.registro[0].indicacao);
                    $('#ds_alergias').val(data.registro[0].alergias);
                    $('#ds_observacoes').val(data.registro[0].observacoes);
                    $('#sn_ativo').prop('checked', (parseInt(data.registro[0].ativo) === 1)).iCheck('update');
                    
                    $('.select2').select2();
                    ativar_guia('#tab_1');
                    
                    $('#box-filtro').hide();
                    $('#box-pesquisa').hide();
                    $('#box-cadastro').show();
                    //$('#box-arquivos').show();
                    
                    $('#box-cadastro').fadeIn();
                    //$('#box-arquivos').fadeIn();
                });
            } 
            
            function novo_cadastro(usuario) {
                var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso = "0100";
                get_allow_user(usuario, rotina, acesso, function(){
                    $('#operacao').val("inserir");
                    
                    // Identificação
                    $('#cd_paciente').val("");
                    $('#nm_paciente').val("");
                    $('#dt_nascimento').val("");
                    $('#tp_sexo').val("0");
                    $('#cd_profissao').val("0");
                    $('#ds_profissao').val("");
                    $('#nr_rg').val("");
                    $('#ds_orgao_rg').val("");
                    $('#dt_emissao_rg').val("");
                    $('#nr_cpf').val("");
                    $('#nm_acompanhante').val("");
                    $('#nm_pai').val("");
                    $('#nm_mae').val("");
                    
                    // Endereço
                    if (typeof($('#end_logradouro')) !== "undefined") {
                        // Customizado
                        $('#cd_estado').val(<?php echo $cd_estado;?>);
                        $('#cd_cidade').val(<?php echo $cd_cidade;?>);
                        $('#end_logradouro').val("");
                        $('#end_bairro').val("");
                        $('#end_estado').val("<?php echo $nm_estado;?>");
                        $('#end_cidade').val("<?php echo $nm_cidade;?>");
                    } else {
                        // Normalização
                        $('#cd_estado').val(<?php echo $cd_estado;?>);
                        listar_cidades_cadastro('<?php echo 'cidade_' . $cd_cidade;?>');
                    }    
                    $('#tp_endereco').val("0");
                    $('#ds_endereco').val("");
                    $('#nr_endereco').val("");
                    $('#ds_complemento').val("");
                    $('#nm_bairro').val("");
                    $('#nr_cep').val("");
                    
                    // Contatos
                    $('#nr_telefone').val("");
                    $('#nr_celular').val("");
                    $('#ds_contatos').val("");
                    $('#ds_email').val("");
                    
                    // Outras informações
                    $('#cd_convenio').val(<?php echo $cd_convenio;?>);
                    $('#nr_matricula').val("");
                    $('#nm_indicacao').val("");
                    $('#ds_alergias').val("");
                    $('#ds_observacoes').val("");
                    $('#sn_ativo').prop('checked', true).iCheck('update');
                    
                    $('.select2').select2();
                    ativar_guia('#tab_1');
                    novo_arquivo();
                    
                    $('#box-filtro').hide();
                    $('#box-pesquisa').hide();
                    $('#box-cadastro').show();
                    //$('#box-arquivos').show();

                    $('#box-cadastro').fadeIn();
                    //$('#box-arquivos').fadeIn();
                });
            }

            function novo_arquivo() {
                $('#cd_grupo_arquivo').val("0");
                $('#cd_arquivo').val("0");
                $('#dt_arquivo').val("");
                $('#ds_arquivo').val("");
                $('#fl_arquivos').val("");
                $('#cd_grupo_arquivo').select2();
                $('#panel-arquivo_paciente').html("<embed id='arquivo' src='./logs/storage/paciente/empty.png?<?= $versao; ?>' type='image/png' width='100%' style='height : auto;'>");
                var doc = document.getElementById('arquivo'); 
                $('#panel-arquivo_paciente').height( doc.height );
            }
            
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
                if ( $('#tab_5').hasClass("active") ) $('#tab_5').removeClass("active");
                if ( $('#tab_6').hasClass("active") ) $('#tab_6').removeClass("active");
                
                if (typeof($(id)) !== "undefined") $(id).addClass("active");
                if (typeof($(id + 'a')) !== "undefined") $(id + 'a').addClass("active");
            }    
            
            function fechar_filtro(pesquisar) {
                $('#btn-configurar-pesquisa').fadeIn();
                //$('#box-filtro').fadeOut(); <-- Muito lento para o contexto
                $('#box-filtro').hide();
                if (pesquisar === true) pesquisar_pacientes();
            } 
            
            function fechar_pesquisa() { 
                $('#box-pesquisa').fadeOut(); 
            } 
            
            function fechar_cadastro() {
                $('#box-cadastro').fadeOut(); 
                //$('#box-arquivos').fadeOut();
            } 
            
            function voltar_pesquisa() {
                $('#box-pesquisa').show();
                $('#box-cadastro').hide();
                //$('#box-arquivos').hide();
            }
                
            function salvar_cadastro() {
                var inserir   = ($('#operacao').val() === "inserir");
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
                //if (($('#ds_endereco').val() !== "")  && ($('#nr_endereco').val() === ""))  requedido += "<li>Número do endereço</li>";
                if ($('#cd_convenio').val() === "0")  requedido += "<li>Convêvio</li>";

                if (requedido !== "") {
                    show_campos_requeridos("Alerta", "Cadastro do Paciente", requedido);
                } else {    
                    salvar_registro_paciente(registro, function(data){
                        if (inserir) {
                            $('#cd_paciente').val(data.registro[0].prontuario);
                            var newRow = $(data.registro[0].tr_table);
                            $("#tb-pacientes").append(newRow);
                        } else {
                            var tr_table = document.getElementById("tr-linha_" + data.registro[0].prontuario); 
                            var colunas  = tr_table.getElementsByTagName('td');
                            
                            colunas[1].firstChild.nodeValue = data.registro[0].nome;
                            colunas[2].firstChild.nodeValue = data.registro[0].fone;
                            colunas[3].firstChild.nodeValue = data.registro[0].idade;
                            colunas[4].firstChild.nodeValue = data.registro[0].rg;
                            colunas[5].firstChild.nodeValue = data.registro[0].cpf;
                        }
                        
                        var linha = "#tr-linha_" + parseFloat($('#cd_paciente').val());
                        $('#id_linha').val(linha);

                        // Atualizar status do registro na tabela
                        var referencia = parseInt("0" + $('#cd_paciente').val());
                        set_status_fa('#status_paciente_' + referencia, $('#sn_ativo').is(":checked"));
                        
                        // Destacar linha na tabela
                        if ((linha !== '') && (typeof($(linha)) !== 'undefined')) {
                            $(linha).removeClass("bg-gray-light");
                            $(linha).addClass("text-bold");
                            $(linha).addClass("bg-gray");

                            voltar_pesquisa(); 
                        }
                    });
                }
            }
            
            function listar_cidades_cadastro(cidade) {
                listar_cidades_select('#cd_estado', '#div-cidades-cadastro', '#cd_cidade', function() {
                    //$("#cd_cidade option[value='0']").remove(); <-- Funciona
                    $('#cd_cidade')[0].options[0].value = 0;
                    $('#cd_cidade')[0].options[0].text  = "Selecione a cidade";
                    
                    if (cidade !== null) {
                        var registro  = cidade.replace("cidade_", "");
                        var valor = parseInt("0" + registro);
                        if (valor !== 0) $('#cd_cidade').val(valor);
                    }    
                    
                    $('#cd_cidade').select2(); 
                });
            }
            
            function buscar_endereco(id) {
                var cep = $(id).val().trim();
                buscar_cep(cep, function(data){
                    if (parseInt("0" + data.registro.length) === 0) {
                        show_alerta("CEP", "Número de Cep não localizado.");
                    } else {
                        if (data.registro[0].estado !== $('#cd_estado').val()) {
                            $('#cd_estado').val(data.registro[0].estado);
                        }

                        if (data.registro[0].cidade !== $('#cd_cidade').val()) {
                            listar_cidades_cadastro('cidade_' + data.registro[0].cidade);
                        }    

                        $('#tp_endereco').val(data.registro[0].tipo);
                        $('#ds_endereco').val(data.registro[0].logradouro);
                        $('#nm_bairro').val(data.registro[0].bairro);

                        $('.select2').select2();
                    }    
                });
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
            
            function excluir_registro(id, elemento) {
                var usuario = "user_<?php echo $user->getCodigo();?>";
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
            
            function excluir_arquivo(id) {
                var usuario = "user_<?php echo $user->getCodigo();?>";
                var rotina  = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso  = "0100";
                get_allow_user(usuario, rotina, acesso, function(){
                    var registro  = id.replace("excluir_arquivo_", "");
                    var tr_table  = document.getElementById("tr-linhaarquivo_" + registro); 
                    var colunas   = tr_table.getElementsByTagName('td');
                    var descricao = colunas[2].firstChild.nodeValue;
                    excluir_arquivo_paciente(registro, descricao, function(){
                        RemoveTableRow(tr_table);
                    });
                });
            }    
            
            function editar_arquivo(id) {
                var usuario = "user_<?php echo $user->getCodigo();?>";
                var rotina  = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso  = "0100";
                get_allow_user(usuario, rotina, acesso, function(){
                    var registro  = id.replace("editar_arquivo_", "");
                    var tr_table  = document.getElementById("tr-linhaarquivo_" + registro); 
                    var colunas   = tr_table.getElementsByTagName('td');
                    var descricao = colunas[2].firstChild.nodeValue;
                    visualizar_arquivo_paciente(registro, function(data) {    
                        console.log(descricao); // <-- Teste
                        // Descarcar registro selecionado
                        var linha_arquivo = $('#linha_arquivo').val();
                        if ((linha_arquivo !== '') && (typeof($(linha_arquivo)) !== 'undefined')) {
                            $(linha_arquivo).removeClass("text-bold");
                            $(linha_arquivo).removeClass("bg-gray-light");
                            $(linha_arquivo).removeClass("bg-gray");
                        }
                        // Selecionar novo registro
                        var tr = $(tr_table).closest('tr');
                        tr.addClass("text-bold");
                        tr.addClass("bg-gray-light");
                        $('#linha_arquivo').val( '#' + $(tr).attr('id') ); 
                        // Carregar dados retornados
                        $('#eid_arquivo').val(data.id);
                        $('#ecd_arquivo').val(zero_esquerda(data.codigo, 7));
                        $('#edt_arquivo').val(data.data);
                        $('#enm_arquivo').val(data.nome + "." + data.extensao);
                        $('#eds_arquivo').val(data.descricao);
                        $('#ecd_grupo').val(data.grupo);
                        $('#ecd_grupo').select2();
                        $('#btn_editar-arquivo').trigger('click');
                    });
                });
            }    
            
            function historico_atendimento(){
                var id_agenda   = $('#id_agenda').val();
                var dt_agenda   = $('#dt_agenda').val();
                var cd_paciente = $('#cd_paciente').val();
                $('#tab_3').html("<i class='fa fa-spin fa-refresh'></i>&nbsp; Buscando histórico de atendimentos anteriores ao dia " + dt_agenda + ", <strong>aguarde</strong>!");
                carregar_historico_atendimento(id_agenda, dt_agenda, cd_paciente, function(data){
                    $('#tab_3').html(data);
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
                                {"orderable": true, "targets": 0}, // Data
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
            
            function inserir_exame() {
                //$('#cd_paciente').val( $('#cd_paciente_ag').val() );
                
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
            
            function inserir_arquivo(event) {
                var usuario = "user_<?php echo $user->getCodigo();?>";
                var rotina  = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso  = "0100";
                get_allow_user(usuario, rotina, acesso, function() {
                    var paciente   = parseFloat("0" + $('#cd_paciente').val());
                    if (paciente === 0) {
                        show_alerta("Novo Arquivo", "Paciente não cadastrado ou não informado.");
                    } else
                    if ($('#fl_arquivos').val() === '') {
                        show_alerta("Novo Arquivo", "Selecione o arquivo do paciente para upload.");
                    } else {
                        upload_arquivo_paciente(event, paciente, function(){
                            novo_arquivo();
                            carregar_arquivos();
                        });
                    }
                });
            }
            
            function carregar_exames() {
                var referencia = $('#id_agenda').val();
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
                var referencia = $('#id_agenda').val();
                var paciente   = parseFloat("0" + $('#cd_paciente').val());
                $('#id_atendimento').val(referencia);
                $('#dt_atendimento').val($('#dt_agenda').val());
                $('#box-tabela_evolucoes').html("<p class='text-center'><br><i class='fa fa-spin fa-refresh'></i>&nbsp; Buscando evoluções do paciente, <strong>aguarde</strong>!<br></p>");
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

            function carregar_arquivos() {
                var paciente   = parseFloat("0" + $('#cd_paciente').val());
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
                    $('#panel-arquivo_paciente').html("<embed id='arquivo' src='" + retorno.url + "?<?= $versao ?>' type='" + retorno.tipo + "' width='100%' style='height : auto;'>");
                    var doc = document.getElementById('arquivo'); 
                    $('#panel-arquivo_paciente').height( doc.height );
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
                                // carregar_exames();  <-- Está causando muita lentidão
                                show_informe("Salvar Exames", "Resultado(s) gravado(s) com sucesso.");
                            });
                        } else {
                            show_alerta("Salvar Exames", "Favor informe o(s) resultado(s) do(s) exame(s)");
                        }
                    }
                }
            }
            
            function gravar_resultados_evolucoes() {
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
                                // carregar_evolucoes();  <-- Está causando muita lentidão
                                show_informe("Salvar Evoluções", "Resultado(s) gravado(s) com sucesso.");
                            });
                        } else {
                            show_alerta("Salvar Evoluções", "Favor informe o(s) resultado(s) da(s) evolução(ões)");
                        }
                    }
                }
            }
            
            function gravar_dados_arquivo() {
                var registro  = parseFloat($('#ecd_arquivo').val());
                var requedido = "";
                
                if (!validar_data($('#edt_arquivo').val())) requedido += "<li>Data do arquivo</li>";
                if ($('#ecd_grupo').val() === "0")  requedido += "<li>Grupo do arquivo</li>";
                if ($('#eds_arquivo').val() === "") requedido += "<li>Descrição do arquivo</li>";

                if (requedido !== "") {
                    show_campos_requeridos("Alerta", "Dados do Arquivo", requedido);
                } else {    
                    $('#editar-arquivo_confirm').prop('disabled', true);
                    salvar_dados_arquivo_paciente(registro, function(){
                        var tr_table  = document.getElementById("tr-linhaarquivo_" + registro); 
                        var colunas   = tr_table.getElementsByTagName('td');
                        colunas[0].firstChild.nodeValue = $('#ecd_grupo option:selected').text();
                        colunas[1].firstChild.nodeValue = $('#edt_arquivo').val();
                        colunas[2].firstChild.nodeValue = $('#eds_arquivo').val();
                        $('#editar-arquivo_confirm').prop('disabled', false);
                        $('#editar-arquivo_close').trigger('click');
                    });
                }
            }
            
            function imprimir_controle_exames(handler) {
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
