<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<?php
    // PDO SQL Server:
    // https://docs.microsoft.com/pt-br/sql/connect/php/loading-the-php-sql-driver?view=sql-server-2017

    // exec sp_SpaceUsed -> Descobrir o espaço utilizado do banco

    ini_set('default_charset', 'UTF-8');
    ini_set('display_errors', true);
    error_reporting(E_ALL);
    date_default_timezone_set('America/Belem');
    
    require '../../dist/php/constantes.php';
    require '../../dist/php/sessao.php';
    require '../../dist/dao/conexao.php';
    require '../../dist/dao/autenticador.php';
    require '../../dist/php/usuario.php';
    require '../../dist/php/funcoes.php';
    
    session_start();
    $user = new Usuario();
    if ( isset($_SESSION['user']) ) {
        $user = unserialize($_SESSION['user']);
    } else {
        header('location: ./index.php');
        exit;
    }
    
    $id_atendimento = $_REQUEST['at'];
    $id_empresa     = $_REQUEST['ep'];
    
    $ano = date("Y");
    $tkn = $user->getToken(); // sha1(date("d/m/Y") . $user->getCodigo() . $_SERVER["REMOTE_ADDR"]);
    $cok = sha1($user->getCodigo());

    $pdo = Conexao::getConnection();

    // Carregar dados da empresa
    $qry     = $pdo->query("Select * from dbo.sys_empresa");
    $dados   = $qry->fetchAll(PDO::FETCH_ASSOC);
    $empresa = null;
    foreach($dados as $item) {
        $empresa = $item;
    }
    $qry->closeCursor();
    
    $qry   = $pdo->query("exec dbo.getAtendimentoPaciente N'{$id_atendimento}', N'{$id_empresa}'");
    $dados = $qry->fetchAll(PDO::FETCH_ASSOC);
    $atendimento = null;
    foreach($dados as $item) {
        $atendimento = $item;
    }
    
    // Fechar conexão PDO
    unset($qry);
    unset($pdo);
?>
<html>
  <head>
    <meta charset="UTF-8">
    <title><?php echo Constante::SystemGCM;?> | Prescrição</title>
    <link rel="shortcut icon" href="../../print.ico" >
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.4 -->
    <link href="../../bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Font Awesome Icons -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <!-- Ionicons -->
    <link href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" rel="stylesheet" type="text/css" />
    <!-- Theme style -->
    <link href="../../dist/css/AdminLTE.min.css" rel="stylesheet" type="text/css" />

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body onload="window.print();">
    <div class="wrapper">
      <!-- Main content -->
      <section class="invoice">
        <!-- title row -->
        <div class="row">
          <div class="col-xs-12">
            <h2 class="page-header">
              <?php echo $empresa['nm_fantasia'];?>
              <small><?php echo $empresa['ds_endereco'];?></small>  
              <?php
                if (isset($empresa['ds_contatos']) && trim($empresa['ds_contatos']) !== '') {
                    echo "<small>{$empresa['ds_contatos']}</small>";
                }
                if (isset($empresa['ds_email']) && trim($empresa['ds_email']) !== '') {
                    echo "<small>Email: {$empresa['ds_email']}</small>";
                }
              ?>
              <small class="pull-right">Data : <?php echo date('d/m/Y');?></small>
              <small>&nbsp;</small>  
            </h2>
              <h4 class="text-uppercase text-center">Prescrição / Receituário</h4>  
          </div><!-- /.col -->
        </div>
        
        <!-- info row -->
        <div class="row invoice-info">
          <div class="col-xs-8">
            Paciente
            <address>
              <strong><?php echo $atendimento['nm_paciente'];?></strong><br>
              <?php if (trim($atendimento['end_logradouro']) !== "") echo $atendimento['end_logradouro'] . ", " . $atendimento['end_bairro'] . "<br>";?>
              <?php echo $atendimento['end_cidade']. "/" . $atendimento['end_estado'] . " - Cep " . formatarTexto('##.###-###', str_pad($atendimento['nr_cep'], 8, "00000000", STR_PAD_LEFT));?><br>
              Celular: <?php echo $atendimento['celular'];?><br/>
              Email: <?php echo $atendimento['email'];?>
            </address>
          </div><!-- /.col -->
          
          <div class="col-xs-4">
            <b>Controle #<?php echo str_pad($atendimento['cd_atendimento'], 7, "0", STR_PAD_LEFT);?></b><br>
            <b>Data :</b> <?php echo $atendimento['data_atendimento'];?><br>
            <b>Hora :</b> <?php echo $atendimento['hora_atendimento'];?><br>
          </div><!-- /.col -->
        </div><!-- /.row -->

        <div class="row">
          <div class="col-xs-12">
              <font face='courier new'>
                <?php 
                  if (isset($atendimento['ds_prescricao'])) {
                    if (trim($atendimento['ds_prescricao']) !== "") {
                        echo "<p class='text-uppercase text-muted well well-sm no-shadow' style='margin-top: 10px;'>";
                        echo str_replace(chr(32), "&nbsp;", str_replace(chr(10), "<br>", $atendimento['ds_prescricao']));
                        echo "</p>";
                    } else {
                        echo "<p class='text-uppercase text-muted well well-sm no-shadow' style='margin-top: 10px;'>";
                        echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>";
                        echo "</p>";
                    }
                  } else {
                      echo "<p class='text-uppercase text-muted well well-sm no-shadow' style='margin-top: 10px;'>";
                      echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>";
                      echo "</p>";
                  }
                ?>
              </font>
          </div>
        </div>
        
        <div class="row invoice-info">
            <div class="col-md-4">&nbsp;</div>
            <div class="col-md-4 text-center">
                <br>
                <br>
                --------------------------------------------------------------------<br>
                <strong><?php echo $atendimento['profissional'];?></strong><br>
                <?php echo $atendimento['ds_conselho'];?>
            </div>
            <div class="col-md-4">&nbsp;</div>
        </div>
      </section><!-- /.content -->
    </div><!-- ./wrapper -->

    <!-- AdminLTE App -->
    <script src="../../dist/js/app.min.js" type="text/javascript"></script>
  </body>
</html>
