<!DOCTYPE html>
<?php
    ini_set('default_charset', 'UTF-8');
    ini_set('display_errors', true);
    error_reporting(E_ALL);
    date_default_timezone_set('America/Belem');
    
    require '../dist/php/constantes.php';
    require '../dist/dao/conexao.php';
    require '../dist/php/usuario.php';
    
    $id_estacao = md5($_SERVER["REMOTE_ADDR"]);
    
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
    $file = "../logs/cookies/configurar_agenda_" . sha1($user->getCodigo()) . ".json";
    if (file_exists($file)) {
        $file_cookie = file_get_contents($file);
        $json = json_decode($file_cookie);
        if (isset($json->filtro[0])) {
            $qtde = (int)$json->filtro[0]->qt_registro;
        }
    }
?>
<html>
  <body class="hold-transition skin-blue sidebar-mini">
    
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h1>
            Agenda Médica
            <small>Painel de configurações para disponibilidades das agendas</small>
            <input type="hidden" id="estacaoID" value="<?php echo $id_estacao;?>">
          </h1>
          <ol class="breadcrumb">
              <li><a href="#"><i class="fa fa-home"></i> Home</a></li>
              <li><a href="#">Recepção</a></li>
              <li><a href="#">Configurações</a></li>
              <li class="active" id="page-click" onclick="preventDefault()">Agenda Médica</li>
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

            <div class="box-footer">
                    <button class='btn btn-primary' id='btn_sumit_pesquisa' name='btn_sumit_pesquisa' onclick='fechar_filtro(true)' title="Executar pesquisa"><i class='fa fa-search'></i></button>
                    <!--<button class='btn btn-primary' id='btn_sumit_pesquisa' name='btn_novo_formulario' onclick='' title="Novo Registro"><i class='fa fa-file-o'></i></button>-->
                    <!--<button class='btn btn-primary' id='btn_reset_limpar'   name='btn_reset_limpar'    onclick='' title="Preparar nova pesquisa"><i class='fa  fa-eraser' ></i></button>-->
                    <select class="form-control select2"  id="qtde-registros-conf" style="width: 70px;">
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

          <!-- Painel de Pesquisa -->
          <div class="box box-info" id="box-pesquisa">
              <div class="box-header with-border">
                  <h3 class="box-title">Registros</h3>
                  <div class="box-tools pull-right">
                      <button type="button" class="btn btn-primary" title="Configurar Filtro" onclick="abrir_filtro()" id="btn-configurar-pesquisa" disabled><i class="fa fa-filter"></i></button>
                      <button type="button" class="btn btn-primary" title="Atualizar" onclick="pesquisar_configuracoes_agenda()" id="btn-atualizar-pesquisa"><i class="fa fa-refresh"></i></button>
                      <button type="button" class="btn btn-primary" title="Nova Configuração" onclick="novo_cadastro('user_<?php echo $user->getCodigo();?>')" id="btn-novo-cadastro"><i class="fa fa-file-o"></i></button>
                  </div>
              </div>

              <div class="box-body" id="box-tabela">
                <p>Lista de registros resultantes da pesquisa</p>
<!--                
                <table id='tb-configuracoes' class='table table-bordered table-hover'>
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

              <div class="box-body form-horizontal" id="box-formulario">
                  
                <div class="col-md-10">
                    <div class="form-group" style="margin: 2px;">
                        <label for="cd_agenda" class="col-sm-2 control-label padding-label">Código</label>
                        <div class="col-sm-2 padding-field">
                            <input type="text" class="form-control proximo_campo" id="cd_agenda" maxlength="5" placeholder="00" readonly>
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <label for="nm_agenda" class="col-sm-2 control-label padding-label">Nome</label>
                        <div class="col-sm-10 padding-field">
                            <input type="text" class="form-control proximo_campo" id="nm_agenda" maxlength="50" placeholder="Nome da agenda">
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <label for="ds_observacoes" class="col-sm-2 control-label padding-label">Observação</label>
                        <div class="col-sm-10 padding-field">
                            <textarea class="form-control" rows="5" id="ds_observacoes" placeholder="Observações gerais..." maxlength="250" style="width: 100%;"></textarea>
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <input type="hidden" id="cd_especialidade" value="0">
                        <input type="hidden" id="cd_profissional"  value="0">
                        <label for="dt_inicial" class="col-sm-2 control-label padding-label">D.Incial</label>
                        <div class="col-sm-3 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask id="dt_inicial">
                            </div>
                        </div>
                        <label for="dt_final" class="col-sm-1 control-label padding-label">D.Final</label>
                        <div class="col-sm-3 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask id="dt_final">
                            </div>
                        </div>
                        <label for="hr_divisao_agenda" class="col-sm-1 control-label padding-label">Tempo</label>
                        <div class="col-sm-2 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o" title="Tempo de cada atendimento"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_divisao_agenda" title="Tempo de cada atendimento">
                            </div>
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <div class="col-sm-2 padding-field"></div>
                        <div class="col-sm-8 padding-field">
                            <div class="checkbox icheck">
                              <label>
                                  <input class="proximo_campo" type="checkbox" id="sn_ativo" value="1"> Cadastro ativo
                              </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin: 2px;">
                    </div>
                    <div class="form-group" style="margin: 2px;">
                    </div>
                    
                    <div class="form-group" style="margin: 2px;">
                        <span class="col-sm-12 bg-blue-active text-uppercase text-center">Dias e horários de disponibilidade da agenda</span>
                        <p>Informe cuidadosamente os intervalos de horários para cada dia da semana em que a agenda estará aberta para atendimentos.</p>
                    </div>
                    
                    <div class="form-group" style="margin: 2px;">
                        <div class="col-sm-2 padding-field">
                            <div class="checkbox icheck">
                              <label>
                                  <input class="proximo_campo dia-semana" type="checkbox" id="sn_domingo" value="1"> DOM
                              </label>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_dom_ini_manha" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_dom_fim_manha" readonly>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_dom_ini_tarde" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_dom_fim_tarde" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin: 2px;">
                        <div class="col-sm-2 padding-field">
                            <div class="checkbox icheck">
                              <label>
                                  <input class="proximo_campo dia-semana" type="checkbox" id="sn_segunda" value="1"> SEG
                              </label>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_seg_ini_manha" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_seg_fim_manha" readonly>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_seg_ini_tarde" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_seg_fim_tarde" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin: 2px;">
                        <div class="col-sm-2 padding-field">
                            <div class="checkbox icheck">
                              <label>
                                  <input class="proximo_campo dia-semana" type="checkbox" id="sn_terca" value="1"> TER
                              </label>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_ter_ini_manha" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_ter_fim_manha" readonly>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_ter_ini_tarde" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_ter_fim_tarde" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin: 2px;">
                        <div class="col-sm-2 padding-field">
                            <div class="checkbox icheck">
                              <label>
                                  <input class="proximo_campo dia-semana" type="checkbox" id="sn_quarta" value="1"> QUA
                              </label>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_qua_ini_manha" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_qua_fim_manha" readonly>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_qua_ini_tarde" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_qua_fim_tarde" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin: 2px;">
                        <div class="col-sm-2 padding-field">
                            <div class="checkbox icheck">
                              <label>
                                  <input class="proximo_campo dia-semana" type="checkbox" id="sn_quinta" value="1"> QUI
                              </label>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_qui_ini_manha" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_qui_fim_manha" readonly>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_qui_ini_tarde" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_qui_fim_tarde" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin: 2px;">
                        <div class="col-sm-2 padding-field">
                            <div class="checkbox icheck">
                              <label>
                                  <input class="proximo_campo dia-semana" type="checkbox" id="sn_sexta" value="1"> SEX
                              </label>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_sex_ini_manha" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_sex_fim_manha" readonly>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_sex_ini_tarde" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_sex_fim_tarde" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin: 2px;">
                        <div class="col-sm-2 padding-field">
                            <div class="checkbox icheck">
                              <label>
                                  <input class="proximo_campo dia-semana" type="checkbox" id="sn_sabado" value="1"> SAB
                              </label>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_sab_ini_manha" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_sab_fim_manha" readonly>
                            </div>
                        </div>
                        <div class="col-sm-5 padding-field">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_sab_ini_tarde" readonly>
                                <div class="input-group-addon">
                                    <span>às</span>
                                </div>
                                <div class="input-group-addon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <input type="text" class="form-control proximo_campo dia-horario" data-inputmask="'alias': 'hh:mm'" data-mask id="hr_sab_fim_tarde" readonly>
                            </div>
                        </div>
                    </div>
                    
                </div>
<!--                  
                <div class="col-md-6">
                    
                </div>  
-->                  
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
//                            $('#qtde-registros-conf').val(data.filtro[qtde - 1].qt_registro);
//                            $('#qtde-registros-conf').select2();
//                        }
//                    });
//                } catch (er) {
//                }
                $('#qtde-registros-conf').val(<?php echo $qtde;?>);
                $('#qtde-registros-conf').select2();
                
                configurar_checked();
                configurar_tabela('#tb-configuracoes');
            });
            
            $('#box-filtro').hide();
            $('#box-pesquisa').show();
            $('#box-cadastro').hide();

            $('#cd_tipo_filtro').val("1");
            $(".select2").select2();      // Ativar o CSS nos "Select"
            $('[data-mask]').inputmask(); // Ativar as máscaras nas "Input"

            pesquisar_configuracoes_agenda();
            
            if (document.getElementById("box-filtro").style.display === 'block') { $('#btn-configurar-pesquisa').fadeOut() };

            function configurar_checked() {
                $('input').iCheck({
                    checkboxClass: 'icheckbox_square-blue',
                    radioClass   : 'iradio_square-blue',
                    increaseArea : '20%' // optional
                });
                
                $('.dia-semana').on('ifChecked', function(){
                    liberar_edicao_horario(this);
                });
            }
            
            function liberar_edicao_horario(e) {
                var str = e.id;
                var tag = str.substring(3, 6);
                if (typeof($('#hr_' + tag + '_ini_manha')) !== "undefined") $('#hr_' + tag + '_ini_manha').prop('readonly', $(e.id).is(":checked"));
                if (typeof($('#hr_' + tag + '_fim_manha')) !== "undefined") $('#hr_' + tag + '_fim_manha').prop('readonly', $(e.id).is(":checked"));
                if (typeof($('#hr_' + tag + '_ini_tarde')) !== "undefined") $('#hr_' + tag + '_ini_tarde').prop('readonly', $(e.id).is(":checked"));
                if (typeof($('#hr_' + tag + '_fim_tarde')) !== "undefined") $('#hr_' + tag + '_fim_tarde').prop('readonly', $(e.id).is(":checked"));
            }
                
            function editar_horario(tag, valor) {
                if (typeof($('#hr_' + tag + '_ini_manha')) !== "undefined") $('#hr_' + tag + '_ini_manha').prop('readonly', (valor === 0));
                if (typeof($('#hr_' + tag + '_fim_manha')) !== "undefined") $('#hr_' + tag + '_fim_manha').prop('readonly', (valor === 0));
                if (typeof($('#hr_' + tag + '_ini_tarde')) !== "undefined") $('#hr_' + tag + '_ini_tarde').prop('readonly', (valor === 0));
                if (typeof($('#hr_' + tag + '_fim_tarde')) !== "undefined") $('#hr_' + tag + '_fim_tarde').prop('readonly', (valor === 0));
            }
                
            function configurar_tabela(id) {
                if (typeof($(id)) !== "undefined") {
                    var qt_registros = parseInt('0' + $('#qtde-registros-conf').val());
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
                            null,                 // 1. Descrição
                            null,                 // 2. Especialidade
                            { "width": "10px" },  // 3. Status
                            { "width": "5px"  }   // 4. <Excluir>
                        ],
                        "columnDefs": [
                            {"orderable": false, "targets": 0}, // Código
                            {"orderable": false, "targets": 3}, // Status
                            {"orderable": false, "targets": 4}  // <Excluir>
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
                var linha = $('#id_linha').val();
                if ((linha !== '') && (typeof($(linha)) !== 'undefined')) {
                    $(linha).removeClass("text-bold");
                    $(linha).removeClass("bg-gray-light");
                    $(linha).removeClass("bg-gray");
                }
                var tr = $(handler).closest('tr');
                tr.addClass("text-bold");
                tr.addClass("bg-gray-light");
                $('#id_linha').val( '#' + $(tr).attr('id') );    
                var registro = id.replace("configuracao_", "");
                
                carregar_configuracao_agenda(registro, function(data){
                    $('#operacao').val("editar");
                    
                    $('#cd_agenda').val(zero_esquerda(data.registro[0].codigo, 2));
                    $('#nm_agenda').val(data.registro[0].nome);
                    $('#ds_observacoes').val(data.registro[0].observacoes);
//                    $('#cd_especialidade').val(data.registro[0].especialidade);
//                    $('#cd_profissional').val(data.registro[0].profissional);
                    $('#dt_inicial').val(data.registro[0].data_inicial);
                    $('#dt_final').val(data.registro[0].data_final);
                    $('#hr_divisao_agenda').val(data.registro[0].divisao_agenda);
                    $('#sn_domingo').prop('checked', (parseInt(data.registro[0].domingo) === 1)).iCheck('update');
                    $('#sn_segunda').prop('checked', (parseInt(data.registro[0].segunda) === 1)).iCheck('update');
                    $('#sn_terca').prop('checked',   (parseInt(data.registro[0].terca) === 1)).iCheck('update');
                    $('#sn_quarta').prop('checked',  (parseInt(data.registro[0].quarta) === 1)).iCheck('update');
                    $('#sn_quinta').prop('checked',  (parseInt(data.registro[0].quinta) === 1)).iCheck('update');
                    $('#sn_sexta').prop('checked',   (parseInt(data.registro[0].sexta) === 1)).iCheck('update');
                    $('#sn_sabado').prop('checked',  (parseInt(data.registro[0].sabado) === 1)).iCheck('update');
                    $('#sn_ativo').prop('checked',   (parseInt(data.registro[0].ativo) === 1)).iCheck('update');
                    // Horários de Domingo
                    editar_horario('dom', parseInt(data.registro[0].domingo));
                    $('#hr_dom_ini_manha').val(data.registro[0].dom_ini_manha);
                    $('#hr_dom_fim_manha').val(data.registro[0].dom_fim_manha);
                    $('#hr_dom_ini_tarde').val(data.registro[0].dom_ini_tarde);
                    $('#hr_dom_fim_tarde').val(data.registro[0].dom_fim_tarde);
                    // Horários de Segunda
                    editar_horario('seg', parseInt(data.registro[0].segunda));
                    $('#hr_seg_ini_manha').val(data.registro[0].seg_ini_manha);
                    $('#hr_seg_fim_manha').val(data.registro[0].seg_fim_manha);
                    $('#hr_seg_ini_tarde').val(data.registro[0].seg_ini_tarde);
                    $('#hr_seg_fim_tarde').val(data.registro[0].seg_fim_tarde);
                    // Horários de Terça
                    editar_horario('ter', parseInt(data.registro[0].terca));
                    $('#hr_ter_ini_manha').val(data.registro[0].ter_ini_manha);
                    $('#hr_ter_fim_manha').val(data.registro[0].ter_fim_manha);
                    $('#hr_ter_ini_tarde').val(data.registro[0].ter_ini_tarde);
                    $('#hr_ter_fim_tarde').val(data.registro[0].ter_fim_tarde);
                    // Horários de Quarta
                    editar_horario('qua', parseInt(data.registro[0].quarta));
                    $('#hr_qua_ini_manha').val(data.registro[0].qua_ini_manha);
                    $('#hr_qua_fim_manha').val(data.registro[0].qua_fim_manha);
                    $('#hr_qua_ini_tarde').val(data.registro[0].qua_ini_tarde);
                    $('#hr_qua_fim_tarde').val(data.registro[0].qua_fim_tarde);
                    // Horários de Quinta
                    editar_horario('qui', parseInt(data.registro[0].quinta));
                    $('#hr_qui_ini_manha').val(data.registro[0].qui_ini_manha);
                    $('#hr_qui_fim_manha').val(data.registro[0].qui_fim_manha);
                    $('#hr_qui_ini_tarde').val(data.registro[0].qui_ini_tarde);
                    $('#hr_qui_fim_tarde').val(data.registro[0].qui_fim_tarde);
                    // Horários de Sexta
                    editar_horario('sex', parseInt(data.registro[0].sexta));
                    $('#hr_sex_ini_manha').val(data.registro[0].sex_ini_manha);
                    $('#hr_sex_fim_manha').val(data.registro[0].sex_fim_manha);
                    $('#hr_sex_ini_tarde').val(data.registro[0].sex_ini_tarde);
                    $('#hr_sex_fim_tarde').val(data.registro[0].sex_fim_tarde);
                    // Horários de Sábado
                    editar_horario('sab', parseInt(data.registro[0].sabado));
                    $('#hr_sab_ini_manha').val(data.registro[0].sab_ini_manha);
                    $('#hr_sab_fim_manha').val(data.registro[0].sab_fim_manha);
                    $('#hr_sab_ini_tarde').val(data.registro[0].sab_ini_tarde);
                    $('#hr_sab_fim_tarde').val(data.registro[0].sab_fim_tarde);
                    
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
                    
                    $('#cd_agenda').val("");
                    $('#nm_agenda').val("");
                    $('#ds_observacoes').val("");
                    $('#cd_especialidade').val("0");
                    $('#cd_profissional').val("0");
                    $('#dt_inicial').val("");
                    $('#dt_final').val("");
                    $('#hr_divisao_agenda').val("");
                    $('.dia-semana').prop('checked', false).iCheck('update');
                    $('.dia-horario').prop('readonly', true);
                    $('.dia-horario').val("");
                    $('#sn_ativo').prop('checked', true).iCheck('update');
                    
                    $('#box-filtro').hide();
                    $('#box-pesquisa').hide();
                    $('#box-cadastro').show();

                    $('#box-cadastro').fadeIn(); 
                });
            }
                
            function fechar_filtro(pesquisar) {
                $('#btn-configurar-pesquisa').fadeIn();
                //$('#box-filtro').fadeOut(); <-- Muito lento para o contexto
                $('#box-filtro').hide();
                if (pesquisar === true) pesquisar_configuracoes_agenda();
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
                var registro  = $('#cd_agenda').val();
                var requedido = "";

                if ($('#nm_agenda').val() === "")  requedido += "<li>Nome da agenda</li>";
                if (($('#dt_inicial').val() === "") || !validar_data($('#dt_inicial').val())) requedido += "<li>Data Incial da vigência</li>";
                if (($('#dt_final').val() === "")   || !validar_data($('#dt_final').val())) requedido += "<li>Data Final da vigência</li>";
                if (($('#hr_divisao_agenda').val() === "") || ($('#hr_divisao_agenda').val() === "00:00")) requedido += "<li>Tempo de cada atendimento</li>";
                if (!$('.dia-semana').is(':checked')) requedido += "<li>Dia(s) da semana disponível para agenda</li>";

                if ($('#sn_domingo').is(':checked')) {
                    var str = "";
                    if (validar_hora($('#hr_dom_ini_manha').val())) str += "a";
                    if (validar_hora($('#hr_dom_fim_manha').val())) str += "a";
                    if (validar_hora($('#hr_dom_ini_tarde').val())) str += "b";
                    if (validar_hora($('#hr_dom_fim_tarde').val())) str += "b";
                    if (!((str === 'aa') || (str === 'bb') || (str === 'aabb'))) requedido += "<li>Horário disponível no Domingo</li>";
                }
                    
                if ($('#sn_segunda').is(':checked')) {
                    var str = "";
                    if (validar_hora($('#hr_seg_ini_manha').val())) str += "a";
                    if (validar_hora($('#hr_seg_fim_manha').val())) str += "a";
                    if (validar_hora($('#hr_seg_ini_tarde').val())) str += "b";
                    if (validar_hora($('#hr_seg_fim_tarde').val())) str += "b";
                    if (!((str === 'aa') || (str === 'bb') || (str === 'aabb'))) requedido += "<li>Horário disponível na Segunda</li>";
                }
                    
                if ($('#sn_terca').is(':checked')) {
                    var str = "";
                    if (validar_hora($('#hr_ter_ini_manha').val())) str += "a";
                    if (validar_hora($('#hr_ter_fim_manha').val())) str += "a";
                    if (validar_hora($('#hr_ter_ini_tarde').val())) str += "b";
                    if (validar_hora($('#hr_ter_fim_tarde').val())) str += "b";
                    if (!((str === 'aa') || (str === 'bb') || (str === 'aabb'))) requedido += "<li>Horário disponível na Terça</li>";
                }
                    
                if ($('#sn_quarta').is(':checked')) {
                    var str = "";
                    if (validar_hora($('#hr_qua_ini_manha').val())) str += "a";
                    if (validar_hora($('#hr_qua_fim_manha').val())) str += "a";
                    if (validar_hora($('#hr_qua_ini_tarde').val())) str += "b";
                    if (validar_hora($('#hr_qua_fim_tarde').val())) str += "b";
                    if (!((str === 'aa') || (str === 'bb') || (str === 'aabb'))) requedido += "<li>Horário disponível na Quarta</li>";
                }
                    
                if ($('#sn_quinta').is(':checked')) {
                    var str = "";
                    if (validar_hora($('#hr_qui_ini_manha').val())) str += "a";
                    if (validar_hora($('#hr_qui_fim_manha').val())) str += "a";
                    if (validar_hora($('#hr_qui_ini_tarde').val())) str += "b";
                    if (validar_hora($('#hr_qui_fim_tarde').val())) str += "b";
                    if (!((str === 'aa') || (str === 'bb') || (str === 'aabb'))) requedido += "<li>Horário disponível na Quinta</li>";
                }
                    
                if ($('#sn_sexta').is(':checked')) {
                    var str = "";
                    if (validar_hora($('#hr_sex_ini_manha').val())) str += "a";
                    if (validar_hora($('#hr_sex_fim_manha').val())) str += "a";
                    if (validar_hora($('#hr_sex_ini_tarde').val())) str += "b";
                    if (validar_hora($('#hr_sex_fim_tarde').val())) str += "b";
                    if (!((str === 'aa') || (str === 'bb') || (str === 'aabb'))) requedido += "<li>Horário disponível na Sexta</li>";
                }
                    
                if ($('#sn_sabado').is(':checked')) {
                    var str = "";
                    if (validar_hora($('#hr_sab_ini_manha').val())) str += "a";
                    if (validar_hora($('#hr_sab_fim_manha').val())) str += "a";
                    if (validar_hora($('#hr_sab_ini_tarde').val())) str += "b";
                    if (validar_hora($('#hr_sab_fim_tarde').val())) str += "b";
                    if (!((str === 'aa') || (str === 'bb') || (str === 'aabb'))) requedido += "<li>Horário disponível no Sábado</li>";
                }
                    
                if (requedido !== "") {
                    show_campos_requeridos("Alerta", "Configuração da Agenda", requedido);
                } else {    
                    salvar_registro_configuracao_agenda(registro, function(){
                        var linha = "#tr-linha_" + parseFloat($('#cd_agenda').val());
                        $('#id_linha').val(linha);

                        // Atualizar status do registro na tabela
                        var referencia = parseInt("0" + $('#cd_agenda').val());
                        set_status_fa('#status_configuracao_' + referencia, $('#sn_ativo').is(":checked"));
                        
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
            
            function excluir_registro(id, elemento) {
                var usuario = "user_<?php echo $user->getCodigo();?>";
                var rotina  = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso  = "0100";
                get_allow_user(usuario, rotina, acesso, function(){
                    var registro  = id.replace("excluir_configuracao_", "");
                    var tr_table  = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                    var colunas   = tr_table.getElementsByTagName('td');
                    var descricao = colunas[1].firstChild.nodeValue;
                    
                    excluir_configuracao_agenda(registro, descricao, function(){
                        RemoveTableRow(elemento);
                    });
                });
            }    
        </script>
  </body>
</html>
