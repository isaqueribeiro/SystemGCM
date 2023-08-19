<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of constantes
 *
 * @author Isaque
 */
class Constante {
    const SystemGCM = "SystemGCM";
    const System    = "System";
    const GCM       = "GCM";
    const bGCM      = "<b>G</b>CM";
    const Versao    = "v1.0";
    const SystemKey = "user_SystemGCM";
    const SystemDescription = "Sistema para Gestão de Consultórios Médicos";
    
    const PerfilAdministradorSistema = 1;
    const PerfilAdministrador = 2;
    const PerfilGerencia = 3;
    const PerfilRecepcao = 4;
    const PerfilProfissionalMedico = 5;
    
    // Configurações de Upload de arquivos
    const  UPLOAD = [
          'storage'    => 'logs/storage/'
        , 'max_size'   => (1024 * 1024 * 3) // 3 megabytes
        , 'rename'     => true
        , 'extensions' => array('jpeg', 'jpg', 'png', 'pdf')
        , 'error'      => [
              '0' => 'Não houve erro'
            , '1' => 'O arquivo do upload é maior do que o tamanho limite no PHP'
            , '2' => 'O arquivo ultrapassa o tamanho limite especifiado na plataforma'
            , '3' => 'O upload do arquivo foi feito parcialmente'
            , '4' => 'Não foi feito o upload do arquivo'
        ]
    ];

    // Configurações de acesso à AWS Amazon
    const AWS_AMAZON_REGION = [
        'version' => 'latest',
        'region'  => 'sa-east-1'
    ];
    const AWS_AMAZON_CREDENTIALS = [
        'key'     => 'AKIASIXPT3QXJDZUYSIT',
        'secret'  => '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'
    ];
    const AWS_AMAZON_S3_BUCKET = 'aws-systemgcm-arquivos';
    const AWS_AMAZON_S3_SERVER_ENABLED = false;
    const AWS_AMAZON_S3_INIT_URL = 's3://aws-systemgcm-arquivos/';
}
