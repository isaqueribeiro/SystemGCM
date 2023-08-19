<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of usuario
 *
 * @author Isaque
 */

abstract class Autenticador {
    private static $isntancia = null;

    private function __construct() {}

    public $retorno = "";
    public $usuario = null;
    
    /**
     * 
     * @return Autenticador
     */
    public static function getInstancia() {
        if (self::$isntancia == NULL) {
            self::$isntancia = new Login();
        }
        
        return self::$isntancia;
    }
    
    public abstract function gerar_arquivo_vazio($file_name, $chave);
    public abstract function registrar($user, $tokenID);
    public abstract function logar($login, $senha, $tokenID); 
    public abstract function expulsar();
    public abstract function bloquear();
    public abstract function esta_logado();
    public abstract function pegar_usuario();
    public abstract function desbloquear_sessao();
    public abstract function usuario_medico();
    public abstract function bloquear_sessao();
}

class Login extends Autenticador {
    
    public function gerar_arquivo_vazio($file_name, $chave) {
        if (!file_exists($file_name)) {
            $registros = array($chave => array());
            $json = json_encode($registros);
            file_put_contents($file_name, $json);
        }
    }
    
    public function registrar($user, $tokenID) {
        $this->usuario = $user;
        $sess  = Sessao::getInstancia();
        $key   = 'user_SystemGCM';
        $token = md5($_SERVER["REMOTE_ADDR"] . "-" . date("d/m/Y"));
        
        if ($token !== $tokenID) {
            $this->retorno = "denied";
            $sess->set($key, "Token inválido.<br>Não é premitido acesso às informações do sistema.");
            return false;
            exit;
        }
        
        $pdo = Conexao::getConnection();
        try {
            $qry = $pdo->query(
                  "Select * "
                . "from dbo.sys_usuario "
                . "where lower(ds_email) = lower('{$this->usuario->getEmail()}')");
                
            if ((($obj = $qry->fetch(PDO::FETCH_OBJ))) !== false) {
                $this->retorno = "denied";
                $sess->set($key, "O e-mail <strong>{$obj->ds_email}</strong> já está registrado para o usuário <strong>{$obj->nm_usuario}</strong>.");
                return false;
            } else {
                $pdo->beginTransaction();
                $stm = $pdo->prepare(
                      "Insert Into dbo.sys_usuario ("
                    . "    id_usuario "
                    . "  , nm_usuario "
                    . "  , ds_email   "
                    . "  , ds_senha   "
                    . ") values (     "
                    . "    dbo.ufnGetGuidID() "
                    . "  , :nome   "
                    . "  , :email  "
                    . "  , :senha  "
                    . ")");
                
                $stm->execute(array(
                      ':nome'  => $this->usuario->getNome()
                    , ':email' => $this->usuario->getEmail()
                    , ':senha' => sha1($this->usuario->getEmail() . $this->usuario->getSenha())
                ));
                $pdo->commit();
                unset($pdo);
                
                $this->retorno = "OK";
                $sess->set($key, "Registro realizado com sucesso para e e-mail <strong>{$this->usuario->getEmail()}</strong>.<br>Aguarde receber no e-mail informado a sua autorização de acesso ao sistema.");
                return true;
            }
        } catch (Exception $ex) {
            $pdo->rollBack();
            $this->retorno = "error";
            $sess->set($key, $ex->getMessage());
            return false;
        }  
    }
    
    public function logar($login, $senha, $tokenID) {
        $sess  = Sessao::getInstancia();
        $key   = 'user_SystemGCM';
        $token = md5($_SERVER["REMOTE_ADDR"] . "-" . date("d/m/Y"));
        
        if ($token !== $tokenID) {
            $this->retorno = "error";
            $sess->set($key, "Token inválido.<br>Não é premitido acesso às informações do sistema.");
            return false;
            exit;
        }
        
        try {
            $pdo = Conexao::getConnection();
            
            $qry = $pdo->query("Select * from dbo.sys_empresa");
            $empresa = $qry->fetch(PDO::FETCH_OBJ);
            $qry->closeCursor();
            
            $sql =
                  "Select "
                . "    u.id_usuario "
                . "  , u.cd_usuario "
                . "  , u.nm_usuario "
                . "  , u.ds_email   "
                . "  , u.ds_senha   "
                . "  , u.cd_perfil  "
                . "  , p.ds_perfil  "
                . "  , u.id_token   "
                . "  , u.tp_plataforma "
                . "  , u.ft_usuario    "
                . "  , e.id_empresa    "
                . "  , e.sn_ativo      "
                . "  , case when m.cd_profissional is not null then 1 else 0 end as sn_medico "
                . "  , convert(varchar, e.dh_ativacao, 103) as dt_ativacao "
                . "  , convert(varchar, e.dh_ativacao, 108) as hr_ativacao "
                // Profisional
                . "  , coalesce(m.cd_profissional, 0) as cd_profissional "
                . "  , m.nm_apresentacao "
                . "  , m.ds_conselho     "
                . "  , coalesce(nullif(trim(m.nm_apresentacao), ''), m.nm_profissional, u.nm_usuario) as apresentacao  "
                . "from dbo.sys_usuario u "
                . "  left join dbo.sys_perfil p on (p.cd_perfil = u.cd_perfil) "
                . "  left join dbo.sys_usuario_empresa e on (e.id_empresa = '{$empresa->id_empresa}' and e.id_usuario = u.id_usuario) "
                . "  left join dbo.tbl_profissional m on (m.id_usuario = e.id_usuario and m.id_empresa = e.id_empresa) "
                . "where lower(u.ds_email) = lower('{$login}')";
                
            $qry = $pdo->query($sql);

            if (($obj = $qry->fetch(PDO::FETCH_OBJ)) !== false) {
                if (!isset($obj->id_empresa)) {
                    $this->retorno = "denied";
                    $sess->set($key, "Seu registro ainda está pendente.<br>Aguarde receber em seu e-mail a confirmação de acesso.");
                    return false;
                } else {
                    $senhaBase = $obj->ds_senha;
                    $senhaInfo = sha1($login . $senha);
                    if ($senhaBase === $senhaInfo) {
                        $user  = new Usuario();
                        $perf  = new Perfil();
                        $token = sha1(date("d/m/Y") . "-" . $user->getCodigo());
                                
                        // Gravar usuário logado
                        $file = '../../logs/cookies/login_' . md5($_SERVER["REMOTE_ADDR"]) . '.json';
                        if (file_exists($file)) {
                            unlink($file);
                        }
                        $registro = array('user' => array());
                        $registro['user'][0]['login'] = strtolower($obj->ds_email);
                        $json = json_encode($registro);
                        file_put_contents($file, $json);
                        
                        $perf->setCodigo($obj->cd_perfil);
                        $perf->setDescricao($obj->ds_perfil);
                        
                        $user->setCodigo($obj->id_usuario);
                        $user->setNome(ucwords(strtolower( $obj->nm_usuario ))); 
                        $user->setEmail(strtolower($obj->ds_email));
                        $user->setSenha($senha);
                        $user->setToken($token);
                        $user->setPerfil($perf);
                        $user->setBloqueado(false);
                        $user->setMedico( ((int)$obj->sn_medico === 1) );
                        $user->setProfissional( $obj->cd_profissional );
                        $user->setData_ativacao( (isset($obj->dt_ativacao)?$obj->dt_ativacao:date('d/m/Y')) );

                        $this->retorno = "OK";
                        $this->usuario = $user;
                        $sess->set($key, $user);
                        
                        session_start();
                        $_SESSION['user'] = serialize($user);

                        $qry = null;
                        $pdo = null;

                        // Gravar as configurações do filtro utilizado pelo usuário -- (INICIO)
                        $this->gerar_arquivo_vazio('../../logs/cookies/cidade_' . sha1($user->getCodigo()) . '.json', 'filtro');
                        $this->gerar_arquivo_vazio('../../logs/cookies/bairro_' . sha1($user->getCodigo()) . '.json', 'filtro');
                        $this->gerar_arquivo_vazio('../../logs/cookies/cep_'    . sha1($user->getCodigo()) . '.json', 'filtro');
                        // Gravar as configurações do filtro utilizado pelo usuário -- (FINAL)
                        
                        return true;
                    } else {
                        $this->retorno = "denied";
                        $sess->set($key, "Usuário e/ou senha inválidos!");
                        return false;
                    }
                }
            } else {
                unset($qry);
                unset($pdo);
                
                $this->retorno = "denied";
                $sess->set($key, "Usuário não cadastrado.<br>Solicite acesso ao sistema.");
                return false;
            }
        } catch (Exception $ex) {
            $this->retorno = "error";
            $sess->set($key, $ex->getMessage());
            return false;
        }  
    }

    public function expulsar() {
        header('location: ../php/controller.php?acao=close');
    }

    public function bloquear() {
        header('location: ../php/controller.php?acao=lock');
    }

    public function esta_logado() {
        $sess = Sessao::getInstancia();
        $key  = 'user_SystemGCM';
        return $sess->existe($key);
    }

    public function pegar_usuario() {
        $sess = Sessao::getInstancia();
        $key  = 'user_SystemGCM';
        
        if ($this->esta_logado()) {
            $usuario = $sess->get($key);
            return $usuario;
        } else {
            return false;
        }
    }

    public function desbloquear_sessao() {
        $sess = Sessao::getInstancia();
        $key  = 'user_SystemGCM';
        
        if ($this->esta_logado()) {
            $usuario = $sess->get($key);
            $usuario->setBloqueado(false);
            $sess->set($key, $usuario);
        }
    }

    public function usuario_medico() {
        $sess = Sessao::getInstancia();
        $key  = 'user_SystemGCM';
        
        if ($this->esta_logado()) {
            $usuario = $sess->get($key);
            return ((int)$usuario->getPerfil()->getCodigo() === 5); // Perfil Médico
        } else {
            return false;
        }
    }

    public function bloquear_sessao() {
        $sess = Sessao::getInstancia();
        $key  = 'user_SystemGCM';
        
        if ($this->esta_logado()) {
            $usuario = $sess->get($key);
            $usuario->setBloqueado(true);
            $sess->set($key, $usuario);
        }
    }
}
