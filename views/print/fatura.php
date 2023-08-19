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
    
    $id_agenda  = $_REQUEST['ag'];
    $id_empresa = $_REQUEST['ep'];
    
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
    
    $qry   = $pdo->query("exec dbo.getAgendaPaciente N'{$id_agenda}', N'{$id_empresa}'");
    $dados = $qry->fetchAll(PDO::FETCH_ASSOC);
    $agendamento = null;
    foreach($dados as $item) {
        $agendamento = $item;
    }
    
    // Fechar conexão PDO
    unset($qry);
    unset($pdo);
?>
<html>
  <head>
    <meta charset="UTF-8">
    <title><?php echo Constante::SystemGCM;?> | Fatura</title>
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
          </div><!-- /.col -->
        </div>
        
        <!-- info row -->
        <div class="row invoice-info">
          <div class="col-sm-4 invoice-col">
            Agendamento para
            <address>
                <strong><?php echo $agendamento['data_agenda'] . " às " . substr(str_replace(":", "h", $agendamento['hora_agenda']), 0, 5) ;?></strong><br>
              <?php echo $agendamento['tipo'];?><br>
              <?php echo $agendamento['especialidade'];?><br>
              <?php echo $agendamento['profissional'];?><br>
            </address>
          </div><!-- /.col -->
          
          <div class="col-sm-4 invoice-col">
            Paciente 
            <address>
              <strong><?php echo $agendamento['nm_paciente'];?></strong><br>
              <?php if (trim($agendamento['end_logradouro']) !== "") echo $agendamento['end_logradouro'] . ", " . $agendamento['end_bairro'] . "<br>";?>
              <?php echo $agendamento['end_cidade']. "/" . $agendamento['end_estado'] . " - Cep " . formatarTexto('##.###-###', str_pad($agendamento['nr_cep'], 8, "00000000", STR_PAD_LEFT));?><br>
              Celular: <?php echo $agendamento['celular'];?><br/>
              Email: <?php echo $agendamento['email'];?>
            </address>
          </div><!-- /.col -->
          
          <div class="col-sm-4 invoice-col">
            <b>Controle #<?php echo str_pad($agendamento['cd_agenda'], 7, "0", STR_PAD_LEFT);?></b><br>
            <br/>
            <b>Atendente :</b> <?php echo $agendamento['atendente'];?><br>
            <b>Data :</b> <?php echo $agendamento['data_atendimento'];?><br>
            <b>Hora :</b> <?php echo $agendamento['hora_atendimento'];?><br>
            <b>Situação :</b> <?php echo $agendamento['situacao'];?>
          </div><!-- /.col -->
        </div><!-- /.row -->

        <!-- Table row -->
        <div class="row">
          <div class="col-xs-12 table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Serviço</th>
                  <th style="text-align: right;">Valor (R$)&nbsp;</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>1</td>
                  <td><?php echo $agendamento['servico'];?></td>
                  <td align='right'><?php echo number_format($agendamento['vl_servico'], 2, ",", ".");?>&nbsp;</td>
                </tr>
              </tbody>
            </table>
          </div><!-- /.col -->
        </div><!-- /.row -->

        <div class="row">
          <div class="col-xs-6">
            <p class="lead">Alergias / Outras observações:</p>
            <!--<p class="text-muted well well-sm no-shadow" style="margin-top: 10px;">-->
              <?php 
                if (isset($agendamento['ds_alergias'])) {
                    echo "<p class='text-muted well well-sm no-shadow' style='margin-top: 10px;'>";
                    echo str_replace(chr(10), "<br>", $agendamento['ds_alergias']);
                    echo "</p>";
                }
              ?>
            <!--</p>-->
            <!--<p class="text-muted well well-sm no-shadow" style="margin-top: 10px;">-->
              <?php 
                if (isset($agendamento['ds_observacoes'])) {
                    echo "<p class='text-muted well well-sm no-shadow' style='margin-top: 10px;'>";
                    echo str_replace(chr(10), "<br>", $agendamento['ds_observacoes']);
                    echo "</p>";
                }
              ?>
            <!--</p>-->
          </div>
          
          <div class="col-xs-6">
            <p class="lead">Observações registradas no agendamento:</p>
            <!--<p class="text-muted well well-sm no-shadow" style="margin-top: 10px;">-->
              <?php 
                if (isset($agendamento['ds_observacao'])) {
                    echo "<p class='text-muted well well-sm no-shadow' style='margin-top: 10px;'>";
                    echo str_replace(chr(10), "<br>", $agendamento['ds_observacao']);
                    echo "</p>";
                }
              ?>
            <!--</p>-->
          </div>
        </div>
        
      </section><!-- /.content -->
    </div><!-- ./wrapper -->

    <!-- AdminLTE App -->
    <script src="../../dist/js/app.min.js" type="text/javascript"></script>
  </body>
</html>
