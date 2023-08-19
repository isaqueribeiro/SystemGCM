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
    
    $id_empresa     = $_REQUEST['ep'];
    $id_atendimento = $_REQUEST['at'];
    $cd_paciente    = $_REQUEST['pac'];
    $dt_atendimento = date('d/m/Y');
    
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
        $atendimento    = $item;
        $dt_atendimento = $atendimento['data_atendimento'];
    }
    
    if ($atendimento === null) {
        $qry   = $pdo->query("exec dbo.getAgendamentoPaciente N'{$id_atendimento}', N'{$id_empresa}'");
        $dados = $qry->fetchAll(PDO::FETCH_ASSOC);
        $atendimento = null;
        foreach($dados as $item) {
            $atendimento    = $item;
            $dt_atendimento = $atendimento['data_atendimento'];
        }
    }
    
    if ($atendimento === null) {
        $sql = 
              "Select * "
            . "from dbo.tbl_agenda a  "
            . "where (a.id_agenda in ( "
            . "  Select  "
            . "    max(a.id_agenda) "
            . "  from dbo.tbl_agenda a "
            . "  where (a.id_empresa  = '{$id_empresa}') "
            . "	   and (a.cd_paciente = {$cd_paciente})  "
            . ")) ";
        $qry = $pdo->query($sql);
        if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
            $id_atendimento = $obj->id_agenda;
        }
                
        $qry   = $pdo->query("exec dbo.getDadosPaciente {$cd_paciente}, N'{$id_atendimento}', N'{$id_empresa}'");
        $dados = $qry->fetchAll(PDO::FETCH_ASSOC);
        $atendimento = null;
        foreach($dados as $item) {
            $atendimento = $item;
        }
    }
    
    // Fechar conexão PDO
    unset($qry);
    unset($pdo);
?>
<html>
  <head>
    <meta charset="UTF-8">
    <title><?php echo Constante::SystemGCM;?> | Controle de Evoluções de Medidas</title>
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
              <h4 class="text-uppercase text-center">Controle de Evoluções de Medidas</h4>  
          </div><!-- /.col -->
        </div>
        
        <!-- info row -->
        <div class="row invoice-info">
          <div class="col-xs-12">
            Paciente
            <address>
              <strong><?php echo $atendimento['nm_paciente'];?></strong><br>
              <?php if (trim($atendimento['end_logradouro']) !== "") echo $atendimento['end_logradouro'] . ", " . $atendimento['end_bairro'] . "<br>";?>
              <?php echo $atendimento['end_cidade']. "/" . $atendimento['end_estado'] . " - Cep " . formatarTexto('##.###-###', str_pad($atendimento['nr_cep'], 8, "00000000", STR_PAD_LEFT));?><br>
              Celular: <?php echo $atendimento['celular'];?><br/>
              Email: <?php echo $atendimento['email'];?>
            </address>
          </div><!-- /.col -->
        </div><!-- /.row -->

        <div class="row invoice-info">
            <div class="col-xs-12 table-responsive">
                <table class="table table-striped table-bordered" style="font-size: 10px;">
                    <?php
                    
                        try {
                            $id_empresa     = $atendimento['empresa'];
                            $id_atendimento = $atendimento['id_atendimento'];
                            $cd_paciente    = (float)preg_replace("/[^0-9]/", "", "0" . (isset($atendimento['cd_paciente']))?$atendimento['cd_paciente']:$cd_paciente);

                            $pdo = Conexao::getConnection();

                            $sql = 
                                  "Select distinct  "
                                . "    p.id_evolucao   "
                                . "  , e.cd_evolucao   "
                                . "  , e.ds_evolucao   "
                                . "  , e.un_evolucao   "
                                . "from dbo.tbl_evolucao_medida_pac p    "
                                . "  inner join dbo.tbl_evolucao e on (e.id_evolucao = p.id_evolucao and e.id_empresa = '{$id_empresa}')   "
                                . "where (p.cd_paciente = {$cd_paciente})                           "
                                . "  and (p.dt_evolucao   <= convert(date, '{$dt_atendimento}', 103))  "
                                . "order by       "
                                . "    e.cd_evolucao "; 

                            $qry = $pdo->query($sql);
                            $evolucoes = $qry->fetchAll(PDO::FETCH_ASSOC);

                            $sql = 
                                  "Select "
                                . "  convert(varchar(12), x.dt_evolucao, 103) as dt_evolucao "
                                . "from (           "
                                . "	Select distinct "
                                . "	  convert(date, convert(varchar(12), dat.dt_evolucao, 103), 103) as dt_evolucao "
                                . "	from ( "
                                . "	  Select getdate() as dt_evolucao "
                                . "	  union     "
                                . "	  Select    "
                                . "		p.dt_evolucao "
                                . "	  from dbo.tbl_evolucao_medida_pac p "
                                . "   where (p.cd_paciente = {$cd_paciente})                           "
                                . "     and (p.dt_evolucao   <= convert(date, '{$dt_atendimento}', 103))  "
                                . "	) dat "
                                . ") x "
                                . "order by  "
                                . "  x.dt_evolucao DESC ";

                            $qry = $pdo->query($sql);
                            $datas = $qry->fetchAll(PDO::FETCH_ASSOC);

                            $retorno = 
                                  "  <thead> "
                                . "    <tr> "
                                . "      <th>#</th> " 
                                . "      <th>Evolução</th> ";

                            // Apenas os 7 últimos resultados de cada evolucao (x.dt_evolucao DESC)
                            $limite = (count($datas) <= 7?0:count($datas) - 7);

                            // Montando os cabeçalhos da tabela com as datas
                            for ($i = (count($datas) - 1); $i >= $limite; $i--) {
                                $dat = $datas[$i];
                                $retorno .= "      <th class='text-center' style='width: 8%;'>{$dat['dt_evolucao']}</th> ";
                            }

                            $retorno .= 
                                  "      <th>Und.</th> "
                                . "    </tr> "
                                . "  </thead> "
                                . "  <tbody> ";

                            $idx_evolucao = 0;

                            foreach($evolucoes as $exm) {
                                $ref   = substr($exm['id_evolucao'], 1, strlen($exm['id_evolucao']) - 2);
                                $input = "<input type='hidden' id='ref_controle_evolucao_{$idx_evolucao}' value='{$exm['id_evolucao']}'>";
                                $retorno .= 
                                      "<tr id='reg-linha_evolucao_{$ref}'>"
                                    . "  <td>" . str_pad($exm['cd_evolucao'], 2, "0", STR_PAD_LEFT) . "{$input}</td>"
                                    . "  <td>{$exm['ds_evolucao']}</td>";

                                // Recuperar os valores dos evolucoes de acordo com a data    
                                for ($i = (count($datas) - 1); $i >= $limite; $i--) {
                                    $dat = $datas[$i];
                                    $sql = "exec dbo.getEvolucaoPaciente N'{$id_empresa}', {$cd_paciente}, N'{$exm['id_evolucao']}', N'{$dat['dt_evolucao']}'"; 
                                    $qry = $pdo->query($sql);
                                    if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                                        $retorno .= "  <td class='text-right'>{$obj->vl_evolucao_texto}</td>";
                                    } else {
                                        $retorno .= "  <td class='text-right'>&nbsp;</td>";
                                    }
                                }

                                $retorno .= 
                                      "  <td>{$exm['un_evolucao']}</td>"
                                    . "</tr>";

                                $idx_evolucao += 1;      
                            }

                            $retorno .=
                                  "  </tbody> \n"
                                . "</table>   \n";

                            // Fechar conexão PDO
                            unset($qry);
                            unset($pdo);

                            echo $retorno;
                        } catch (Exception $ex) {
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }
                            echo $ex->getMessage() . (isset($pdo)?"<br><br>" . $pdo->errorInfo():"");
                        } 
                    
                    ?>
                </table>
            </div>
        </div>
      </section><!-- /.content -->
    </div><!-- ./wrapper -->

    <!-- AdminLTE App -->
    <script src="../../dist/js/app.min.js" type="text/javascript"></script>
  </body>
</html>
