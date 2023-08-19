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
    $file = "../logs/cookies/profissional_" . sha1($user->getCodigo()) . ".json";
    if (file_exists($file)) {
        $file_cookie = file_get_contents($file);
        $json = json_decode($file_cookie);
        if (isset($json->filtro[0])) {
            $qtde = (int)$json->filtro[0]->qt_registro;
        }
    }
    
    $pdo = Conexao::getConnection();
    // Carregar dados da empresa
    $qry = $pdo->query(
          "Select * "
        . "from dbo.sys_empresa e "
        . "inner join dbo.sys_usuario_empresa u on (u.id_empresa = e.id_empresa and u.id_usuario = '" . $user->getCodigo() . "')");
    $dados   = $qry->fetchAll(PDO::FETCH_ASSOC);
    $empresa = null;
    foreach($dados as $item) {
        $empresa = $item;
    }
    $qry->closeCursor();
    
    // Carregar usuarios médicos
    $sql = 
          "Select "
        . "    u.id_usuario "
        . "  , u.nm_usuario "
        . "from dbo.sys_usuario u "
        . "  inner join dbo.sys_usuario_empresa e on (e.id_empresa = '{$empresa['id_empresa']}' and e.id_usuario = u.id_usuario) "
        . "where (e.sn_ativo  = 1) "
        . "  and (u.cd_perfil = 5) " // Profissional Médico
        . "order by "
        . "    u.nm_usuario ";
    $qry = $pdo->query($sql);
    $usuarios = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    // Carregar especialidades ativas
    $sql = 
          "Select  "
        . "    row_number() over(order by ds_especialidade asc) as nr_linha "
        . "  , e.cd_especialidade "
        . "  , e.ds_especialidade "
        . "from dbo.tbl_especialidade e   "
        . "where (e.sn_ativo = 1) " 
        . "order by           "
        . "    e.ds_especialidade ";
    $qry = $pdo->query($sql);
    $especialidades = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    // Fechar conexão PDO
    unset($qry);
    unset($pdo);
?>
<html>
  <body class="hold-transition skin-blue sidebar-mini">
    
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h1>
            Dados do(s) Médico(s)
            <small>Painel de manutenção dos dados de profissionais médicos</small>
            <input type="hidden" id="estacaoID" value="<?php echo $id_estacao;?>">
          </h1>
          <ol class="breadcrumb">
              <li><a href="#"><i class="fa fa-home"></i> Home</a></li>
              <li><a href="#">Recepção</a></li>
              <li><a href="#">Configurações</a></li>
              <li class="active" id="page-click" onclick="preventDefault()">Dados do(s) Médico(s)</li>
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
                                <option value='1'>Apenas ativos</option>
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
                    <select class="form-control select2"  id="qtde-registros-prof" style="width: 70px;">
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
                      <button type="button" class="btn btn-primary" title="Atualizar" onclick="pesquisar_dados_profissionais()" id="btn-atualizar-pesquisa"><i class="fa fa-refresh"></i></button>
                      <button type="button" class="btn btn-primary" title="Nova Configuração" onclick="novo_cadastro('user_<?php echo $user->getCodigo();?>')" id="btn-novo-cadastro"><i class="fa fa-file-o"></i></button>
                  </div>
              </div>

              <div class="box-body" id="box-tabela">
                <p>Lista de registros resultantes da pesquisa</p>
<!--                
                <table id='tb-profissionais' class='table table-bordered table-hover'>
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
                        <label for="cd_profissional" class="col-sm-2 control-label padding-label">Código</label>
                        <div class="col-sm-2 padding-field">
                            <input type="text" class="form-control proximo_campo" id="cd_profissional" maxlength="5" placeholder="00" readonly>
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <label for="nm_profissional" class="col-sm-2 control-label padding-label">Nome</label>
                        <div class="col-sm-10 padding-field">
                            <input type="text" class="form-control proximo_campo" id="nm_profissional" maxlength="150" placeholder="Nome completo do profissional">
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <label for="nm_apresentacao" class="col-sm-2 control-label padding-label">Apresentação</label>
                        <div class="col-sm-10 padding-field">
                            <input type="text" class="form-control proximo_campo" id="nm_apresentacao" maxlength="150" placeholder="Nome de apresentação">
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <label for="ds_conselho" class="col-sm-2 control-label padding-label">Conselho</label>
                        <div class="col-sm-4 padding-field">
                            <input type="text" class="form-control proximo_campo" id="ds_conselho" maxlength="40" placeholder="Identificação no Conselho" onkeyup="javascript: this.value = texto_maiusculo(this);">
                        </div>
                        <label for="id_usuario" class="col-sm-1 control-label padding-label">Usuário</label>
                        <div class="col-sm-5 padding-field">
                            <select class="form-control select2 proximo_campo" id="id_usuario" style="width: 100%;">
                                <option value=''>Selecione o usuário do profissional</option>
                                <?php
                                    foreach($usuarios as $item) {
                                        echo "<option value='{$item['id_usuario']}'>{$item['nm_usuario']}</option>";
                                    }
                                ?>
                            </select>
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
                </div>
                  
                <div class="col-md-10">
                    <div class="form-group" style="margin: 2px;">
                        <label class="col-sm-2 control-label padding-label">Especialidades</label>
                        <div class="col-sm-10 padding-field">
                            <table class="table table-responsive table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 50px;">#</th>
                                        <th>Descrição</th>
                                        <th class="text-center" style="width: 50px;">Uso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($especialidades as $item):?>
                                    <tr>
                                        <td class="text-center"><?= $item['nr_linha'] ?></td>
                                        <td><?= $item['ds_especialidade'] ?></td>
                                        <td>
                                            <div class="checkbox icheck" style="padding: 0px; margin: 0px;">
                                              <label>
                                                  <input class="proximo_campo especialidade" type="checkbox" id="cd_especialidade-<?= $item['cd_especialidade'] ?>" value="1">
                                              </label>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table>
                            <?php
                                $qtde_especialidade  = count($especialidades);
                                $array_especialidade = "000";
                                foreach($especialidades as $item) {
                                    $array_especialidade .= "|" . $item['cd_especialidade'];
                                }
                                $array_especialidade = str_replace("000", "", str_replace("000|", "", $array_especialidade));
                            ?>
                            <input type="hidden" name="qtde_especialidade"  id="qtde_especialidade"  value="<?= $qtde_especialidade ?>"/>
                            <input type="hidden" name="array_especialidade" id="array_especialidade" value="<?= $array_especialidade ?>"/>
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
//                            $('#qtde-registros-prof').val(data.filtro[qtde - 1].qt_registro);
//                            $('#qtde-registros-prof').select2();
//                        }
//                    });
//                } catch (er) {
//                }
                $('#qtde-registros-prof').val(<?php echo $qtde;?>);
                $('#qtde-registros-prof').select2();
                
                configurar_checked();
                configurar_tabela('#tb-configuracoes');
            });
            
            $('#box-filtro').hide();
            $('#box-pesquisa').show();
            $('#box-cadastro').hide();

            $('#cd_tipo_filtro').val("0");
            $(".select2").select2();      // Ativar o CSS nos "Select"
            $('[data-mask]').inputmask(); // Ativar as máscaras nas "Input"

            pesquisar_dados_profissionais();
            
            if (document.getElementById("box-filtro").style.display === 'block') { $('#btn-configurar-pesquisa').fadeOut() };

            function configurar_checked() {
                $('input').iCheck({
                    checkboxClass: 'icheckbox_square-blue',
                    radioClass   : 'iradio_square-blue',
                    increaseArea : '20%' // optional
                });
                
                //$('.especialidade').on('ifChecked', function(){
                //    liberar_edicao_horario(this);
                //});
                //
                //$('.especialidade').on('ifUnchecked', function(){
                //    liberar_edicao_horario(this);
                //});
            }
            
            function configurar_tabela(id) {
                if (typeof($(id)) !== "undefined") {
                    var qt_registros = parseInt('0' + $('#qtde-registros-prof').val());
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
                            null,                 // 1. Nome completo
                            null,                 // 2. Apresentação
                            null,                 // 3. Conselho
                            { "width": "10px" },  // 4. Status
                            { "width": "5px"  }   // 5. <Excluir>
                        ],
                        "columnDefs": [
                            {"orderable": false, "targets": 0}, // Código
                            {"orderable": false, "targets": 4}, // Status
                            {"orderable": false, "targets": 5}  // <Excluir>
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
                
                var especialidades = $('#array_especialidade').val().split("|");
                for (var i = 0; i < especialidades.length; ++i) {
                    $('#cd_especialidade-' + especialidades[i]).prop('checked', false).iCheck('update');
                }    
                
                carregar_profissional(registro, function(data){
                    $('#operacao').val("editar");
                    var listagem = data.registro[0].especialidades.split(",");
                    for (var i = 0; i < listagem.length; ++i) {
                        $('#cd_especialidade-' + listagem[i]).prop('checked', true).iCheck('update');
                    }    
                    
                    $('#cd_profissional').val(zero_esquerda(data.registro[0].codigo, 2));
                    $('#nm_profissional').val(data.registro[0].nome);
                    $('#nm_apresentacao').val(data.registro[0].apresentacao);
                    $('#ds_conselho').val(data.registro[0].conselho);
                    $('#id_usuario').val(data.registro[0].usuario);
                    $('#sn_ativo').prop('checked', (parseInt(data.registro[0].ativo) === 1)).iCheck('update');

                    $(".select2").select2();
                    
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
                    
                    $('#cd_profissional').val("");
                    $('#nm_profissional').val("");
                    $('#nm_apresentacao').val("");
                    $('#ds_conselho').val("");
                    $('#id_usuario').val("");
                    $('#sn_ativo').prop('checked', true).iCheck('update');
                    
                    var especialidades = $('#array_especialidade').val().split("|");
                    for (var i = 0; i < especialidades.length; ++i) {
                        $('#cd_especialidade-' + especialidades[i]).prop('checked', false).iCheck('update');
                    }    

                    $(".select2").select2();
                    
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
                if (pesquisar === true) pesquisar_dados_profissionais();
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
                var registro  = $('#cd_profissional').val();
                var requedido = "";

                if ($('#nm_profissional').val() === "")  requedido += "<li>Nome do profissional</li>";
                if ($('#nm_apresentacao').val() === "")  requedido += "<li>Nome de apresentação</li>";
                if ($('#ds_conselho').val()     === "")  requedido += "<li>Identificação no conselho</li>";
                if ($('#id_usuario').val()      === "")  requedido += "<li>Usuário de acesso</li>";

                if (requedido !== "") {
                    show_campos_requeridos("Alerta", "Dados do Profissional", requedido);
                } else {    
                    salvar_registro_profissional(registro, function(){
                        var linha = "#tr-linha_" + parseFloat($('#cd_profissional').val());
                        $('#id_linha').val(linha);

                        // Atualizar status do registro na tabela
                        var referencia = parseInt("0" + $('#cd_profissional').val());
                        set_status_fa('#status_profissional_' + referencia, $('#sn_ativo').is(":checked"));
                        salvar_especialidades(referencia);
                        
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
            
            function salvar_especialidades(profissional) {
                var listagem       = $('#array_especialidade').val().split("|");
                var especialidades = "0";
                for (var i = 0; i < listagem.length; ++i) {
                    if ( $('#cd_especialidade-' + listagem[i]).is(":checked") ) {
                        especialidades = especialidades + ", " + listagem[i];
                    }
                }    
                salvar_especialidades_profissional(profissional, especialidades);
            }
            
            function excluir_registro(id, elemento) {
                var usuario = "user_<?php echo $user->getCodigo();?>";
                var rotina  = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
                var acesso  = "0100";
                get_allow_user(usuario, rotina, acesso, function(){
                    var registro  = id.replace("excluir_profissional_", "");
                    var tr_table  = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                    var colunas   = tr_table.getElementsByTagName('td');
                    var descricao = colunas[1].firstChild.nodeValue;
                    
                    excluir_profissional(registro, descricao, function(){
                        RemoveTableRow(elemento);
                    });
                });
            }    
        </script>
  </body>
</html>
