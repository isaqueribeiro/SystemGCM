<!DOCTYPE html>
<html>
  <body class="hold-transition skin-blue sidebar-mini">
    
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h1>
            Cidades
            <small>Relação de cidades disponíveis</small>
          </h1>
          <ol class="breadcrumb">
              <li><a href="#"><i class="fa fa-home"></i> Home</a></li>
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
    <!--
              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Ocultar"><i class="fa fa-minus"></i></button>
                <button type="button" class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remover"><i class="fa fa-times"></i></button>
              </div>
    -->
            </div>

            <div class="box-body">
                
            </div>

              <div class="box-footer">
                  <button class='btn btn-primary' id='btn_sumit_pesquisa' name='btn_sumit_pesquisa' onclick='fechar_filtro()' title="Executar pesquisa"><i class='fa fa-search'></i></button>
                  <!--<button class='btn btn-primary' id='btn_sumit_pesquisa' name='btn_novo_formulario' onclick='' title="Novo Registro"><i class='fa fa-file-o'></i></button>-->
                  <!--<button class='btn btn-primary' id='btn_reset_limpar'   name='btn_reset_limpar'    onclick='' title="Preparar nova pesquisa"><i class='fa  fa-eraser' ></i></button>-->
              </div>
          </div>

          <!-- Painel de Pesquisa -->
          <div class="box box-primary" id="box-pesquisa">
              <div class="box-header with-border">
                  <h3 class="box-title">Registros</h3>
                  <div class="box-tools pull-right">
                      <button type="button" class="btn btn-primary" title="Configurar Pesquisa" onclick="abrir_filtro()"><i class="fa fa-search"></i></button>
                      <button type="button" class="btn btn-primary" title="Atualizar"><i class="fa fa-refresh"></i></button>
                  </div>
              </div>

              <div class="box-body" id="box-tabela">
                  <p>Lista de registros resultantes da pesquisa</p>
              </div>
          </div>

          <!-- Painel de Cadastro -->
          <div class="box box-primary" id="box-cadastro">
              <div class="box-header with-border">
                  <h3 class="box-title">Cadastros</h3>
                  <div class="box-tools pull-right">
                      <button type="button" class="btn btn-primary" title="Fechar" onclick="fechar_cadastro()"><i class="fa fa-close"></i></button>
                  </div>
              </div>

              <div class="box-body" id="box-formulario">
                  <p>Lista de registros resultantes da pesquisa</p>
              </div>
          </div>

        </section>
        <!-- /.content -->
    
        <script type="text/javascript">
            document.getElementById("box-filtro").style.display = 'none';
            document.getElementById("box-pesquisa").style.display = 'block';
            document.getElementById("box-cadastro").style.display = 'none';
            
            function abrir_filtro() { $('#box-filtro').fadeIn(); } 
            function abrir_pesquisa() { $('#box-pesquisa').fadeIn(); } 
            function abrir_cadastro() { $('#box-cadastro').fadeOut(); } 
            function fechar_filtro() { $('#box-filtro').fadeOut(); } 
            function fechar_pesquisa() { $('#box-pesquisa').fadeOut(); } 
            function fechar_cadastro() { $('#box-cadastro').fadeOut(); } 
        </script>
  </body>
</html>
