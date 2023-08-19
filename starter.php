<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<?php
    // Ativar Certificado SSL em "localhost"
    // https://www.vivodecodigo.com.br/internet/como-ativar-o-https-ssl-no-servidor-wampserver-3-2-0/
    
    // PDO SQL Server:
    // https://docs.microsoft.com/pt-br/sql/connect/php/loading-the-php-sql-driver?view=sql-server-2017

    // exec sp_SpaceUsed -> Descobrir o espaço utilizado do banco

    ini_set('default_charset', 'UTF-8');
    ini_set('display_errors', true);
    error_reporting(E_ALL);
    date_default_timezone_set('America/Belem');
    
    require './dist/php/constantes.php';
    require './dist/php/sessao.php';
    require './dist/dao/conexao.php';
    require './dist/dao/autenticador.php';
    require './dist/php/usuario.php';
    
    session_start();
    $user = new Usuario();
    if ( isset($_SESSION['user']) ) {
        $user = unserialize($_SESSION['user']);
    } else {
        header('location: ./index.php');
        exit;
    }
    
    $ano = date("Y");
    $tkn = $user->getToken(); // sha1(date("d/m/Y") . $user->getCodigo() . $_SERVER["REMOTE_ADDR"]);
    $cok = sha1($user->getCodigo());

    $tam_base = "Usando 0.0 MB de 10 GB";
    
    $pdo = Conexao::getConnection();

    // Carregar informações de uso do espaço em disco do ServerDB
    $qry = $pdo->query("exec sp_SpaceUsed");
    if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
        $uso = str_replace(".00", "", $obj->database_size);
        $tam_base = "Usando {$uso} de 10 GB";
    }
    $qry->closeCursor();

    // Carregar dados da empresa
    $qry     = $pdo->query("Select * from dbo.sys_empresa e inner join dbo.sys_usuario_empresa u on (u.id_empresa = e.id_empresa and u.id_usuario = '" . $user->getCodigo() . "')");
    $dados   = $qry->fetchAll(PDO::FETCH_ASSOC);
    $empresa = null;
    foreach($dados as $item) {
        $empresa = $item;
    }
    $qry->closeCursor();
    
    $qry = $pdo->query("Select * from dbo.tbl_profissional where sn_ativo = 1");
    $profissionais = $qry->fetchAll(PDO::FETCH_ASSOC);
    
    // Fechar conexão PDO
    $qry = null;
    $pdo = null;
    
    // Forçar a renovação dos arquivos no cache do navegador
    $versao = "v=". time();
?>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo Constante::SystemGCM;?> | Starter</title>
  <link rel="shortcut icon" href="icon.ico" >
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.6 -->
  <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="plugins/datatables/dataTables.bootstrap.css">
  <!-- Select2 -->
  <link rel="stylesheet" href="plugins/select2/select2.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
  <!-- AdminLTE Skins. We have chosen the skin-blue for this starter
        page. However, you can choose any other skin. Make sure you
        apply the skin class to the body tag so the changes take effect.
  -->
  <link rel="stylesheet" href="dist/css/skins/skin-blue.min.css">
  <!-- Pace style -->
  <link rel="stylesheet" href="plugins/pace/pace.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="plugins/iCheck/square/blue.css">
  <!-- SystemGCM -->
  <link rel="stylesheet" href="dist/css/SystemGCM.css">
  <!-- AutoComplete -->
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  
  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<!--
BODY TAG OPTIONS:
=================
Apply one or more of the following classes to get the
desired effect
|---------------------------------------------------------|
| SKINS         | skin-blue                               |
|               | skin-black                              |
|               | skin-purple                             |
|               | skin-yellow                             |
|               | skin-red                                |
|               | skin-green                              |
|---------------------------------------------------------|
|LAYOUT OPTIONS | fixed                                   |
|               | layout-boxed                            |
|               | layout-top-nav                          |
|               | sidebar-collapse                        |
|               | sidebar-mini                            |
|---------------------------------------------------------|
-->
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <!-- Main Header -->
  <header class="main-header" id="page-header">

    <!-- Logo -->
    <a href="starter.php" class="logo">
      <!-- mini logo for sidebar mini 50x50 pixels -->
      <span class="logo-mini"><?php echo Constante::bGCM;?></span>
      <!-- logo for regular state and mobile devices -->
      <span class="logo-lg"><b><?php echo Constante::System;?></b><?php echo Constante::GCM;?></span>
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
        <span class="sr-only">Toggle navigation</span>
      </a>
      
      <!-- Navbar Right Menu -->
      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
          <!-- Messages: style can be found in dropdown.less-->
          <li class="dropdown messages-menu">
            <?php
//            <!-- Menu toggle button -->
//            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
//              <i class="fa fa-envelope-o"></i>
//              <span class="label label-success">4</span>
//            </a>
//            <ul class="dropdown-menu">
//              <li class="header">You have 4 messages</li>
//              <li>
//                <!-- inner menu: contains the messages -->
//                <ul class="menu">
//                  <li><!-- start message -->
//                    <a href="#">
//                      <div class="pull-left">
//                        <!-- User Image -->
//                        <img src="dist/img/user2-160x160.jpg" class="img-circle" alt="User Image">
//                      </div>
//                      <!-- Message title and timestamp -->
//                      <h4>
//                        Support Team
//                        <small><i class="fa fa-clock-o"></i> 5 mins</small>
//                      </h4>
//                      <!-- The message -->
//                      <p>Why not buy a new awesome theme?</p>
//                    </a>
//                  </li>
//                  <!-- end message -->
//                </ul>
//                <!-- /.menu -->
//              </li>
//              <li class="footer"><a href="#">See All Messages</a></li>
//            </ul>
//          </li>
//          <!-- /.messages-menu -->
//          
//          <!-- Notifications Menu -->
//          <li class="dropdown notifications-menu">
//            <!-- Menu toggle button -->
//            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
//              <i class="fa fa-bell-o"></i>
//              <span class="label label-warning">10</span>
//            </a>
//            <ul class="dropdown-menu">
//              <li class="header">You have 10 notifications</li>
//              <li>
//                <!-- Inner Menu: contains the notifications -->
//                <ul class="menu">
//                  <li><!-- start notification -->
//                    <a href="#">
//                      <i class="fa fa-users text-aqua"></i> 5 new members joined today
//                    </a>
//                  </li>
//                  <!-- end notification -->
//                </ul>
//              </li>
//              <li class="footer"><a href="#">View all</a></li>
//            </ul>
//          </li>
//          
//          <!-- Tasks Menu -->
//          <li class="dropdown tasks-menu">
//            <!-- Menu Toggle Button -->
//            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
//              <i class="fa fa-flag-o"></i>
//              <span class="label label-danger">9</span>
//            </a>
//            <ul class="dropdown-menu">
//              <li class="header">You have 9 tasks</li>
//              <li>
//                <!-- Inner menu: contains the tasks -->
//                <ul class="menu">
//                  <li><!-- Task item -->
//                    <a href="#">
//                      <!-- Task title and progress text -->
//                      <h3>
//                        Design some buttons
//                        <small class="pull-right">20%</small>
//                      </h3>
//                      <!-- The progress bar -->
//                      <div class="progress xs">
//                        <!-- Change the css width attribute to simulate progress -->
//                        <div class="progress-bar progress-bar-aqua" style="width: 20%" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
//                          <span class="sr-only">20% Complete</span>
//                        </div>
//                      </div>
//                    </a>
//                  </li>
//                  <!-- end task item -->
//                </ul>
//              </li>
//              <li class="footer">
//                <a href="#">View all tasks</a>
//              </li>
//            </ul>
//          </li>
          ?>
          <!-- User Account Menu -->
          <li class="dropdown user user-menu">
            <!-- Menu Toggle Button -->
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <!-- The user image in the navbar-->
                <input type="hidden" id="tokenID"   value="<?php echo $tkn;?>"/>
                <input type="hidden" id="cookieID"  value="<?php echo $cok;?>"/>
                <input type="hidden" id="userID"    value="<?php echo $user->getCodigo();?>"/>
                <input type="hidden" id="empresaID" value="<?php echo $empresa['id_empresa'];?>"/>
                <img src="dist/img/user-160x160.png" class="user-image" alt="Foto do usuário">
                <!-- hidden-xs hides the username on small devices so only the image appears. -->
                <span class="hidden-xs"><?php echo $user->getNome();?></span>
            </a>
            <ul class="dropdown-menu">
              <!-- The user image in the menu -->
              <li class="user-header">
                <img src="dist/img/user-160x160.png" class="img-circle" alt="Foto do usuário">
                <p>
                    <?php echo $user->getNome();?> | <?php echo $user->getPerfil()->getDescricao();?>
                    <small>Usuário ativo desde <?php echo $user->getData_ativacao();?></small>
                </p>
              </li>
              
              <!-- Menu Footer-->
              <li class="user-footer">
                <div class="pull-left">
                  <a href="#" class="btn btn-default btn-flat">Configurações</a>
                </div>
                  
                <!-- Botões importantes escondidos -->
                <button type="button" class="btn btn-default btn-lrg ajax" title="Ajax Request" id="btn_ajax_start"><i class="fa fa-spin fa-refresh"></i></button>
                
                <div class="pull-right">
                    <a href="dist/php/controller.php?acao=close" class="btn btn-default btn-flat">Sair</a>
                </div>
              </li>
            </ul>
          </li>
          
          <?php
//          <!-- Controle de configuração Sidebar Toggle Button -->
//          <li>
//            <a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>
//          </li>
          ?>
        </ul>
      </div>
    </nav>
  </header>
  
  <!-- Left side column. contains the logo and sidebar -->
  <aside class="main-sidebar" id="page-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

      <!-- Sidebar user panel (optional) -->
      <div class="user-panel">
        <div class="pull-left image">
            <img src="dist/img/user-160x160.png" class="img-circle" alt="Foto do usuário">
        </div>
        <div class="pull-left info">
            <p><?php echo $user->getNome();?></p>
            <a href="#"><i class="fa fa-circle text-info"></i> <?php echo $user->getPerfil()->getDescricao();?></a>
        </div>
      </div>
      
      <?php
//      <!-- search form (Optional) -->
//      <form action="#" method="get" class="sidebar-form">
//        <div class="input-group">
//          <input type="text" name="q" class="form-control" placeholder="Search...">
//              <span class="input-group-btn">
//                <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
//                </button>
//              </span>
//        </div>
//      </form>
//      <!-- /.search form -->
      ?>
      
    <!-- Sidebar Menu -->
    <ul class="sidebar-menu">
        <li class="header">RECEPÇÃO</li>
        <!-- Optionally, you can add icons to the links ( class="active" ) -->
        <li id="page-home"><a href="#" onclick="page_home()"><i class="fa fa-home"></i> <span>Home</span></a></li>
        <li id="page-agendamento"><a href="#" onclick="page_agendamentos('user_<?php echo $user->getCodigo();?>')"><i class="fa fa-calendar"></i> <span>Agendamentos</span></a></li>
        <li class="treeview">
            <a href="#"><i class="fa fa-gears"></i> <span>Configurações</span>
                <span class="pull-right-container">
                    <i class="fa fa-angle-left pull-right"></i>
                </span>
            </a>
            <ul class="treeview-menu">
                <li><a href="#">Dados do Consultório</a></li>
                <li id="page-dados_profissionais"><a href="#" onclick="page_dados_profissionais('user_<?php echo $user->getCodigo();?>')">Dados do(s) Médico(s)</a></li>
                <li id="page-configurar_agenda"><a href="#" onclick="page_configurar_agendas('user_<?php echo $user->getCodigo();?>')">Agenda Médica</a></li>
            </ul>
        </li>
        
        <li class="header">CENTRAL DE CADASTRO</li>
        <li class="treeview">
            <a href="#"><i class="fa fa-table"></i> <span>Base de Endereços</span>
                <span class="pull-right-container">
                    <i class="fa fa-angle-left pull-right"></i>
                </span>
            </a>
            <ul class="treeview-menu">
                <li id="page-cidade"><a href="#" onclick="page_cidades('user_<?php echo $user->getCodigo();?>')">Cidades</a></li>
<!--
                <li><a href="#">Bairros</a></li>
                <li><a href="#">Logradouros</a></li>
-->                
                <li id="page-cep"><a href="#" onclick="page_ceps('user_<?php echo $user->getCodigo();?>')">Ceps</a></li>
            </ul>
        </li>
        <li class="treeview">
            <a href="#"><i class="fa fa-table"></i> <span>Tabelas Auxiliares</span>
                <span class="pull-right-container">
                    <i class="fa fa-angle-left pull-right"></i>
                </span>
            </a>
            <ul class="treeview-menu">
                <!--<li><a href="#">Tipos de Atendimento</a></li>-->
                <!--<li id="page-convenio"><a href="#" onclick="page_convenios('user_<?php // echo $user->getCodigo();?>')">Convênios</a></li>-->
                <!--<li id="page-profissao"><a href="#" onclick="page_profissoes('user_<?php // echo $user->getCodigo();?>')">Profissões</a></li>-->
                <?php if ($user->getPerfil()->getCodigo() === Constante::PerfilAdministradorSistema):?>
                <li id="page-grupoarquivo"><a href="#" onclick="page_grupoarquivos('user_<?php echo $user->getCodigo();?>')">Grupos de Arquivos</a></li>
                <li id="page-especialidade"><a href="#" onclick="page_especialidades('user_<?php echo $user->getCodigo();?>')">Especialidades</a></li>
                <li><a href="#"><hr style="margin: 2px; border: 1px solid #8aa4af;"></a></li>
                <?php endif;?>
                <!--<li><a href="#">Procedimentos</a></li>-->
                <li id="page-tabela_preco"><a href="#" onclick="page_tabela_precos('user_<?php echo $user->getCodigo();?>')">Tabela de Preços</a></li>
                <li id="page-tabela_exame"><a href="#" onclick="page_tabela_exames('user_<?php echo $user->getCodigo();?>')">Tabela de Exames</a></li>
                <li id="page-tabela_evolucao"><a href="#" onclick="page_tabela_evolucoes('user_<?php echo $user->getCodigo();?>')">Tabela de Evoluções</a></li>
            </ul>
        </li>
        <li id="page-paciente"><a href="#" onclick="page_pacientes('user_<?php echo $user->getCodigo();?>')"><i class="fa fa-th"></i> <span>Pacientes</span></a></li>
        <li class="header">ATENDIMENTO</li>
        <!--<li><a href="#"><i class="fa fa-user-md"></i> <span>Consultório</span></a></li>-->
        <!--<li><a href="#"><i class="fa fa-heartbeat"></i> <span>Exames</span></a></li>-->
        <?php
            foreach($profissionais as $item) {
                $usuario = "'user_{$user->getCodigo()}'"; 
                $profiss = "'profissional_{$item['cd_profissional']}'"; 
                $onclick = "onclick=page_medical(" . $usuario . "," . $profiss . ")";
                if ( $user->getMedico() === false  ) {
                    echo "<li><a href='medical.php?id={$item['cd_profissional']}' {$onclick}><i class='fa fa-user-md'></i> <span>{$item['nm_apresentacao']}</span></a></li>";
                } else {
                    echo "<li><a href='medical.php' {$onclick}><i class='fa fa-user-md'></i> <span>{$item['nm_apresentacao']}</span></a></li>";
                }
            }
            
            if (count($profissionais) === 0) {
                echo "<li><a href='medical.php'><i class='fa fa-user-md'></i> <span>Consultório</span></a></li>";
            }
        ?>
        <li class="header">CONTROLE DE ACESSO</li>
        <li id="page-constrole_usuario"><a href="#" onclick="page_controle_usuarios('user_<?php echo $user->getCodigo();?>')"><i class="fa fa-users"></i> <span>Usuários</span></a></li>
        <li><a href="#"><i class="fa fa-unlock"></i> <span>Permissões de Acesso</span></a></li>
    </ul>
    <!-- /.sidebar-menu -->
      
    </section>
    <!-- /.sidebar -->
  </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper" id="content-wrapper">
        <?php
            include './views/_home.php';
        ?>
    </div>
  <!-- /.content-wrapper -->

  <!-- Main Footer -->
  <footer class="main-footer">
    <!-- To the right -->
    <div class="pull-right hidden-xs">
        <label class="label-danger btn-xs">&nbsp; <?php echo $tam_base;?> &nbsp;</label>
    </div>
    <!-- Default to the left -->
    <strong>Copyright &copy; <?php echo $ano;?> <a href="#"><?php echo $empresa['nm_fantasia'];?></a>.</strong> Todos os direitos reservados.
  </footer>

  <!-- Control Sidebar -->
  <!--
  <aside class="control-sidebar control-sidebar-dark">
    <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
      <li class="active"><a href="#control-sidebar-home-tab" data-toggle="tab"><i class="fa fa-home"></i></a></li>
      <li><a href="#control-sidebar-settings-tab" data-toggle="tab"><i class="fa fa-gears"></i></a></li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane active" id="control-sidebar-home-tab">
        <h3 class="control-sidebar-heading">Recent Activity</h3>
        <ul class="control-sidebar-menu">
          <li>
            <a href="javascript::;">
              <i class="menu-icon fa fa-birthday-cake bg-red"></i>

              <div class="menu-info">
                <h4 class="control-sidebar-subheading">Langdon's Birthday</h4>

                <p>Will be 23 on April 24th</p>
              </div>
            </a>
          </li>
        </ul>

        <h3 class="control-sidebar-heading">Tasks Progress</h3>
        <ul class="control-sidebar-menu">
          <li>
            <a href="javascript::;">
              <h4 class="control-sidebar-subheading">
                Custom Template Design
                <span class="pull-right-container">
                  <span class="label label-danger pull-right">70%</span>
                </span>
              </h4>

              <div class="progress progress-xxs">
                <div class="progress-bar progress-bar-danger" style="width: 70%"></div>
              </div>
            </a>
          </li>
        </ul>

      </div>
        
      <div class="tab-pane" id="control-sidebar-stats-tab">Stats Tab Content</div>
      <div class="tab-pane" id="control-sidebar-settings-tab">
        <form method="post">
          <h3 class="control-sidebar-heading">General Settings</h3>

          <div class="form-group">
            <label class="control-sidebar-subheading">
              Report panel usage
              <input type="checkbox" class="pull-right" checked>
            </label>

            <p>
              Some information about this general settings option
            </p>
          </div>
        </form>
      </div>
    </div>
  </aside>
  -->
  
  <!-- Add the sidebar's background. This div must be placed
       immediately after the control sidebar -->
  <!--<div class="control-sidebar-bg"></div>-->
</div>
<!-- ./wrapper -->

<!-- REQUIRED JS SCRIPTS -->

<!-- jQuery 2.2.3 -->
<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
<!-- Bootstrap 3.3.6 -->
<script src="bootstrap/js/bootstrap.min.js"></script>
<!-- iCheck -->
<script src="plugins/iCheck/icheck.min.js"></script>
<!-- DataTables -->
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables/dataTables.bootstrap.min.js"></script>
<!-- PACE -->
<script src="plugins/pace/pace.min.js"></script>
<!-- SlimScroll -->
<script src="plugins/slimScroll/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="plugins/fastclick/fastclick.js"></script>
<!-- Select2 -->
<script src="plugins/select2/select2.full.min.js"></script>
<!-- InputMask -->
<script src="plugins/input-mask/jquery.inputmask.js"></script>
<script src="plugins/input-mask/jquery.inputmask.date.extensions.js"></script>
<script src="plugins/input-mask/jquery.inputmask.extensions.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/app.min.js"></script>
<!-- ChartJS -->
<script src="plugins/chartjs/Chart.js"></script>

<!-- Starter GCM -->
<script src="dist/js/starter.js?<?= $versao ?>"></script>
<script src="dist/js/medical.js?<?= $versao ?>"></script>
<script src="dist/js/pages/modal.js?<?= $versao ?>"></script>
<script src="dist/js/pages/controle_acesso.js?<?= $versao ?>"></script>
<script src="dist/js/pages/agendamento.js?<?= $versao ?>"></script>
<script src="dist/js/pages/profissional.js?<?= $versao ?>"></script>
<script src="dist/js/pages/configurar_agenda.js?<?= $versao ?>"></script>
<script src="dist/js/pages/cidade.js?<?= $versao ?>"></script>
<script src="dist/js/pages/cep.js?<?= $versao ?>"></script>
<script src="dist/js/pages/convenio.js?<?= $versao ?>"></script>
<script src="dist/js/pages/profissao.js?<?= $versao ?>"></script>
<script src="dist/js/pages/grupo_arquivo.js?<?= $versao ?>"></script>
<script src="dist/js/pages/especialidade.js?<?= $versao ?>"></script>
<script src="dist/js/pages/tabela_preco.js?<?= $versao ?>"></script>
<script src="dist/js/pages/tabela_exame.js?<?= $versao ?>"></script>
<script src="dist/js/pages/tabela_evolucao.js?<?= $versao ?>"></script>
<script src="dist/js/pages/paciente.js?<?= $versao ?>"></script>
<script src="dist/js/pages/arquivo_paciente.js?<?= $versao ?>"></script>
<script src="dist/js/pages/atendimento.js?<?= $versao ?>"></script>
<script src="dist/js/pages/usuario.js?<?= $versao ?>"></script>
<!-- Autocomplete -->
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<!-- FormatNumber -->
<script src="dist/js/NumberFormat.js"></script>
<!-- Necessários à ordenação do campo Data/Hora nos DataTables -->
<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.4/moment.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/plug-ins/1.10.10/sorting/datetime-moment.js"></script>

<!-- Optionally, you can add Slimscroll and FastClick plugins.
     Both of these plugins are recommended to enhance the
     user experience. Slimscroll is required when using the
     fixed layout. -->
    <script type="text/javascript">
	$(document).ajaxStart(function() { Pace.restart(); });
        $('.ajax').click(function(){
            $.ajax({url: '#', success: function(result){
                $('.ajax-content').html('<hr>Ajax Request Completed !');
            }});
        });

        $("#btn_ajax_start").fadeOut(1);
        $('#page-home').addClass("active");
        
        function ajaxStart() {
            $("#btn_ajax_start").trigger("click");
        }
        
        getCharts();
    </script>
</body>
</html>
