<!DOCTYPE html>
<?php
    /*
     * Lista de zonas horárias:
     * https://www.php.net/manual/pt_BR/timezones.america.php
     */
    require_once './dist/php/sessao.php';
    require_once './dist/php/constantes.php';
    
    session_start();
    session_destroy();
    
    $token = $_SERVER["REMOTE_ADDR"] . "-" . date("d/m/Y");
    
    ini_set('default_charset', 'utf-8');
    ini_set('display_errors', true);
    error_reporting(E_ALL);
    date_default_timezone_set('America/Belem');
    
    $login = "";
    $file  = './logs/cookies/login_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
    if (file_exists($file)) {
        $file_cookie = file_get_contents($file);
        $json = json_decode($file_cookie);
        if (isset($json->user[0])) {
            $login = $json->user[0]->login;
        }
    }
?>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo Constante::SystemGCM;?> | Log in</title>
  <link rel="shortcut icon" href="icon.ico" >
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.6 -->
  <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="plugins/iCheck/square/blue.css">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
    <style>
        .example-modal .modal {
            position: relative;
            top: auto;
            bottom: auto;
            right: auto;
            left: auto;
            display: block;
            z-index: 1;
        }

        .example-modal .modal {
            background: transparent !important;
        }
    </style>
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
          <a href="index.php"><b><?php echo Constante::System;?></b><?php echo Constante::GCM;?> <span class="small"><?php echo Constante::Versao;?></span></a>
        </div>

        <?php
            //var_dump(isset($_SERVER['HTTPS']));
        ?>
        
        <div class="login-box-body">
          <p class="login-box-msg">Entre para iniciar sua sessão</p>

          <form action="dist/php/controller.php" method="post">
              <div class="form-group has-feedback">
                  <input type="hidden" id="token" name="token" value="<?php echo md5($token);?>">
                  <input type="email" class="form-control" placeholder="Email" name="email" id="email" value="<?php echo $login;?>" required>
                  <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
              </div>
              <div class="form-group has-feedback">
                  <input type="password" class="form-control" placeholder="Senha" name="senha" id="senha" required>
                  <span class="glyphicon glyphicon-lock form-control-feedback"></span>
              </div>
              <div class="row">
                  <div class="col-xs-8">
                      <div class="checkbox icheck">
              <!--            
                        <label>
                          <input type="checkbox"> Remember Me
                        </label>
              -->
                      </div>
                  </div>
                  <div class="col-xs-4">
                      <button type="submit" class="btn btn-primary btn-block btn-flat" name="acao" value="login">Entrar</button>
                  </div>
              </div>
          </form>

          <a href="#">Eu esqueci minha senha</a><br>
          <a href="views/registrar.php" class="text-center">Solicitar acesso ao sistema</a>

<!--      
          <p>
          <?php
//              require_once './dist/dao/conexao.php';
//              
//              try{
//        
//                  $conn = Conexao::getConnection();
//                  $qry  = $conn->query("Select * from dbo.sys_perfil");
//                  $perfis = $qry->fetchAll();
//      
//                  var_dump($perfis);
//                  
//                  foreach($perfis as $pefil) {
//                      echo $pefil['ds_perfil'] . "<br>";
//                  }
//              } catch (Exception $e){
//                  echo $e->getMessage();
//                  exit;
//              }
          ?>
          </p>    
-->
        </div>
        <?php
        include './views/modal.php';
        ?>
    </div>

    <!-- jQuery 2.2.3 -->
    <script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
    <!-- Bootstrap 3.3.6 -->
    <script src="bootstrap/js/bootstrap.min.js"></script>
    <!-- iCheck -->
    <script src="plugins/iCheck/icheck.min.js"></script>
    
    <script>
        $(function () {
            $('input').iCheck({
                checkboxClass: 'icheckbox_square-blue',
                radioClass   : 'iradio_square-blue',
                increaseArea : '20%' // optional
            });

            $('#email').focus();
            
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
