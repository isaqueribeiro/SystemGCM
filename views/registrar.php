<!DOCTYPE html>
<?php
    require_once '../dist/php/constantes.php';
    require_once '../dist/php/sessao.php';
    require_once '../dist/php/constantes.php';
    require_once '../dist/dao/autenticador.php';
    
    session_start();
    session_destroy();
    
    $token = $_SERVER["REMOTE_ADDR"] . "-" . date("d/m/Y");
    
    ini_set('default_charset', 'utf-8');
    ini_set('display_errors', true);
    error_reporting(E_ALL);
    date_default_timezone_set('America/Belem');
?>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo Constante::SystemGCM;?> | Registrar Usuário</title>
  <link rel="shortcut icon" href="../icon.ico" >
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.6 -->
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/AdminLTE.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="../plugins/iCheck/square/blue.css">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body class="hold-transition register-page">
<div class="register-box">
  <div class="register-logo">
      <a href="./registrar.php"><b><?php echo Constante::System;?></b><?php echo Constante::GCM;?> <span class="small"><?php echo Constante::Versao;?></span></a>
  </div>

  <div class="register-box-body">
    <p class="login-box-msg">Registre seu usuário para acesso ao sistema</p>

    <form action="../dist/php/controller.php" method="post">
        <div class="form-group has-feedback">
            <input type="hidden" id="token" name="token" value="<?php echo md5($token);?>">
            <input type="text" class="form-control" placeholder="Nome completo" name="nome" id="nome" required>
            <span class="glyphicon glyphicon-user form-control-feedback"></span>
        </div>
        <div class="form-group has-feedback">
            <input type="email" class="form-control" placeholder="Email" name="email" id="email" required>
            <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
        </div>
        <div class="form-group has-feedback">
            <input type="password" class="form-control" placeholder="Senha" name="senha" id="senha" required>
            <span class="glyphicon glyphicon-lock form-control-feedback"></span>
        </div>
        <div class="form-group has-feedback">
            <input type="password" class="form-control" placeholder="Confirmar senha" name="resenha" required>
            <span class="glyphicon glyphicon-log-in form-control-feedback"></span>
        </div>
        <div class="row">
            <div class="col-xs-8">
                <div class="checkbox icheck">
<!--                    
                    <label>
                        <input type="checkbox"> I agree to the <a href="#">terms</a>
                    </label>
-->
                </div>
            </div>

            <div class="col-xs-4">
                <button type="submit" class="btn btn-primary btn-block btn-flat"  name="acao" value="register">Enviar</button>
            </div>
        </div>
    </form>

    <a href="../index.php" class="text-center">Eu já tenho um usuário de acesso</a>
  </div>
  <!-- /.form-box -->
  
    <?php
    include './modal.php';
    ?>
</div>

    <!-- jQuery 2.2.3 -->
    <script src="../plugins/jQuery/jquery-2.2.3.min.js"></script>
    <!-- Bootstrap 3.3.6 -->
    <script src="../bootstrap/js/bootstrap.min.js"></script>
    <!-- iCheck -->
    <script src="../plugins/iCheck/icheck.min.js"></script>
    <script>
        $(function () {
            $('input').iCheck({
                checkboxClass: 'icheckbox_square-blue',
                radioClass   : 'iradio_square-blue',
                increaseArea : '20%' // optional
            });
            
            $("#btn_msg_padrao").fadeOut(1);
            $("#btn_msg_alerta").fadeOut(1);
            $("#btn_msg_erro").fadeOut(1);
            $("#btn_msg_informe").fadeOut(1);
            $("#btn_msg_primario").fadeOut(1);
            $("#btn_msg_sucesso").fadeOut(1);

            <?php 
            $msg = "";
            if (isset($_GET['tag'])) {
                $tag = $_GET["tag"];
                $msg = "";
                
                try {
                    $sess = Sessao::getInstancia();
                    $key  = 'user_SystemGCM';
                    $msg  = $sess->get($key);
                } catch (Exception $ex) {
                    $msg = "";
                }
                
                if ($msg !== "") {
                    if ($tag === 'OK') {
                        echo "$('#primary_title').html('Sucesso');";
                        echo "$('#primary_msg').html('{$msg}');";
                        echo "$('#btn_msg_primario').trigger('click');";
                    } else
                    if ($tag === 'erro_pwd') {
                        echo "$('#info_title').html('Senha');";
                        echo "$('#info_msg').html('{$msg}');";
                        echo "$('#btn_msg_informe').trigger('click'); \n";
                    } else
                    if ($tag === 'denied') {
                        echo "$('#info_title').html('Login');";
                        echo "$('#info_msg').html('{$msg}');";
                        echo "$('#btn_msg_informe').trigger('click');";
                    } else
                    if ($tag === 'error') {
                        echo "$('#danger_title').html('Login');";
                        echo "$('#danger_msg').html('{$msg}');";
                        echo "$('#btn_msg_erro').trigger('click');";
                    }
                }
            }
            ?>
        });
    </script>
</body>
</html>
