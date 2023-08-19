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
    
    $id_empresa = $_REQUEST['ep'];
    $nr_dia = str_pad(trim($_REQUEST['dia']), 2, "0", STR_PAD_LEFT);
    $nr_mes = str_pad(trim($_REQUEST['mes']), 2, "0", STR_PAD_LEFT);
    $nr_ano = str_pad(trim($_REQUEST['ano']), 4, "0", STR_PAD_LEFT);
    
    $ano = date("Y");
    $tkn = $user->getToken(); // sha1(date("d/m/Y") . $user->getCodigo() . $_SERVER["REMOTE_ADDR"]);
    $cok = sha1($user->getCodigo());

    $pdo = Conexao::getConnection();

    // Carregar dados da empresa
    $qry     = $pdo->query("Select * from dbo.sys_empresa e left join dbo.tbl_profissional p on (p.id_empresa = e.id_empresa) where e.id_empresa = '{$id_empresa}'");
    $dados   = $qry->fetchAll(PDO::FETCH_ASSOC);
    $empresa = null;
    foreach($dados as $item) {
        $empresa = $item;
    }
    $qry->closeCursor();
    
    $pdo->beginTransaction();
    $qry = $pdo->query("exec dbo.getAgendaQtdeAtendimento {$nr_ano}, {$nr_mes}, N'{$id_empresa}'");
    $qry = $pdo->query("exec dbo.setHorariosAgenda N'{$nr_dia}/{$nr_mes}/{$nr_ano}', N'{$id_empresa}', 0, 0");
    $pdo->commit();
    
    // Fechar conexão PDO
    unset($qry);
    unset($pdo);
?>
<html>
  <head>
    <meta charset="UTF-8">
    <title><?php echo Constante::SystemGCM;?> | Agenda</title>
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
          <div class="col-sm-1 invoice-col">
            Agenda do dia:
            <address>
                <strong><?php echo $nr_dia . "/" . $nr_mes . "/" . $nr_ano;?></strong><br>
            </address>
          </div><!-- /.col -->
          
          <div class="col-sm-11 invoice-col">
            Médico:
            <address>
                <strong><?php echo $empresa['nm_apresentacao'];?></strong><br>
            </address>
          </div><!-- /.col -->
          
        <!-- Table row -->
        <div class="row">
          <div class="col-xs-12 table-responsive">
            <table class="table table-striped" style="margin-left: 5px; margin-right: 5px;">
              <thead>
                <tr>
                  <th style="text-align: center;">Horário</th>
                  <th>Paciente</th>
                  <th>Contato</th>
                  <th>Atendimento</th>
                  <th>Especialidade</th>
                  <!--<th style="text-align: right;">Valor (R$)&nbsp;</th>-->
                </tr>
              </thead>
              <tbody>
                <?php
                    $pdo = Conexao::getConnection();
                    
                    $sql = 
                          "Select   "
                        . "    a.*  "
                        . "  , convert(varchar(12), a.dt_agenda, 103) as data_agenda  "
                        . "  , convert(varchar(8),  a.hr_agenda, 108) as hora_agenda  "
                        . "  , coalesce(p.nm_paciente, a.nm_paciente, '...') as paciente        "
                        . "  , coalesce(nullif(a.nr_celular, ''), nullif(a.nr_telefone, ''), nullif(p.nr_celular, ''), nullif(p.nr_telefone, ''), '...') as contato "
                        . "  , t.ds_tipo as ds_atendimento  "
                        . "  , s.ds_situacao                "
                        . "  , coalesce(e.ds_especialidade, '...') as ds_especialidade  "
                        . "  , coalesce(m.nm_profissional,  '...') as nm_profissional   "
                        . "from dbo.tbl_agenda a  "
                        . "  left join dbo.tbl_paciente p on (p.cd_paciente = a.cd_paciente)                "
                        . "  left join dbo.tbl_especialidade e on (e.cd_especialidade = a.cd_especialidade) "
                        . "  left join dbo.tbl_profissional m on (m.cd_profissional = a.cd_profissional)    "
                        . "  left join dbo.vw_situacao_agenda s on (s.cd_situacao = a.st_agenda)    "
                        . "  left join dbo.vw_tipo_atendimento t on (t.cd_tipo = a.tp_atendimento)  "
                        . "where (a.id_empresa = '{$id_empresa}')  "
                        . "  and (a.dt_agenda  = convert(date, '{$nr_dia}/{$nr_mes}/{$nr_ano}', 103))  "
                        . "order by      "
                        . "	   (case when a.st_agenda = 4 then 1 else 0 end) " // Deixar os agendamentos cancelados no final
                        . "	 , a.hr_agenda "; 

                    $qry = $pdo->query($sql);
                    while (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                        $ds_horario     = substr($obj->hora_agenda, 0, 5);
                        $ds_atendimento = ((int)$obj->st_agenda === 4?$obj->ds_situacao . " * ":$obj->ds_atendimento);
                        
                        echo "<tr>";
                        echo "  <td style='text-align: center;'>{$ds_horario}</td>";
                        echo "  <td>{$obj->paciente}</td>";
                        echo "  <td>{$obj->contato}</td>";
                        echo "  <td>{$ds_atendimento}</td>";
                        echo "  <td>{$obj->ds_especialidade}</td>";
                        echo "</tr>";
                    }
                ?>
              </tbody>
            </table>
          </div><!-- /.col -->
        </div><!-- /.row -->

      </section><!-- /.content -->
    </div><!-- ./wrapper -->

    <!-- AdminLTE App -->
    <script src="../../dist/js/app.min.js" type="text/javascript"></script>
  </body>
</html>
