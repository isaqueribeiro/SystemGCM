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
    $file = "../logs/cookies/convenio_" . sha1($user->getCodigo()) . ".json";
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
            Convênios
            <small>Relação de convênios disponíveis</small>
            <input type="hidden" id="estacaoID" value="<?php echo $id_estacao;?>">
          </h1>
          <ol class="breadcrumb">
              <li><a href="#"><i class="fa fa-home"></i> Home</a></li>
              <li><a href="#">Central de Cadastros</a></li>
              <li><a href="#">Tabelas Auxiliares</a></li>
              <li class="active" id="page-click" onclick="preventDefault()">Convênios</li>
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
                                <option value='1'>Pessoa Física</option>
                                <option value='2'>Pessoa Jurídica</option>
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
                    <select class="form-control select2"  id="qtde-registros-conv" style="width: 70px;">
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
                      <button type="button" class="btn btn-primary" title="Configurar Filtro" onclick="abrir_filtro()" id="btn-configurar-pesquisa"><i class="fa fa-filter"></i></button>
                      <button type="button" class="btn btn-primary" title="Atualizar" onclick="pesquisar_convenios()" id="btn-atualizar-pesquisa"><i class="fa fa-refresh"></i></button>
                      <button type="button" class="btn btn-primary" title="Novo Cadastro" onclick="novo_cadastro('user_<?php echo $user->getCodigo();?>')" id="btn-novo-cadastro"><i class="fa fa-file-o"></i></button>
                  </div>
              </div>

              <div class="box-body" id="box-tabela">
                <p>Lista de registros resultantes da pesquisa</p>
<!--                
                <table id='tb-convenios' class='table table-bordered table-hover'>
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
                <div class="col-md-6">
                    <div class="form-group" style="margin: 2px;">
                        <label for="cd_convenio" class="col-sm-2 control-label">Código</label>
                        <div class="col-sm-2">
                            <input type="text" class="form-control" id="cd_convenio" maxlength="10" placeholder="000" readonly>
                        </div>
                        <label for="nr_cnpj_cpf" class="col-sm-2 control-label">CPF / CPNJ</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" id="nr_cnpj_cpf" maxlength="25" placeholder="Número CPF/CNPJ">
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <label for="nr_registro_ans" class="col-sm-2 control-label">Registro ANS</label>
                        <div class="col-sm-2">
                            <input type="text" class="form-control" id="nr_registro_ans" maxlength="6" placeholder="Registro ANS">
                        </div>
                        <label for="nm_resumido" class="col-sm-2 control-label">Nome resumido</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" id="nm_resumido" maxlength="50" placeholder="Nome resumido">
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <label for="nm_convenio" class="col-sm-2 control-label">Razão Social</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="nm_convenio" maxlength="200" placeholder="Razão Social (Nome completo)">
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <div class="col-sm-2"></div>
                        <div class="col-sm-8">
                            <div class="checkbox icheck">
                              <label>
                                  <input type="checkbox" id="sn_ativo" value="1"> Cadastro ativo
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
                $('#qtde-registros-conv').val(<?php echo $qtde;?>);
                $('#qtde-registros-conv').select2();
                
                configurar_checked();
                configurar_tabela('#tb-convenios');
            });
            
            $('#box-filtro').hide();
            $('#box-pesquisa').show();
            $('#box-cadastro').hide();

            $('#cd_tipo_filtro').val("0");
            $(".select2").select2();      // Ativar o CSS nos "Select"
            $('[data-mask]').inputmask(); // Ativar as máscaras nas "Input"

            pesquisar_convenios();
            
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
                    var qt_registros = parseInt('0' + $('#qtde-registros-conv').val());
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
                            null,                 // 2. Razão Social
                            null,                 // 3. CPF/CNPJ
                            null,                 // 4. Registros ANS
                            { "width": "10px" },  // 5. Status
                            { "width": "5px"  }   // 6. <Excluir>
                        ],
                        "columnDefs": [
                            {"orderable": false, "targets": 5}, // Status
                            {"orderable": false, "targets": 6}  // <Excluir>
                        ],
                        "order": [[1, 'asc']], // "order": [] <-- Ordenação indefinida (UF, Nome)
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
                var registro = id.replace("convenio_", "");
                
                carregar_registro_convenio(registro, function(){
                    $('#operacao').val("editar");
                    
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
                    
                    $('#cd_convenio').val("");
                    $('#nr_registro_ans').val("");
                    $('#nm_convenio').val("");
                    $('#nm_resumido').val("");
                    $('#nr_cnpj_cpf').val("");
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
                if (pesquisar === true) pesquisar_convenios();
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
                var registro  = $('#cd_convenio').val();
                var requedido = "";

                if ($('#nr_cnpj_cpf').val() === "")     requedido += "<li>CPF/CNPJ</li>";
                if ($('#nr_registro_ans').val() === "") requedido += "<li>Registro ANS</li>";
                if ($('#nm_convenio').val() === "")     requedido += "<li>Razão Social</li>";
                if ($('#nm_resumido').val() === "")     requedido += "<li>Nome resumido</li>";

                if (requedido !== "") {
                    show_campos_requeridos("Alerta", "Cadastro de Convênio", requedido);
                } else {    
                    salvar_registro_convenio(registro, function(){
                        var linha = "#tr-linha_" + parseFloat($('#cd_convenio').val());
                        $('#id_linha').val(linha);

                        // Atualizar status do registro na tabela
                        var referencia = parseInt("0" + $('#cd_convenio').val());
                        set_status_fa('#status_convenio_' + referencia, $('#sn_ativo').is(":checked"));
                        
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
                    var registro  = id.replace("excluir_convenio_", "");
                    var tr_table  = document.getElementById("tr-linha_" + registro); //$(data.registro[0].tr_table);
                    var colunas   = tr_table.getElementsByTagName('td');
                    var descricao = colunas[2].firstChild.nodeValue;
                    
                    excluir_convenio(registro, descricao, function(){
                        RemoveTableRow(elemento);
                    });
                });
            }    
        </script>
  </body>
</html>
