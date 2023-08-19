/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function pesquisar_usuarios() {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'tipo'   : $('#cd_tipo_filtro').val(),
        'filtro' : $('#ds_filtro').val(),
        'action' : 'pesquisar_usuarios',
        'qt_registro' : $('#qtde-registros-usuario').val()
    };
    
    // Iniciamos o Ajax 
    $.ajax({
        // Definimos a url
        url : './dist/dao/dao_usuario.php',
        // Definimos o tipo de requisição
        type: 'post',
        // Definimos o tipo de retorno
        dataType : 'html',
        // Dolocamos os valores a serem enviados
        data: params,
        // Antes de enviar ele alerta para esperar
        beforeSend : function(){
            document.body.style.cursor = "wait";
            $('#box-tabela').html("<i class='fa fa-spin fa-refresh'></i>&nbsp; Executando pesquisa, <strong>aguarde</strong>!");
        },
        // Colocamos o retorno na tela
        success : function(data){
            $('#box-tabela').html(data);
            document.body.style.cursor = "auto";
            if (typeof($('#tb-tabela_usuarios')) !== "undefined") {
                configurar_tabela('#tb-tabela_usuarios');
            }
        },
        error: function (request, status, error) {
            document.body.style.cursor = "auto";
            $('#box-tabela').html(request.responseText + "<br>" + error + " (" + status + ")");
        }
    });  
    // Finalizamos o Ajax
}

function carregar_registro_usuario(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'codigo' : codigo,
        'action' : 'carregar_usuario'
    };
    
    try {
        $('#operacao').val("editar");
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_usuario.php',
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
                if (retorno === "OK") { 
                    var file = "logs/json/usuario_" + params.estacao + ".json";
                    $.getJSON(file, function(data){
                        if(callback && typeof(callback) === "function") {
                            callback(data);
                        }
                        
                        return true;
                    });
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

function salvar_registro_usuario(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'usuario': codigo,
        'action'   : 'salvar_usuario',
        'id_usuario' : $('#id_usuario').val(),
        'cd_usuario' : $('#cd_usuario').val(),
        'nm_usuario' : $('#nm_usuario').val(),
        'cd_perfil'  : $('#cd_perfil').val(),
        'ds_email'   : $('#ds_email').val(),
        'ds_senha'   : $('#ds_senha').val(),
        'sn_ativo'   : '0'
    };
    
    if ( $('#sn_ativo').is(":checked") ) params.sn_ativo = $('#sn_ativo').val();
    
    try {
        var inserir = ($('#operacao').val() === "inserir");
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_usuario.php',
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
                if (retorno === "OK") {
                    var file = "logs/json/usuario_" + params.estacao + ".json";
                    $.getJSON(file, function(data){
                        if (inserir) {
                            $('#id_usuario').val(data.registro[0].id);
                            $('#cd_usuario').val(data.registro[0].codigo);
                            var newRow = $(data.registro[0].tr_table);
                            $("#tb-tabela_usuarios").append(newRow);
                        } else {
                            var tr_table = document.getElementById("tr-linha_" + data.registro[0].referencia); //$(data.registro[0].tr_table);
                            var colunas  = tr_table.getElementsByTagName('td');
                            
                            colunas[1].firstChild.nodeValue = params.nm_usuario;
                            colunas[2].firstChild.nodeValue = params.ds_email;
                            colunas[3].firstChild.nodeValue = $('#cd_perfil option:selected').text();
                        }

                        if (callback && typeof(callback) === "function") {
                            callback(data);
                        }
                    });                            

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

function excluir_registro_usuario(codigo, descricao, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'usuario': codigo,
        'action' : 'excluir_usuario'
    };
    
    try {
        show_confirmar("Excluir", "Você confirma a exclusão do usuário <strong>'" + descricao + "'</strong>?");
        
        var botao = document.getElementById("primary_confirm");
        botao.onclick = function() {
            $('#btn_msg_primario').trigger('click');
            
            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : './dist/dao/dao_usuario.php',
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
        }
    } catch (e) {
        show_erro("Erro", e);
        return false;
    }
}