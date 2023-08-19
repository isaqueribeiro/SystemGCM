/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function carregar_arquivos_paciente(paciente, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'codigo' : paciente,
        'action' : 'carregar_arquivos_paciente'
    };
    
    if (params.codigo === "") {
        params.codigo = $('#cd_paciente').val();
    }
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_arquivo_paciente.php',
            // Definimos o tipo de requisição
            type: 'post',
            // Definimos o tipo de retorno
            dataType : 'html',
            // Dolocamos os valores a serem enviados
            data: params,
            // Antes de enviar ele alerta para esperar
            beforeSend : function(){
                document.body.style.cursor = "wait";
            },
            // Colocamos o retorno na tela
            success : function(data){
                var retorno = data;
                document.body.style.cursor = "auto";
                
                if (callback && typeof(callback) === "function") {
                    callback(retorno);
                }
                
                return true;
            },
            error: function (request, status, error) {
                document.body.style.cursor = "auto";
                show_erro("Erro", request.responseText + "<br>" + error + " (" + status + ")");
                return false;
            }
        });  
        // Finalizamos o Ajax
    } catch (e) {
        show_erro("Erro", e);
        return false;
    }
}

function upload_arquivo_paciente(e, paciente, callback) {
    e.preventDefault();
    
    var form = $('#form_arquivos')[0];
    var formData = new FormData(form);
    
    formData.append('token', $('#tokenID').val());
    formData.append('estacao', $('#estacaoID').val());
    formData.append('usuario', $('#userID').val());
    formData.append('empresa', $('#empresaID').val());
    formData.append('codigo', paciente);
    formData.append('action', 'upload_arquivo_paciente');
    formData.append('grupo', $('#cd_grupo_arquivo').val());
    formData.append('cd_arquivo', $('#cd_arquivo').val());
    formData.append('dt_arquivo', $('#dt_arquivo').val());
    formData.append('ds_arquivo', $('#ds_arquivo').val());
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_arquivo_paciente.php',
            enctype: 'multipart/form-data',
            // Definimos o tipo de requisição
            type: 'post',
            // Definimos o tipo de retorno
            dataType : 'html',
            // Dolocamos os valores a serem enviados
            data: formData,
            // Parâmetros importantes para upload de arquivos
            contentType: false,
            processData: false,
            // Antes de enviar ele alerta para esperar
            beforeSend : function(){
                document.body.style.cursor = "wait";
            },
            // Colocamos o retorno na tela
            success : function(data){
                var retorno = data;
                document.body.style.cursor = "auto";
                
                if (retorno === "OK") {
                    if (callback && typeof(callback) === "function") {
                        callback();
                    }
                    return true;
                } else {
                    show_alerta("Alerta", retorno);
                    return false;
                }
            },
            error: function (request, status, error) {
                document.body.style.cursor = "auto";
                show_erro("Erro", request.responseText + "<br>" + error + " (" + status + ")");
                return false;
            }
        });  
        // Finalizamos o Ajax
    } catch (e) {
        show_erro("Erro", e);
        return false;
    }
}

function visualizar_arquivo_paciente(arquivo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'codigo' : arquivo,
        'action' : 'visualizar_arquivo_paciente'
    };
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_arquivo_paciente.php',
            // Definimos o tipo de requisição
            type: 'post',
            // Definimos o tipo de retorno
            dataType : 'json',
            // Dolocamos os valores a serem enviados
            data: params,
            // Antes de enviar ele alerta para esperar
            beforeSend : function(){
                document.body.style.cursor = "wait";
            },
            // Colocamos o retorno na tela
            success : function(data){
                var retorno = data;
                document.body.style.cursor = "auto";
                
                if (retorno.success === true ) {
                    if (callback && typeof(callback) === "function") {
                        callback(retorno.arquivo);
                    }
                    return true;
                } else {
                    show_alerta("Alerta", retorno.message);
                    return false;
                }
            },
            error: function (request, status, error) {
                document.body.style.cursor = "auto";
                show_erro("Erro", request.responseText + "<br>" + error + " (" + status + ")");
                return false;
            }
        });  
        // Finalizamos o Ajax
    } catch (e) {
        show_erro("Erro", e);
        return false;
    }
}

function excluir_arquivo_paciente (codigo, descricao, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'empresa': $('#empresaID').val(),
        'usuario': $('#userID').val(),
        'codigo' : codigo,
        'action'   : 'excluir_arquivo_paciente'
    };
    
    try {
        show_confirmar("Excluir", "Você confirma a exclusão do arquivo <strong>'" + descricao + "'</strong>?");
        
        var botao = document.getElementById("primary_confirm");
        botao.onclick = function() {
            $('#btn_msg_primario').trigger('click');
            
            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : './dist/dao/dao_arquivo_paciente.php',
                // Definimos o tipo de requisição
                type: 'post',
                // Definimos o tipo de retorno
                dataType : 'json',
                // Dolocamos os valores a serem enviados
                data: params,
                // Antes de enviar ele alerta para esperar
                beforeSend : function(){
                    document.body.style.cursor = "wait";
                    Pace.restart();
                },
                // Colocamos o retorno na tela
                success : function(data){
                    var retorno = data;
                    document.body.style.cursor = "auto";

                    if (retorno.success === true ) {
                        if (callback && typeof(callback) === "function") {
                            callback(retorno.arquivo);
                        }
                        return true;
                    } else {
                        show_alerta("Alerta", retorno.message);
                        return false;
                    }
                },
                error: function (request, status, error) {
                    document.body.style.cursor = "auto";
                    show_erro("Erro", request.responseText + "<br>" + error + " (" + status + ")");
                    return false;
                }
            });  
            // Finalizamos o Ajax
        }
    } catch (e) {
        show_erro("Erro", e);
        return false;
    }
}

function salvar_dados_arquivo_paciente(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'empresa': $('#empresaID').val(),
        'usuario': $('#userID').val(),
        'codigo' : codigo,
        'id_arquivo' : $('#eid_arquivo').val(),
        'dt_arquivo' : $('#edt_arquivo').val(),
        'ds_arquivo' : $('#eds_arquivo').val(),
        'cd_grupo'   : $('#ecd_grupo').val(),
        'action'     : 'salvar_dados_arquivo_paciente'
    };
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_arquivo_paciente.php',
            // Definimos o tipo de requisição
            type: 'post',
            // Definimos o tipo de retorno
            dataType : 'json',
            // Dolocamos os valores a serem enviados
            data: params,
            // Antes de enviar ele alerta para esperar
            beforeSend : function(){
                document.body.style.cursor = "wait";
                Pace.restart();
            },
            // Colocamos o retorno na tela
            success : function(data){
                var retorno = data;
                document.body.style.cursor = "auto";

                if (retorno.success === true ) {
                    if (callback && typeof(callback) === "function") {
                        callback();
                    }
                    return true;
                } else {
                    show_alerta("Alerta", retorno.message);
                    return false;
                }
            },
            error: function (request, status, error) {
                document.body.style.cursor = "auto";
                show_erro("Erro", request.responseText + "<br>" + error + " (" + status + ")");
                return false;
            }
        });  
        // Finalizamos o Ajax
    } catch (e) {
        show_erro("Erro", e);
        return false;
    }
}