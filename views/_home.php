<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 * Link para visualizar as opções de ICON-IOS:
 * https://ionicframework.com/docs/v3/ionicons/
 * 
 
    Select 
        DatePart(Year,  a.dt_agenda)  as nr_ano
      , DatePart(Month, a.dt_agenda)  as nr_mes
      , Convert(Varchar(10), x.dt_inicial, 103) as dt_inicial
      , Convert(Varchar(10), x.dt_final, 103)   as dt_final 
      , Concat(DateName(Month, a.dt_agenda), '/', DateName(Year, a.dt_agenda)) as ds_mes

      , sum( case when (a.st_agenda between 0 and 4) then 1 else 0 end ) as qtde_livre
      , sum( case when (a.st_agenda between 1 and 3) then 1 else 0 end ) as qtde_agendado
      , sum( case when (a.st_agenda = 3) then 1 else 0 end )			 as qtde_atendido

      , sum( case when (a.st_agenda = 0) then 1 else 0 end ) as qt_livre
      , sum( case when (a.st_agenda = 1) then 1 else 0 end ) as qt_agendado
      , sum( case when (a.st_agenda = 2) then 1 else 0 end ) as qt_confirmado
      , sum( case when (a.st_agenda = 3) then 1 else 0 end ) as qt_atendido
      , sum( case when (a.st_agenda = 4) then 1 else 0 end ) as qt_cancelado
      , sum( case when (a.st_agenda = 5) then 1 else 0 end ) as qt_bloqueado
    from (
      Select
          a.id_empresa
        , min(a.dt_agenda) as dt_inicial
        , max(a.dt_agenda) as dt_final
      from dbo.tbl_agenda a
      where (a.id_empresa = '{300CE361-27DC-4FB1-85D8-63A2F746CD11}')
        and (DateDiff(Day, a.dt_agenda, getdate()) <= 180) -- 30 x 6 = 6 meses 
      group by
          a.id_empresa
    ) x
      inner join dbo.tbl_agenda a on (a.id_empresa = x.id_empresa and a.dt_agenda between x.dt_inicial and x.dt_final)
    group by
        DatePart(Year,  a.dt_agenda)
      , DatePart(Month, a.dt_agenda)
      , Convert(Varchar(10), x.dt_inicial, 103)
      , Convert(Varchar(10), x.dt_final, 103)
      , Concat(DateName(Month, a.dt_agenda), '/', DateName(Year, a.dt_agenda))
    order by
        DatePart(Year,  a.dt_agenda)
      , DatePart(Month, a.dt_agenda)
 
 */
   
    if (isset($_REQUEST['exec'])) {
        if ($_REQUEST['exec'] === 'reload') {
            require '../dist/dao/conexao.php';
            
            $pdo = Conexao::getConnection();
            $qry   = $pdo->query("Select * from dbo.sys_empresa");
            $dados = $qry->fetchAll(PDO::FETCH_ASSOC);
            $empresa = null; 
            foreach($dados as $item) {
                $empresa = $item;
            }
            $qry->closeCursor();
        }
    }
    
    $dias_semana = array('Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sabado');
    $dia_semana = $dias_semana[date('w', strtotime(date('Y-m-d')))];
    $data = date('d/m/Y');
  
    $disponibilidade = null;
    $qt_ocupacao   = 0;
    $pr_ocupacao   = 0.0;
    $pr_confirmado = 0.0;
    $pr_atendido   = 0.0;
    
    $pdo = Conexao::getConnection();
    $qry = $pdo->query("Exec dbo.getDisponibilidadeAgenda N'{$empresa['id_empresa']}', N'{$data}'");
    $dados   = $qry->fetchAll(PDO::FETCH_ASSOC); 
    foreach($dados as $item) {
        $disponibilidade = $item;
        
        $qt_ocupacao   = intval($disponibilidade['qt_horarios']) - intval($disponibilidade['qt_disponivel']);
        $pr_ocupacao   = (intval($disponibilidade['qt_horarios']) > 0?($qt_ocupacao / intval($disponibilidade['qt_horarios'])):0) * 100.0;
        $pr_confirmado = (intval($disponibilidade['qt_agendamentos']) > 0?(intval($disponibilidade['qt_confirmacoes']) / intval($disponibilidade['qt_agendamentos'])):0) * 100;
        $pr_atendido   = (intval($disponibilidade['qt_confirmacoes']) > 0?(intval($disponibilidade['qt_atendidos']) / intval($disponibilidade['qt_confirmacoes'])):0) * 100;
    }

    $sql = 
          "SET LANGUAGE Portuguese; "
        . "Select "
        . "    DatePart(Year,  a.dt_agenda)  as nr_ano "
        . "  , DatePart(Month, a.dt_agenda)  as nr_mes "
        . "  , Convert(Varchar(10), x.dt_inicial, 103) as dt_inicial "
        . "  , Convert(Varchar(10), x.dt_final, 103)   as dt_final  "
        . "  , Concat(DateName(Month, a.dt_agenda), '/', DateName(Year, a.dt_agenda)) as ds_mes "
        . "  "
        . "  , sum( case when (a.st_agenda between 0 and 4) then 1 else 0 end ) as qtde_livre "
        . "  , sum( case when (a.st_agenda between 1 and 3) then 1 else 0 end ) as qtde_agendado "
        . "  , sum( case when (a.st_agenda = 3) then 1 else 0 end )		as qtde_atendido "
        . "  "
        . "  , sum( case when (a.st_agenda = 0) then 1 else 0 end ) as qt_livre      "
        . "  , sum( case when (a.st_agenda = 1) then 1 else 0 end ) as qt_agendado   "
        . "  , sum( case when (a.st_agenda = 2) then 1 else 0 end ) as qt_confirmado "
        . "  , sum( case when (a.st_agenda = 3) then 1 else 0 end ) as qt_atendido   "
        . "  , sum( case when (a.st_agenda = 4) then 1 else 0 end ) as qt_cancelado  "
        . "  , sum( case when (a.st_agenda = 5) then 1 else 0 end ) as qt_bloqueado  "
        . "from ( "
        . "  Select "
        . "      a.id_empresa "
        . "    , min(a.dt_agenda) as dt_inicial "
        . "    , max(a.dt_agenda) as dt_final "
        . "  from dbo.tbl_agenda a "
        . "  where (a.id_empresa = '{$empresa['id_empresa']}') "
        . "    and (a.dt_agenda <= getdate()) " 
        . "    and (DateDiff(Day, a.dt_agenda, getdate()) <= 180) " // <= 30 x 6 = 6 meses  
        . "  group by "
        . "      a.id_empresa "
        . ") x "
        . "  inner join dbo.tbl_agenda a on (a.id_empresa = x.id_empresa and a.dt_agenda between x.dt_inicial and x.dt_final) "
        . "group by "
        . "    DatePart(Year,  a.dt_agenda) "
        . "  , DatePart(Month, a.dt_agenda) "
        . "  , Convert(Varchar(10), x.dt_inicial, 103) "
        . "  , Convert(Varchar(10), x.dt_final, 103) "
        . "  , Concat(DateName(Month, a.dt_agenda), '/', DateName(Year, a.dt_agenda)) "
        . "order by "
        . "    DatePart(Year,  a.dt_agenda) "
        . "  , DatePart(Month, a.dt_agenda) ";
    
    $qry = $pdo->query($sql);
    $valores = $qry->fetchAll(PDO::FETCH_ASSOC); 
    $periodo = null;
    foreach($valores as $item) {
        $periodo = $item;
    }
?>
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        SystemGCM
        <small>Sistema para Gestão de Consultórios Médicos</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="#" class="active" id="page-click" onclick="preventDefault()"><i class="fa fa-home"></i> Home</a></li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      
        <div class="row">
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-aqua"><i class="ion ion-ios-calendar-outline"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">HOJE</span>
                        <span class="info-box-number"><?php echo $data;?><br><small><?php echo $dia_semana;?></small></span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-yellow">
                    <span class="info-box-icon"><i class="ion ion-ios-calendar"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Agendamentos</span>
                        <span class="info-box-number"><?php echo (isset($disponibilidade) ? $disponibilidade['qt_agendamentos'] : "0");?></span>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $pr_ocupacao;?>%"></div>
                        </div>
                        <span class="progress-description"><?php echo number_format($pr_ocupacao, 0, ",", ".");?>% da agenda acupada</span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-green">
                    <span class="info-box-icon"><i class="ion ion-ios-calendar"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Agendamentos confirmados</span>
                        <span class="info-box-number"><?php echo (isset($disponibilidade) ? $disponibilidade['qt_confirmacoes'] : "0");?></span>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $pr_confirmado;?>%"></div>
                        </div>
                        <span class="progress-description"><?php echo number_format($pr_confirmado, 0, ",", ".");?>% dos agendamentos confirmados</span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-blue-active">
                    <span class="info-box-icon"><i class="ion ion-ios-heart-outline"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Atendimentos</span>
                        <span class="info-box-number"><?php echo (isset($disponibilidade) ? $disponibilidade['qt_atendidos'] : "0");?></span>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $pr_atendido;?>%"></div>
                        </div>
                        <span class="progress-description"><?php echo number_format($pr_atendido, 0, ",", ".");?>% dos atendimentos de hoje finalizados</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Consolidação de Agendamentos e Atendimentos</h3>
                    </div>
                    
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-8">
                                <p class="text-center">
                                    <strong>Agendamentos x Atendimentos : <?php echo (isset($periodo) ? $periodo['dt_inicial'] . " - " . $periodo['dt_final'] : "" );?></strong>
                                </p>
                                <div class="chart">
                                    <canvas id="salesChart" style="height: 180px;"></canvas>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <p class="text-center">
                                    <strong>Consolidado da agenda : <?php echo (isset($periodo) ? $periodo['dt_inicial'] . " - " . $periodo['dt_final'] : "");?></strong>
                                </p>
                                
                                <?php
                                $grafic_labels_mes = "x, ";
                                $grafic_data_agend = "0, ";
                                $grafic_data_atend = "0, ";
                                
                                $qt_livre      = 0;
                                $qt_agendado   = 0;
                                $qt_confirmado = 0; 
                                $qt_atendido   = 0;
                                $qt_cancelado  = 0;
                                
                                if ( count($valores) != 0 ) {
                                    foreach($valores as $obj) {
                                        $grafic_labels_mes .= "'" . ucfirst($obj['ds_mes']) . "', ";
                                        $grafic_data_agend .= $obj['qtde_agendado'] . ", ";
                                        $grafic_data_atend .= $obj['qtde_atendido'] . ", ";

                                        $qt_livre      += intval($obj['qt_livre']);
                                        $qt_agendado   += intval($obj['qt_agendado']) + intval($obj['qt_confirmado']) + intval($obj['qt_atendido']) + intval($obj['qt_cancelado']);
                                        $qt_confirmado += intval($obj['qt_confirmado']) + intval($obj['qt_atendido']);
                                        $qt_atendido   += intval($obj['qt_atendido']);
                                        $qt_cancelado  += intval($obj['qt_cancelado']);
                                    }
                                } else {
                                    $grafic_labels_mes .= "'Vazio', ";
                                    $grafic_data_agend .= "0, ";
                                    $grafic_data_atend .= "0, ";
                                }

                                $grafic_labels_mes = substr($grafic_labels_mes, 3, strlen($grafic_labels_mes) - 5);
                                $grafic_data_agend = substr($grafic_data_agend, 3, strlen($grafic_data_agend) - 5);
                                $grafic_data_atend = substr($grafic_data_atend, 3, strlen($grafic_data_atend) - 5);
                                
                                $qt_disponivel = ($qt_livre - $qt_agendado);
                                
                                $pc_disponivel = ($qt_livre == 0?0:($qt_disponivel / $qt_livre) * 100);
                                $pc_agendado   = ($qt_livre == 0?0:($qt_agendado / $qt_livre) * 100);
                                $pc_confirmado = ($qt_agendado == 0?0:($qt_confirmado / $qt_agendado) * 100);
                                $pc_atendido   = ($qt_agendado == 0?0:($qt_atendido / $qt_agendado) * 100);
                                $pc_cancelado  = ($qt_agendado == 0?0:($qt_cancelado / $qt_agendado) * 100);
                                ?>

                                <div class='progress-group'>
                                    <span class='progress-text'>Agenda Livre</span>
                                    <span class='progress-number'><b><?php echo $qt_disponivel;?></b>/<?php echo $qt_livre;?></span>
                                    <div class='progress sm'>
                                        <div class='progress-bar progress-bar-aqua' style='width: <?php echo $pc_disponivel;?>%'></div>
                                    </div>
                                </div>
                                
                                <div class='progress-group'>
                                    <span class='progress-text'>Agendamentos</span>
                                    <span class='progress-number'><b><?php echo $qt_agendado;?></b>/<?php echo $qt_livre;?></span>
                                    <div class='progress sm'>
                                        <div class='progress-bar progress-bar-yellow' style='width: <?php echo $pc_agendado;?>%'></div>
                                    </div>
                                </div>
                                
                                <div class='progress-group'>
                                    <span class='progress-text'>Confirmados</span>
                                    <span class='progress-number'><b><?php echo $qt_confirmado;?></b>/<?php echo $qt_agendado;?></span>
                                    <div class='progress sm'>
                                        <div class='progress-bar progress-bar-green' style='width: <?php echo $pc_confirmado;?>%'></div>
                                    </div>
                                </div>
                                
                                <div class='progress-group'>
                                    <span class='progress-text'>Atendidos</span>
                                    <span class='progress-number'><b><?php echo $qt_atendido;?></b>/<?php echo $qt_agendado;?></span>
                                    <div class='progress sm'>
                                        <div class='progress-bar progress-bar-primary' style='width: <?php echo $pc_atendido;?>%'></div>
                                    </div>
                                </div>
                                
                                <div class='progress-group'>
                                    <span class='progress-text'>Cancelamentos</span>
                                    <span class='progress-number'><b><?php echo $qt_cancelado;?></b>/<?php echo $qt_agendado;?></span>
                                    <div class='progress sm'>
                                        <div class='progress-bar progress-bar-red' style='width: <?php echo $pc_cancelado;?>%'></div>
                                    </div>
                                </div>
                                

                            </div>
                        </div>
                    </div>
                    
                    <div class="box-footer">
                        
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            function getCharts() {
                var salesChartCanvas = $('#salesChart').get(0).getContext('2d');
                var salesChart       = new Chart(salesChartCanvas);
                var salesChartData = {
                    labels  : [<?php echo $grafic_labels_mes;?>],
                    datasets: [
                      {
                        label               : 'Agendamentos',
                        fillColor           : 'rgb(210, 214, 222)',
                        strokeColor         : 'rgb(210, 214, 222)',
                        pointColor          : 'rgb(210, 214, 222)',
                        pointStrokeColor    : '#c1c7d1',
                        pointHighlightFill  : '#fff',
                        pointHighlightStroke: 'rgb(220,220,220)',
                        data                : [<?php echo $grafic_data_agend;?>]
                      },
                      {
                        label               : 'Atendimentos',
                        fillColor           : 'rgba(60,141,188,0.9)',
                        strokeColor         : 'rgba(60,141,188,0.8)',
                        pointColor          : '#3b8bba',
                        pointStrokeColor    : 'rgba(60,141,188,1)',
                        pointHighlightFill  : '#fff',
                        pointHighlightStroke: 'rgba(60,141,188,1)',
                        data                : [<?php echo $grafic_data_atend;?>]
                      }
                    ]
                };

                var salesChartOptions = {
                    // Boolean - If we should show the scale at all
                    showScale               : true,
                    // Boolean - Whether grid lines are shown across the chart
                    scaleShowGridLines      : false,
                    // String - Colour of the grid lines
                    scaleGridLineColor      : 'rgba(0,0,0,.05)',
                    // Number - Width of the grid lines
                    scaleGridLineWidth      : 1,
                    // Boolean - Whether to show horizontal lines (except X axis)
                    scaleShowHorizontalLines: true,
                    // Boolean - Whether to show vertical lines (except Y axis)
                    scaleShowVerticalLines  : true,
                    // Boolean - Whether the line is curved between points
                    bezierCurve             : true,
                    // Number - Tension of the bezier curve between points
                    bezierCurveTension      : 0.3,
                    // Boolean - Whether to show a dot for each point
                    pointDot                : false,
                    // Number - Radius of each point dot in pixels
                    pointDotRadius          : 4,
                    // Number - Pixel width of point dot stroke
                    pointDotStrokeWidth     : 1,
                    // Number - amount extra to add to the radius to cater for hit detection outside the drawn point
                    pointHitDetectionRadius : 20,
                    // Boolean - Whether to show a stroke for datasets
                    datasetStroke           : true,
                    // Number - Pixel width of dataset stroke
                    datasetStrokeWidth      : 2,
                    // Boolean - Whether to fill the dataset with a color
                    datasetFill             : true,
                    // String - A legend template
                    legendTemplate          : '<ul class=\'<%=name.toLowerCase()%>-legend\'><% for (var i=0; i<datasets.length; i++){%><li><span style=\'background-color:<%=datasets[i].lineColor%>\'></span><%=datasets[i].label%></li><%}%></ul>',
                    // Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
                    maintainAspectRatio     : true,
                    // Boolean - whether to make the chart responsive to window resizing
                    responsive              : true
                };

                // Create the line chart
                salesChart.Line(salesChartData, salesChartOptions);
            }
        </script>
    </section>
    <!-- /.content -->

        