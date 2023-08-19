<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.

    O acesso do doutor é:

    Usuário : rubens@gcm.com.br
    Senha   : rubens

-->
<?php
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

    // Identificar profissional da agenda
    $cd_profissional = 0;
    if (isset($_GET["id"])) {
        $cd_profissional = (int)$_GET["id"];
    }
    
    // Carregar dados da empresa
//    $qry = $pdo->query(
//          "Select * "
//        . "from dbo.sys_empresa e "
//        . "  inner join dbo.sys_usuario_empresa u on (u.id_empresa = e.id_empresa and u.id_usuario = '" . $user->getCodigo() . "')"
//        . "  inner join dbo.tbl_profissional    p on (p.id_empresa = u.id_empresa and p.id_usuario = u.id_usuario)");
    $qry = $pdo->query(
          "Select "
        . "    ux.* "
        . "  , e.*  "
        . "  , u.id_empresa as empresaID   "
        . "  , u.dh_ativacao      "
        . "  , u.sn_ativo         "
        . "  , p.cd_profissional  "
        . "  , p.ds_conselho      "
        . "  , p.ft_assinatura    "
        . "  , p.nm_apresentacao  "
        . "  , p.nm_profissional  "
        . "  , coalesce(p.cd_profissional, 0) as profissionalID "
        . "  , coalesce(nullif(trim(p.nm_apresentacao), ''), p.nm_profissional, ux.nm_usuario) as apresentacao "
        . "from dbo.sys_usuario ux "
        . "  inner join dbo.sys_usuario_empresa u on (u.id_usuario = ux.id_usuario)"
        . "  inner join dbo.sys_empresa         e on (e.id_empresa = u.id_empresa)"
        //. "  left join dbo.tbl_profissional     p on (p.id_empresa = u.id_empresa and p.id_usuario = ux.id_usuario)"
        . ($cd_profissional == 0? 
            "  left join dbo.tbl_profissional     p on (p.id_empresa = u.id_empresa and p.id_usuario = ux.id_usuario)" : 
            "  left join dbo.tbl_profissional     p on (p.id_empresa = u.id_empresa and p.cd_profissional = {$cd_profissional})"
          )    
        . "where (ux.id_usuario = '{$user->getCodigo()}')"); 
    $dados     = $qry->fetchAll(PDO::FETCH_ASSOC);
    $empresa   = null;
    $is_medico = false;
    foreach($dados as $item) {
        $empresa    = $item;
        $is_medico  = isset($empresa['ds_conselho']) && ($cd_profissional === 0);
    }

    $_SESSION['profissional'] = $cd_profissional;
    $_SESSION['is_medico']    = $is_medico;
    
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
  <title><?php echo Constante::SystemGCM;?> | Consultório</title>
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
  <!-- bootstrap wysihtml5 - text editor -->
  <link rel="stylesheet" href="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
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
    <a href="medical.php" class="logo">
      <!-- mini logo for sidebar mini 50x50 pixels -->
      <span class="logo-mini"><b>C</b>SM</span>
      <!-- logo for regular state and mobile devices -->
      <span class="logo-lg"><b>Consultório </b>Tofolo</span>
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
          <!-- User Account Menu -->
          <li class="dropdown user user-menu">
            <!-- Menu Toggle Button -->
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <!-- The user image in the navbar-->
                <input type="hidden" id="tokenID"   value="<?php echo $tkn;?>"/>
                <input type="hidden" id="cookieID"  value="<?php echo $cok;?>"/>
                <input type="hidden" id="userID"    value="<?php echo $user->getCodigo();?>"/>
                <input type="hidden" id="empresaID" value="<?php echo $empresa['empresaID'];?>"/>
                <input type="hidden" id="profissionalID" value="<?php echo $empresa['profissionalID'];?>"/>
                <input type="hidden" id="profissionalMedico" value="<?php echo ($is_medico === true ? "S" : "N");?>"/>
                <img src="dist/img/user-160x160.png" class="user-image" alt="Foto do usuário">
                <!-- hidden-xs hides the username on small devices so only the image appears. -->
                <span class="hidden-xs"><?php echo $empresa['apresentacao'];?></span>
            </a>
            <ul class="dropdown-menu">
              <!-- The user image in the menu -->
              <li class="user-header">
                <img src="dist/img/user-160x160.png" class="img-circle" alt="Foto do usuário">
                <p>
                    <?php echo $empresa['apresentacao'];?> | <?php echo ($is_medico === true ? "Médico" : $user->getPerfil()->getDescricao());?>
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
            <p><?php echo $empresa['apresentacao'];?></p>
            <a href="#"><i class="fa fa-globe text-info"></i> <?php echo (isset($empresa['ds_conselho'])?$empresa['ds_conselho']:$user->getPerfil()->getDescricao());?></a>
        </div>
      </div>
      
    <!-- Sidebar Menu -->
    <ul class="sidebar-menu">
        <li class="header">AGENDA</li>
        <!-- Optionally, you can add icons to the links ( class="active" ) -->
        <li id="page-home"><a href="javascript:preventDefault();" onclick="page_home();remover_actives_medical();"><i class="fa fa-home"></i> <span>Home</span></a></li>
        <li id="page-home"><a href="starter.php"><i class="fa fa-arrow-circle-left"></i> <span>Recepção</span></a></li>
        <li class="header">ATENDIMENTOS</li>
        <li id="page-atendimento_hoje"><a href="javascript:preventDefault();" onclick="page_atendimentos_hoje('user_<?php echo $user->getCodigo();?>')"><i class="fa fa-calendar"></i> <span>Hoje</span></a></li>
        <li id="page-atendimento_hist"><a href="javascript:preventDefault();"><i class="fa fa-clock-o"></i> <span>Históricos</span></a></li>
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
<!-- Bootstrap WYSIHTML5 -->
<script src="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>

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
<script src="dist/js/pages/especialidade.js?<?= $versao ?>"></script>
<script src="dist/js/pages/tabela_preco.js?<?= $versao ?>"></script>
<script src="dist/js/pages/paciente.js?<?= $versao ?>"></script>
<script src="dist/js/pages/arquivo_paciente.js?<?= $versao ?>"></script>
<script src="dist/js/pages/atendimento.js?<?= $versao ?>"></script>

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
        
        sleep(1000);
    </script>
</body>
</html>
