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
    $cd_cidade   = "1501402"; // BELÉM
    $cd_convenio = "0";
    
    $pdo = Conexao::getConnection();
    
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
    
//    $qry = $pdo->query("Select * from dbo.tbl_profissao");
//    $profissoes = $qry->fetchAll(PDO::FETCH_ASSOC);
            
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
            </div>

            <div class="box-footer">
                    <button class='btn btn-primary' id='btn_sumit_pesquisa' name='btn_sumit_pesquisa' onclick='fechar_filtro(true)' title="Executar pesquisa"><i class='fa fa-search'></i></button>
                    <!--<button class='btn btn-primary' id='btn_sumit_pesquisa' name='btn_novo_formulario' onclick='' title="Novo Registro"><i class='fa fa-file-o'></i></button>-->
                    <!--<button class='btn btn-primary' id='btn_reset_limpar'   name='btn_reset_limpar'    onclick='' title="Preparar nova pesquisa"><i class='fa  fa-eraser' ></i></button>-->
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
                      <button type="button" class="btn btn-primary" title="Fechar (Voltar à pesquisa)" onclick="voltar_pesquisa()"><i class="fa fa-close"></i></button>
                  </div>
              </div>

              <div class="row-border">
                  <div class="col-md-12">
                      <div class="nav-tabs-custom">
                          <ul class="nav nav-tabs">
                              <li class="active" id="tab_1a"><a href="#tab_1" data-toggle="tab">Identificação</a></li>
                              <li id="tab_2a"><a href="#tab_2" data-toggle="tab">Endereço</a></li>
                              <li id="tab_3a"><a href="#tab_3" data-toggle="tab">Contatos</a></li>
                              <li id="tab_4a"><a href="#tab_4" data-toggle="tab">Outras informações</a></li>
                          </ul>

                          <div class="tab-content">
                              <div class="tab-pane active" id="tab_1">

                                <div class="box-body form-horizontal">
                                  <div class="col-md-8">
                                      <div class="form-group" style="margin: 2px;">
                                          <label for="cd_paciente" class="col-sm-2 control-label">Prontuário</label>
                                          <div class="col-sm-2">
                                              <input type="text" class="form-control" id="cd_paciente" maxlength="10" placeholder="0000000" readonly>
                                          </div>
                                      </div>
                                      
                                      <div class="form-group" style="margin: 2px;">
                                          <label for="nm_paciente" class="col-sm-2 control-label">Nome</label>
                                          <div class="col-sm-10">
                                              <input type="text" class="form-control proximo_campo" id="nm_paciente" maxlength="200" placeholder="Informe nome completo do paciente" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                          </div>
                                      </div>
                                      
                                      <div class="form-group" style="margin: 2px;">
                                          <label for="dt_nascimento" class="col-sm-2 control-label">D.Nascimento</label>
                                          <div class="col-sm-3">
                                              <div class="input-group">
                                                  <div class="input-group-addon">
                                                      <i class="fa fa-calendar"></i>
                                                  </div>
                                                  <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask id="dt_nascimento">
                                              </div>
                                          </div>
                                          <label for="tp_sexo" class="col-sm-1 control-label">Sexo</label>
                                          <div class="col-sm-2">
                                              <select class="form-control select2 proximo_campo"  id="tp_sexo" style="width: 100%;">
                                                  <option value='0'>Selecione o sexo</option>
                                                  <option value='M'>Masculino</option>
                                                  <option value='F'>Feminino</option>
                                                  <!--<option value='N'>Não declarado</option>-->
                                                  <!--<option value='I'>Indefinido</option>-->
                                              </select>
                                          </div>
                                          <label for="ds_profissao" class="col-sm-1 control-label">Profissão</label>
                                          <div class="col-sm-3">
                                              <input type="hidden" id="cd_profissao" value="0">
                                              <input type="text" class="form-control proximo_campo" id="ds_profissao" maxlength="150" placeholder="Descreva as profissões...">
                                              <!--<select class="form-control select2 proximo_campo" id="cd_profissao" style="width: 100%;" onchange="set_focus('#nr_rg')">-->
                                                <!--<option value='0'>Selecione a profissão</option>-->
                                                <?php
//                                                    foreach($profissoes as $item) {
//                                                        echo "<option value='{$item['cd_profissao']}'>{$item['ds_profissao']}</option>";
//                                                    }
                                                ?>
                                              </select>
                                          </div>
                                      </div>
                                      
                                      <div class="form-group" style="margin: 2px;">
                                          <label for="nr_rg" class="col-sm-2 control-label">RG</label>
                                          <div class="col-sm-3">
                                              <input type="text" class="form-control proximo_campo" id="nr_rg" maxlength="10" placeholder="Registro Geral">
                                          </div>
                                          <label for="ds_orgao_rg" class="col-sm-1 control-label">Orgão</label>
                                          <div class="col-sm-2">
                                              <input type="text" class="form-control proximo_campo" id="ds_orgao_rg" maxlength="10" placeholder="Orgão/UF" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                          </div>
                                          <input type="hidden" id="dt_emissao_rg" value="">
<!--                                          
                                          <label for="dt_emissao_rg" class="col-sm-1 control-label">D.Emissão</label>
                                          <div class="col-sm-3">
                                              <div class="input-group">
                                                  <div class="input-group-addon">
                                                      <i class="fa fa-calendar"></i>
                                                  </div>
                                                  <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask id="dt_emissao_rg">
                                              </div>
                                          </div>
-->                                              
                                      </div>
                                      
                                      <div class="form-group" style="margin: 2px;">
                                          <label for="nr_cpf" class="col-sm-2 control-label">CPF</label>
                                          <div class="col-sm-3">
                                              <input type="text" class="form-control proximo_campo" data-inputmask='"mask": "999.999.999-99"' data-mask id="nr_cpf">
                                          </div>
                                          <label for="nm_acompanhante" class="col-sm-1 control-label">Acomp.</label>
                                          <div class="col-sm-6">
                                              <input type="text" class="form-control proximo_campo" id="nm_acompanhante" maxlength="150" placeholder="Nome do acompanhante" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                          </div>
                                      </div>
                                      
                                      <input type="hidden" id="nm_pai" value="">
                                      <input type="hidden" id="nm_mae" value="">
<!--                                      
                                      <div class="form-group" style="margin: 2px;">
                                          <label for="nm_pai" class="col-sm-2 control-label">Pai</label>
                                          <div class="col-sm-10">
                                              <input type="text" class="form-control proximo_campo" id="nm_pai" maxlength="200" placeholder="Informe nome completo do pai" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                          </div>
                                      </div>
                                      
                                      <div class="form-group" style="margin: 2px;">
                                          <label for="nm_mae" class="col-sm-2 control-label">Mãe</label>
                                          <div class="col-sm-10">
                                              <input type="text" class="form-control proximo_campo" id="nm_mae" maxlength="200" placeholder="Informe nome completo da mãe" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                          </div>
                                      </div>
-->                                      
                                      <div class="form-group" style="margin: 2px;">
                                          <div class="col-sm-2"></div>
                                          <div class="col-sm-10">
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

                              <div class="tab-pane" id="tab_2">
                                  <div class="box-body form-horizontal">
                                      <div class="col-md-8">
                                          
                                        <div class="form-group" style="margin: 2px;">
                                            <label for="cd_estado" class="col-sm-2 control-label">Estado</label>
                                            <div class="col-sm-3">
                                                <select class="form-control select2 proximo_campo"  id="cd_estado" style="width: 100%;" onchange="listar_cidades_cadastro('cidade_0')">
                                                    <option value='0'>Selecione o estado</option>
                                                    <?php
                                                        foreach($estados as $item) {
                                                            echo "<option value='{$item['cd_estado']}'>{$item['nm_estado']}</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <label for="cd_cidade" class="col-sm-1 control-label">Cidade</label>
                                            <div class="col-sm-6" id="div-cidades-cadastro">
                                                <select class="form-control select2 proximo_campo"  id="cd_cidade" style="width: 100%;">
                                                    <option value='0'>Selecione a cidade</option>
                                                    <?php
                                                        foreach($cidades as $item) {
                                                            echo "<option value='{$item['cd_cidade']}'>{$item['nm_cidade']}</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                          
                                        <div class="form-group" style="margin: 2px;">
                                            <label for="tp_endereco" class="col-sm-2 control-label">Endereço</label>
                                            <div class="col-sm-7">
                                                <!--<input type="text" class="form-control" id="ds_endereco" maxlength="200" placeholder="Descrição do endereço">-->
                                                <table border="0" style="width: 100%;">
                                                    <tr>
                                                        <td style="width: 25%;">
                                                            <select class="form-control select2 proximo_campo"  id="tp_endereco" style="width: 98%;">
                                                                <option value='0'>Selecione o tipo</option>
                                                                <?php
                                                                    foreach($tipos as $item) {
                                                                        echo "<option value='{$item['cd_tipo']}'>{$item['ds_tipo']}</option>";
                                                                    }
                                                                ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control proximo_campo" id="ds_endereco" maxlength="200" placeholder="Descrição do endereço" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <label for="nr_endereco" class="col-sm-1 control-label">Número</label>
                                            <div class="col-sm-2">
                                                <input type="text" class="form-control proximo_campo" id="nr_endereco" maxlength="10" placeholder="S/N">
                                            </div>
                                        </div>
                                          
                                        <div class="form-group" style="margin: 2px;">
                                            <label for="nm_bairro" class="col-sm-2 control-label">Bairro</label>
                                            <div class="col-sm-3">
                                                <input type="text" class="form-control proximo_campo" id="nm_bairro" maxlength="150" placeholder="Nome do bairro" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                            </div>
                                            <label for="ds_complemento" class="col-sm-1 control-label">Compl.</label>
                                            <div class="col-sm-6">
                                                <input type="text" class="form-control proximo_campo" id="ds_complemento" maxlength="100" placeholder="Conjunto, apartamento, ETC.">
                                            </div>
                                        </div>
                                          
                                        <div class="form-group" style="margin: 2px;">
                                            <label for="nr_cep" class="col-sm-2 control-label">Cep</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <input type="text" class="form-control proximo_campo" data-inputmask='"mask": "99999-999"' data-mask id="nr_cep">
                                                    <div class="input-group-addon">
                                                        <a href="javascript:preventDefault()" onclick="buscar_endereco('#nr_cep')"><i class="fa fa-search"></i></a>
                                                        <!--<button type="button" class="btn-sm btn-primary" title="Buscar Endereço" onclick="buscar_endereco('#nr_cep')"><i class="fa fa-search"></i></button>-->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                          
                                      </div>
                                  </div>
                              </div>

                              <div class="tab-pane" id="tab_3">
                                  <div class="box-body form-horizontal">
                                      <div class="col-md-8">
                                          
                                        <div class="form-group" style="margin: 2px;">
                                            <label for="nr_telefone" class="col-sm-2 control-label">Tefefone</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-addon">
                                                        <i class="fa fa-phone"></i>
                                                    </div>
                                                    <input type="text" class="form-control proximo_campo" data-inputmask='"mask": "(99)9999-9999"' data-mask id="nr_telefone">
                                                </div>
                                            </div>
                                            <label for="nr_celular" class="col-sm-1 control-label">Celular</label>
                                            <div class="col-sm-3">
                                                <div class="input-group">
                                                    <div class="input-group-addon">
                                                        <i class="fa fa-phone"></i>
                                                    </div>
                                                    <input type="text" class="form-control proximo_campo" data-inputmask='"mask": "(99)99999-9999"' data-mask id="nr_celular">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group" style="margin: 2px;">
                                            <label for="ds_contatos" class="col-sm-2 control-label">Outros contatos</label>
                                            <div class="col-sm-7">
                                                <input type="text" class="form-control proximo_campo" id="ds_contatos" maxlength="150" placeholder="Informe aqui outros números para contato...">
                                            </div>
                                        </div>
                                          
                                        <div class="form-group" style="margin: 2px;">
                                            <label for="ds_email" class="col-sm-2 control-label">E-mail(s)</label>
                                            <div class="col-sm-7">
                                                <div class="input-group">
<!--                                                    <div class="input-group-addon">
                                                        <i class="fa fa-envelope"></i>
                                                    </div>-->
                                                    <span class="input-group-addon">@</span>
                                                    <input type="email" class="form-control proximo_campo" id="ds_email" placeholder="Informe o(s) e-mail(s) do paciente" onkeyup="javascript: this.value = texto_minusculo(this);">
                                                </div>
                                            </div>
                                        </div>
                                          
                                      </div>
                                  </div>
                              </div>

                              <div class="tab-pane" id="tab_4">
                                  <div class="box-body form-horizontal">
                                      <div class="col-md-8">
                                          <input type="hidden" id="cd_convenio"  value="0">
                                          <input type="hidden" id="nr_matricula" value="">
<!--                                          
                                        <div class="form-group" style="margin: 2px;">
                                            <label for="cd_convenio" class="col-sm-2 control-label">Convênio</label>
                                            <div class="col-sm-4">
                                                <select class="form-control select2 proximo_campo"  id="cd_convenio" style="width: 100%;" disabled>
                                                    <option value='0'>Selecione o convênio</option>
                                                    <?php
                                                        foreach($convenios as $item) {
                                                            echo "<option value='{$item['cd_convenio']}'>{$item['nm_resumido']}</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <label for="nr_matricula" class="col-sm-1 control-label">Matrícula</label>
                                            <div class="col-sm-5">
                                                <input type="text" class="form-control proximo_campo" id="nr_matricula" placeholder="Matrícula no convênio" disabled>
                                            </div>
                                        </div>
-->                                          
                                        <div class="form-group" style="margin: 2px;">
                                            <label for="nm_indicacao" class="col-sm-2 control-label">Indicado por</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control proximo_campo" id="nm_indicacao" maxlength="150" placeholder="Nome de quem indicou" onkeyup="javascript: this.value = texto_maiusculo(this);">
                                            </div>
                                        </div>
                                          
                                        <div class="form-group" style="margin: 2px;">
                                            <label for="ds_alergias" class="col-sm-2 control-label">Alergias</label>
                                            <div class="col-sm-10">
                                                <textarea class="form-control" rows="5" id="ds_alergias" placeholder="Descreva as alergias do paciente caso tenha..." style="width: 100%;"></textarea>
                                            </div>
                                        </div>
                                          
                                        <div class="form-group" style="margin: 2px;">
                                            <label for="ds_observacoes" class="col-sm-2 control-label">Observações</label>
                                            <div class="col-sm-10">
                                                <textarea class="form-control" rows="5" id="ds_observacoes" placeholder="Observações em geral..." style="width: 100%;"></textarea>
                                            </div>
                                        </div>
                                          
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
                }
                /* Impede o sumbit caso esteja dentro de um form */
                e.preventDefault(e);
                return false;
            });
            
            $(function () {
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
//                            $('#qtde-registros-conv').val(data.filtro[qtde - 1].qt_registro);
//                            $('#qtde-registros-conv').select2();
//                        }
//                    });
//                } catch (er) {
//                }
                $('#qtde-registros-pac').val(<?php echo $qtde;?>);
                $('#qtde-registros-pac').select2();
                $('#cd_tipo_filtro').val(<?php echo $tipo;?>);
                $('#cd_tipo_filtro').select2();
                
                configurar_checked();
                configurar_tabela('#tb-pacientes');
            });
            
            $('#box-filtro').hide();
            $('#box-pesquisa').show();
            $('#box-cadastro').hide();

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
                    
                    $('#box-cadastro').fadeIn(); 
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
                    $('#cd_estado').val(<?php echo $cd_estado;?>);
                    listar_cidades_cadastro('<?php echo 'cidade_' . $cd_cidade;?>');
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
                    
                    $('#box-filtro').hide();
                    $('#box-pesquisa').hide();
                    $('#box-cadastro').show();

                    $('#box-cadastro').fadeIn(); 
                });
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
            } 
            
            function voltar_pesquisa() {
                $('#box-pesquisa').show();
                $('#box-cadastro').hide();
            }
                
            function salvar_cadastro() {
                var registro  = $('#cd_paciente').val();
                var requedido = "";

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
                    salvar_registro_paciente(registro, function(){
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
        </script>
    </body>
</html>
