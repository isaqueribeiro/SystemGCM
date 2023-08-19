<!DOCTYPE html>
<?php
    ini_set('default_charset', 'UTF-8');
    ini_set('display_errors', true);
    error_reporting(E_ALL);
    date_default_timezone_set('America/Belem');
    
    require '../dist/php/constantes.php';
    require '../dist/dao/conexao.php';
    require '../dist/php/usuario.php';
    
    $cd_estado  = "15"; // PARÁ
    $id_estacao = md5($_SERVER["REMOTE_ADDR"]);
    
    $pdo = Conexao::getConnection();
    $qry = $pdo->query("Select * from dbo.sys_estado");
    $estados = $qry->fetchAll(PDO::FETCH_ASSOC);
    
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
    $file = "../logs/cookies/cidade_" . sha1($user->getCodigo()) . ".json";
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
            Cidades
            <small>Relação de cidades disponíveis</small>
            <input type="hidden" id="estacaoID" value="<?php echo $id_estacao;?>">
          </h1>
          <ol class="breadcrumb">
              <li><a href="#"><i class="fa fa-home"></i> Home</a></li>
              <li><a href="#">Central de Cadastros</a></li>
              <li><a href="#">Base de Endereços</a></li>
              <li class="active" id="page-click" onclick="preventDefault()">Cidades</li>
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
                        <label for="cd_estado_filtro" class="col-sm-4 control-label padding-label">Estado</label>
                        <div class="col-sm-8 padding-field">
                            <select class="form-control select2"  id="cd_estado_filtro" style="width: 100%;">
                                <option value='0'>Selecione o estado</option>
                                <?php
                                    foreach($estados as $item) {
                                        echo "<option value='{$item['cd_estado']}'>{$item['nm_estado']}</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="ds_filtro" class="col-sm-4 control-label padding-label">Descrição</label>
                        <div class="col-sm-8 padding-field">
                            <input type="text" class="form-control" id="ds_filtro" placeholder="Informe um dado para filtro">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <button class='btn btn-primary' id='btn_sumit_pesquisa' name='btn_sumit_pesquisa' onclick='fechar_filtro(true)' title="Executar pesquisa"><i class='fa fa-search'></i></button>
                        <select class="form-control select2"  id="qtde-registros-cidade" style="width: 70px;">
                            <option value='5'>5</option>
                            <option value='10' selected>10</option>
                            <option value='11'>11</option>
                            <option value='12'>12</option>
                            <option value='13'>13</option>
                            <option value='14'>14</option>
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
                    <select class="form-control select2"  id="qtde-registros-cidade" style="width: 70px;">
                        <option value='5'>5</option>
                        <option value='10' selected>10</option>
                        <option value='11'>11</option>
                        <option value='12'>12</option>
                        <option value='13'>13</option>
                        <option value='14'>14</option>
                        <option value='15'>15</option>
                        <option value='20'>20</option>
                        <option value='25'>25</option>
                        <option value='30'>30</option>
                    </select>
                    <span>&nbsp; Quantidade de registros por paginação</span>
            </div>
-->              
          </div>

          <!-- Painel de Pesquisa -->
          <div class="box box-info" id="box-pesquisa">
              <div class="box-header with-border">
                  <h3 class="box-title">Registros</h3>
                  <div class="box-tools pull-right">
                      <button type="button" class="btn btn-primary" title="Configurar Filtro" onclick="abrir_filtro()" id="btn-configurar-pesquisa"><i class="fa fa-filter"></i></button>
                      <button type="button" class="btn btn-primary" title="Atualizar" onclick="pesquisar_cidades()" id="btn-atualizar-pesquisa"><i class="fa fa-refresh"></i></button>
                      <button type="button" class="btn btn-primary" title="Novo Cadastro" onclick="novo_cadastro()" id="btn-novo-cadastro" disabled><i class="fa fa-file-o"></i></button>
                  </div>
              </div>

              <div class="box-body" id="box-tabela">
                <p>Lista de registros resultantes da pesquisa</p>
<!--                
                <table id='tb-cidades' class='table table-bordered table-hover'>
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
                        <label for="cd_cidade" class="col-sm-2 control-label padding-label">Código IBGE</label>
                        <div class="col-sm-2 padding-field">
                            <input type="text" class="form-control" id="cd_cidade" placeholder="0000000" readonly>
                        </div>
                        <label for="cd_estado" class="col-sm-1 control-label padding-label">Estado</label>
                        <div class="col-sm-5 padding-field">
                            <select class="form-control select2"  id="cd_estado" style="width: 100%;">
                                <option value='0'>Selecione o estado</option>
                                <?php
                                    foreach($estados as $item) {
                                        echo "<option value='{$item['cd_estado']}'>{$item['nm_estado']}</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <label for="nm_cidade" class="col-sm-2 control-label padding-label">Nome</label>
                        <div class="col-sm-8 padding-field">
                            <input type="text" class="form-control" id="nm_cidade" maxlength="150" placeholder="Nome da cidade">
                        </div>
                    </div>
                    <div class="form-group" style="margin: 2px;">
                        <label for="nr_cep_inicial" class="col-sm-2 control-label padding-label">CEP Inicial</label>
                        <div class="col-sm-2 padding-field">
                            <input type="text" class="form-control" data-inputmask='"mask": "99999-999"' data-mask id="nr_cep_inicial">
                        </div>
                        <label for="nr_cep_final" class="col-sm-1 control-label padding-label">CEP Final</label>
                        <div class="col-sm-2 padding-field">
                            <input type="text" class="form-control" data-inputmask='"mask": "99999-999"' data-mask id="nr_cep_final">
                        </div>
                        <label for="nr_ddd" class="col-sm-1 control-label padding-label">DDD</label>
                        <div class="col-sm-2 padding-field">
                            <input type="text" class="form-control" data-inputmask='"mask": "99"' data-mask id="nr_ddd">
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
//                    var file_cookie = "logs/cookies/cidade_" + $('#cookieID').val() + ".json";
//                    $.getJSON(file_cookie, function(data){
//                        $('#qtde-registros-cidade').val(data.filtro[0].qt_registro);
//                        $('#qtde-registros-cidade').select2();
//                    });
//                } catch (er) {
//                }
                $('#qtde-registros-cidade').val(<?php echo $qtde;?>);
                $('#qtde-registros-cidade').select2();
                
                configurar_tabela('#tb-cidades');
            });
            
            document.getElementById("box-filtro").style.display   = 'none';
            document.getElementById("box-pesquisa").style.display = 'block';
            document.getElementById("box-cadastro").style.display = 'none';

            $('#cd_estado_filtro').val(<?php echo $cd_estado;?>);
            $(".select2").select2();      // Ativar o CSS nos "Select"
            $('[data-mask]').inputmask(); // Ativar as máscaras nas "Input"
            
            pesquisar_cidades();

            if (document.getElementById("box-filtro").style.display === 'block') { $('#btn-configurar-pesquisa').fadeOut() };
            
            function configurar_tabela(id) {
                if (typeof($(id)) !== "undefined") {
                    var qt_registros = parseInt('0' + $('#qtde-registros-cidade').val());
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
                            { "width": "10px" },  // #
                            null,                 // Nome
                            { "width": "10px" },  // UF
                            { "width": "100px" }, // CEP Inicial
                            { "width": "100px" }, // CEP Final
                            { "width": "5px" }    // DDD
                        ],
                        "columnDefs": [
                            {"orderable": false, "targets": 0}, // #
                            {"orderable": false, "targets": 3}, // CEP Inicial
                            {"orderable": false, "targets": 4}, // CEP Final
                            {"orderable": false, "targets": 5}  // DDD
                        ],
                        "order": [[2, 'asc'], [1, 'asc']], // "order": [] <-- Ordenação indefinida (UF, Nome)
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
                var registro = id.replace("cidade_", "");
                
                carregar_registro_cidade(registro, function(){
                    $('#operacao').val("editar");
                    
                    document.getElementById("box-filtro").style.display   = 'none';
                    document.getElementById("box-pesquisa").style.display = 'none';
                    document.getElementById("box-cadastro").style.display = 'block';
                    
                    $('#box-cadastro').fadeIn(); 
                    $('#cd_estado').select2();
                });
            } 
            
            function fechar_filtro(pesquisar) {
                $('#btn-configurar-pesquisa').fadeIn();
                //$('#box-filtro').fadeOut(); <-- Muito lento para o contexto
                document.getElementById("box-filtro").style.display = 'none';
                if (pesquisar === true) pesquisar_cidades();
            } 
            
            function fechar_pesquisa() { 
                $('#box-pesquisa').fadeOut(); 
            } 
            
            function fechar_cadastro() {
                $('#box-cadastro').fadeOut(); 
            } 
            
            function voltar_pesquisa() {
                document.getElementById("box-pesquisa").style.display = 'block';
                document.getElementById("box-cadastro").style.display = 'none';
            }
                
            function salvar_cadastro() {
                var registro = $('#cd_cidade').val();
                salvar_registro_cidade(registro, function(){
                    var linha = $('#id_linha').val();
                    
                    if ((linha !== '') && (typeof($(linha)) !== 'undefined')) {
                        $(linha).removeClass("bg-gray-light");
                        $(linha).addClass("bg-gray");
                        
                        voltar_pesquisa(); 
                    }
                });
            }    
        </script>
  </body>
</html>
