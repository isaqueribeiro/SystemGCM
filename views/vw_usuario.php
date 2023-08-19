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
    
    $pdo = Conexao::getConnection();
    
    $qry = $pdo->query("Select * from dbo.sys_perfil p where (p.sn_ativo = 1)");
    $perfis = $qry->fetchAll(PDO::FETCH_ASSOC);
    
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
    $file = "../logs/cookies/controle_usuario_" . sha1($user->getCodigo()) . ".json";
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
            Usuários
            <small>Relação de usuários cadastros para acesso ao sistema</small>
            <input type="hidden" id="estacaoID" value="<?php echo $id_estacao;?>">
          </h1>
          <ol class="breadcrumb">
              <li><a href="#"><i class="fa fa-home"></i> Home</a></li>
              <li><a href="#">Controle de Acessos</a></li>
              <li class="active" id="page-click" onclick="preventDefault()">Usuários</li>
          </ol>
        </section>

        <!-- Main content -->
        <section class="content">

          <!-- Painel de Pesquisa -->
          <div class="box" id="box-filtro">
            <div class="box-header with-border">
              <h3 class="box-title">Filtro(s) da pesquisa</h3>
              <div class="box-tools pull-right">
                <button type="button" class="btn btn-primary" title="Fechar (Voltar à pesquisa)" onclick="fechar_filtro(false)"><i class="fa fa-close"></i></button>  
              </div>
            </div>
            
            <div class="box-body form-horizontal">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="cd_tipo_filtro" class="col-sm-2 control-label">Tipo</label>
                        <div class="col-sm-10">
                            <select class="form-control select2"  id="cd_tipo_filtro" style="width: 100%;">
                                <option value='0'>Todos</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="ds_filtro" class="col-sm-2 control-label">Nome/Email</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="ds_filtro" placeholder="Informe um dado para filtro">
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                    <button class='btn btn-primary' id='btn_sumit_pesquisa' name='btn_sumit_pesquisa' onclick='fechar_filtro(true)' title="Executar pesquisa"><i class='fa fa-search'></i></button>
                    <select class="form-control select2"  id="qtde-registros-usuario" style="width: 70px;">
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
                      <button type="button" class="btn btn-primary" title="Atualizar" onclick="pesquisar_usuarios()" id="btn-atualizar-pesquisa"><i class="fa fa-refresh"></i></button>
                      <button type="button" class="btn btn-primary" title="Novo Cadastro" onclick="novo_cadastro('user_<?php echo $user->getCodigo();?>')" id="btn-novo-cadastro"><i class="fa fa-file-o"></i></button>
                  </div>
              </div>

              <div class="box-body" id="box-tabela">
                <p>Lista de registros resultantes da pesquisa</p>
              </div>
          </div>

          <!-- Painel de Cadastro -->
          <div class="box box-primary" id="box-cadastro">
              <div class="box-header with-border">
                  <h3 class="box-title">Cadastro</h3>
                  <div class="box-tools pull-right">
                      <input type="hidden" id="id_linha">
                      <input type="hidden" id="operacao">
                      <input type="hidden" id="id_usuario">
                      <button type="button" class="btn btn-primary" title="Fechar (Voltar à pesquisa)" onclick="voltar_pesquisa()"><i class="fa fa-close"></i></button>
                  </div>
              </div>
              
              <div class="box-body form-horizontal" id="box-formulario">
                <div class="col-md-10">
                    <div class="form-group" style="margin: 2px;">
                        <label for="cd_usuario" class="col-sm-2 control-label padding-label">Código</label>
                        <div class="col-sm-1  padding-field">
                            <input type="text" class="form-control proximo_campo" id="cd_usuario" maxlength="10" placeholder="000" readonly>
                        </div>
                        <label for="tp_plataforma" class="col-sm-1 control-label padding-label">Plat.</label>
                        <div class="col-sm-2  padding-field">
                            <select class="form-control select2 no-padding"  id="tp_plataforma" style='width: 100%;' disabled>
                                <option value='0'>Indisponível</option>
                                <option value='1'>IOS</option>
                                <option value='2'>Android</option>
                                <option value='3'>Windows</option>
                            </select>
                        </div>
                        <label for="dh_ativacao" class="col-sm-3 control-label padding-label">Data/hora de ativação</label>
                        <div class="col-sm-3  padding-field">
                            <input type="text" class="form-control proximo_campo" id="dh_ativacao" readonly>
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <label for="nm_usuario" class="col-sm-2 control-label padding-label">Nome</label>
                        <div class="col-sm-10 padding-field">
                            <input type="text" class="form-control proximo_campo" id="nm_usuario" name="nm_usuario" maxlength="150" placeholder="Nome do usuário..." required>
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <label for="ds_email" class="col-sm-2 control-label padding-label">Email</label>
                        <div class="col-sm-4 padding-field">
                            <input type="email" class="form-control proximo_campo" id="ds_email" name="ds_email" maxlength="150" placeholder="Email para login de acesso..." required>
                        </div>
                        <label for="cd_perfil" class="col-sm-1 control-label padding-label">Perfil</label>
                        <div class="col-sm-5 padding-field">
                          <select class="form-control select2 proximo_campo" id="cd_perfil" style="width: 100%;">
                              <option value='0'>Selecione o perfil</option>
                              <?php
                                  foreach($perfis as $item) {
                                      echo "<option value='{$item['cd_perfil']}'>{$item['ds_perfil']}</option>";
                                  }
                              ?>
                          </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <label for="ds_senha" class="col-sm-2 control-label padding-label">Senha</label>
                        <div class="col-sm-4 padding-field">
                            <input type="password" class="form-control proximo_campo" id="ds_senha" name="ds_senha" maxlength="40" placeholder="Senha pessoal...">
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <label for="ds_confirma" class="col-sm-2 control-label padding-label">Confirmar</label>
                        <div class="col-sm-4 padding-field">
                            <input type="password" class="form-control proximo_campo" id="ds_confirma" name="ds_senha" maxlength="40" placeholder="Confirmar senha...">
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
            <?php
            include './../dist/js/pages/_modal.js';
            ?>
                
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

                $('#qtde-registros-usuario').val(<?php echo $qtde;?>);
                $('#qtde-registros-usuario').select2();
                
                configurar_checked();
                configurar_tabela('#tb-tabela_usuarios');
            });
            
            $('#box-filtro').hide();
            $('#box-pesquisa').show();
            $('#box-cadastro').hide();

            $('#cd_tipo_filtro').val("0");
            $(".select2").select2();      // Ativar o CSS nos "Select"
            $('[data-mask]').inputmask(); // Ativar as máscaras nas "Input"

            pesquisar_usuarios();
            
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
                    var qt_registros = parseInt('0' + $('#qtde-registros-usuario').val());
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
                            null,                 // 2. Login
                            null,                 // 3. Perfil
                            { "width": "10px" },  // 4. Status
                            { "width": "5px"  }   // 5. <Excluir>
                        ],
                        "columnDefs": [
                            {"orderable": false, "targets": 0}, // #
                            {"orderable": false, "targets": 4}, // Status
                            {"orderable": false, "targets": 5}  // <Excluir>
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
                var registro = id.replace("reg-usuario_", "");
                
                carregar_registro_usuario(registro, function(data){
                    $('#operacao').val("editar");
                    
                    $('#id_usuario').val(data.registro[0].id);
                    $('#cd_usuario').val(zero_esquerda(data.registro[0].codigo, 2));
                    $('#nm_usuario').val(data.registro[0].nome);
                    $('#cd_perfil').val(data.registro[0].perfil);
                    $('#ds_email').val(data.registro[0].email);
                    $('#ds_senha').val("");
                    $('#ds_confirma').val("");
                    $('#dh_ativacao').val(data.registro[0].ativacao);
                    $('#tp_plataforma').val(data.registro[0].plataforma);
                    $('#sn_ativo').prop('checked', (parseInt(data.registro[0].ativo) === 1)).iCheck('update');
                    
                    $('#ds_email').prop('readonly', true);
                    $('.select2').select2();
                    
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
                    
                    $('#id_usuario').val("");
                    $('#cd_usuario').val("");
                    $('#nm_usuario').val("");
                    $('#cd_perfil').val("0");
                    $('#ds_email').val("");
                    $('#ds_senha').val("");
                    $('#ds_confirma').val("");
                    $('#dh_ativacao').val("");
                    $('#tp_plataforma').val("0");
                    $('#sn_ativo').prop('checked', true).iCheck('update');
                    
                    $('#ds_email').prop('readonly', false);
                    $('.select2').select2();
                    
                    $('#box-filtro').hide();
                    $('#box-pesquisa').hide();
                    $('#box-cadastro').show();

                    $('#box-cadastro').fadeIn(); 
                });
            }
                
            function fechar_filtro(pesquisar) {
                $('#btn-configurar-pesquisa').fadeIn();
                $('#box-filtro').hide();
                if (pesquisar === true) pesquisar_usuarios();
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
                var registro  = $('#id_exame').val();
                var requedido = "";

                if ($('#nm_usuario').val() === "") requedido += "<li>Nome</li>";
                if ($('#ds_email').val()   === "") requedido += "<li>Login</li>";
                if ($('#cd_perfil').val() === "0") requedido += "<li>Perfil</li>";
                if ($('#operacao').val() === "inserir") {
                    if ($('#ds_senha').val() === "") requedido += "<li>Senha</li>";
                    if ($('#ds_senha').val() !== $('#ds_confirma').val()) requedido += "<li>Confirmar Senha</li>";
                }
                
                if (requedido !== "") {
                    show_campos_requeridos("Alerta", "Cadastro de Usuários", requedido);
                } else {    
                    salvar_registro_usuario(registro, function(data){
                        var linha = "#tr-linha_" + data.registro[0].referencia;
                        $('#id_linha').val(linha);

                        // Atualizar status do registro na tabela
                        var referencia = data.registro[0].referencia;
                        set_status_fa('#status_usuario_' + referencia, $('#sn_ativo').is(":checked"));
                        
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
                    var registro  = id.replace("excluir_usuario_", "");
                    var tr_table  = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                    var colunas   = tr_table.getElementsByTagName('td');
                    var descricao = colunas[1].firstChild.nodeValue;
                    
                    excluir_registro_usuario(registro, descricao, function(){
                        RemoveTableRow(elemento);
                    });
                });
            }    
        </script>
  </body>
</html>
