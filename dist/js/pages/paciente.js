/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function pesquisar_pacientes() {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'empresa': $('#empresaID').val(),
        'usuario': $('#userID').val(),
        'tipo'   : $('#cd_tipo_filtro').val(),
        'filtro' : $('#ds_filtro').val(),
        'action' : 'pesquisar_pacientes',
        'qt_registro' : $('#qtde-registros-pac').val()
    };
    
    // Iniciamos o Ajax 
    $.ajax({
        // Definimos a url
        url : './dist/dao/dao_paciente.php',
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
            Pace.restart();
        },
        // Colocamos o retorno na tela
        success : function(data){
            $('#box-tabela').html(data);
            document.body.style.cursor = "auto";
            if (typeof($('#tb-pacientes')) !== "undefined") {
                configurar_tabela('#tb-pacientes');
            }
        },
        error: function (request, status, error) {
            document.body.style.cursor = "auto";
            $('#box-tabela').html(request.responseText + "<br>" + error + " (" + status + ")");
        }
    });  
    // Finalizamos o Ajax
}

function pesquisar_arquivos() {
    var params = {
        'token'   : $('#tokenID').val(),
        'estacao' : $('#estacaoID').val(),
        'empresa' : $('#empresaID').val(),
        'usuario' : $('#userID').val(),
        'action'  : 'pesquisar_arquivos',
        'paciente': $('#cd_paciente').val()
    };
    
    // Iniciamos o Ajax 
    $.ajax({
        // Definimos a url
        url : './dist/dao/dao_paciente.php',
        // Definimos o tipo de requisição
        type: 'post',
        // Definimos o tipo de retorno
        dataType : 'html',
        // Dolocamos os valores a serem enviados
        data: params,
        // Antes de enviar ele alerta para esperar
        beforeSend : function(){
            document.body.style.cursor = "wait";
            $('#box-tabela_arquivos').html("<i class='fa fa-spin fa-refresh'></i>&nbsp; Executando pesquisa, <strong>aguarde</strong>!");
            Pace.restart();
        },
        // Colocamos o retorno na tela
        success : function(data){
            $('#box-tabela_arquivos').html(data);
            document.body.style.cursor = "auto";
            if (typeof($('#tb-tabela_arquivos')) !== "undefined") {
                configurar_tabela_arquivos('#tb-tabela_arquivos');
            }
        },
        error: function (request, status, error) {
            document.body.style.cursor = "auto";
            $('#box-tabela_arquivos').html(request.responseText + "<br>" + error + " (" + status + ")");
        }
    });  
    // Finalizamos o Ajax
}

function buscar_pacientes(filtro, painel, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'empresa': $('#empresaID').val(),
        'usuario': $('#userID').val(),
        'tipo'   : $('#cd_tipo_filtro').val(),
        'filtro' : filtro,
        'action' : 'buscar_pacientes'
    };
    
    // Iniciamos o Ajax 
    $.ajax({
        // Definimos a url
        url : './dist/dao/dao_paciente.php',
        // Definimos o tipo de requisição
        type: 'post',
        // Definimos o tipo de retorno
        dataType : 'html',
        // Dolocamos os valores a serem enviados
        data: params,
        // Antes de enviar ele alerta para esperar
        beforeSend : function(){
            document.body.style.cursor = "wait";
            $(painel).html("<i class='fa fa-spin fa-refresh'></i>&nbsp; Executando pesquisa, <strong>aguarde</strong>!");
        },
        // Colocamos o retorno na tela
        success : function(data){
            $(painel).html(data);
            document.body.style.cursor = "auto";
            
            if(callback && typeof(callback) === "function") {
                callback(data);
            }
        },
        error: function (request, status, error) {
            document.body.style.cursor = "auto";
            $(painel).html(request.responseText + "<br>" + error + " (" + status + ")");
        }
    });  
    // Finalizamos o Ajax
}

function carregar_registro_paciente(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'empresa': $('#empresaID').val(),
        'usuario': $('#userID').val(),
        'codigo' : codigo,
        'action' : 'carregar_paciente'
    };
    
    try {
        $('#operacao').val("editar");
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_paciente.php',
            // Definimos o tipo de requisição
            type: 'post',
            // Definimos o tipo de retorno
            dataType : 'html',
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
                if (retorno === "OK") { 
                    var file = "logs/json/paciente_" + params.estacao + ".json";
                    $.getJSON(file, function(data){
//                        // Identificação
//                        $('#cd_paciente').val(zero_esquerda(data.registro[0].prontario, 7));
//                        $('#nm_paciente').val(data.registro[0].nome);
//                        $('#dt_nascimento').val(data.registro[0].nascimento);
//                        $('#tp_sexo').val(data.registro[0].sexo);
//                        $('#cd_profissao').val( get_value(data.registro[0].codigo_profissao, 0) );
//                        $('#ds_profissao').val( data.registro[0].profissao );
//                        $('#nr_rg').val(data.registro[0].rg);
//                        $('#ds_orgao_rg').val(data.registro[0].orgao);
//                        $('#dt_emissao_rg').val(data.registro[0].emissao);
//                        $('#nr_cpf').val(data.registro[0].cpf);
//                        $('#nm_acompanhante').val(data.registro[0].acompanhante);
//                        $('#nm_pai').val(data.registro[0].pai);
//                        $('#nm_mae').val(data.registro[0].mae);
//                        
//                        // Endereço
//                        if (typeof($('#end_logradouro')) !== "undefined") {
//                            // Customizado
//                            $('#cd_estado').val( get_value(data.registro[0].estado, 0) );
//                            $('#cd_cidade').val( get_value(data.registro[0].cidade, 0) );
//                            $('#end_logradouro').val(data.registro[0].end_logradouro);
//                            $('#end_bairro').val(data.registro[0].end_bairro);
//                            $('#end_estado').val(data.registro[0].end_estado);
//                            $('#end_cidade').val(data.registro[0].end_cidade);
//                        } else {
//                            // Normalização
//                            if ( get_value(data.registro[0].estado, 0) !== parseInt("0" + $('#cd_estado').val()) ) {
//                                $('#cd_estado').val( get_value(data.registro[0].estado, 0) );
//                            }
//                            if ( get_value(data.registro[0].cidade, 0) !== parseInt("0" + $('#cd_cidade').val()) ) {
//                                listar_cidades_cadastro('cidade_' + get_value(data.registro[0].cidade, 0));
//                            }    
//                        }
//                        $('#tp_endereco').val( get_value(data.registro[0].tipo, 0) );
//                        $('#ds_endereco').val(data.registro[0].endereco);
//                        $('#nr_endereco').val(data.registro[0].numero);
//                        $('#ds_complemento').val(data.registro[0].complemento);
//                        $('#nm_bairro').val(data.registro[0].bairro);
//                        $('#nr_cep').val(data.registro[0].cep);
//                        
//                        // Contatos
//                        $('#nr_telefone').val(data.registro[0].fone);
//                        $('#nr_celular').val(data.registro[0].celular);
//                        $('#ds_contatos').val(data.registro[0].contatos);
//                        $('#ds_email').val(data.registro[0].email);
//                        // Outras informações
//                        $('#cd_convenio').val(data.registro[0].convenio);
//                        $('#nr_matricula').val(data.registro[0].matricula);
//                        $('#nm_indicacao').val(data.registro[0].indicacao);
//                        $('#ds_alergias').val(data.registro[0].alergias);
//                        $('#ds_observacoes').val(data.registro[0].observacoes);
//                        $('#sn_ativo').prop('checked', (parseInt(data.registro[0].ativo) === 1)).iCheck('update');
                        
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

function salvar_registro_paciente(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'empresa': $('#empresaID').val(),
        'usuario': $('#userID').val(),
        'prontuario'  : codigo,
        'action'      : 'salvar_paciente',
        // Identificação
        'cd_paciente' : $('#cd_paciente').val(),
        'nm_paciente' : $('#nm_paciente').val(),
	'dt_nascimento' : $('#dt_nascimento').val(),
	'tp_sexo'       : $('#tp_sexo').val(),
	'cd_profissao' : $('#cd_profissao').val(),
        'ds_profissao' : $('#ds_profissao').val(),
	'nr_rg'        : $('#nr_rg').val(),
	'ds_orgao_rg'   : $('#ds_orgao_rg').val(),
	'dt_emissao_rg' : $('#dt_emissao_rg').val(),
        'nr_cpf'        : $('#nr_cpf').val(),
	'nm_acompanhante' : $('#nm_acompanhante').val(),
	'nm_pai' : $('#nm_pai').val(),
	'nm_mae' : $('#nm_mae').val(),
        // Endereço (Customzado)
	'end_logradouro' : $('#end_logradouro').val(),
	'end_bairro'     : $('#end_bairro').val(),
	'end_cidade'     : $('#end_cidade').val(),
	'end_estado'     : $('#end_estado').val(),
        // Endereço
	'cd_estado'   : $('#cd_estado').val(),
	'cd_cidade'   : $('#cd_cidade').val(),
	'tp_endereco' : $('#tp_endereco').val(),
	'ds_endereco' : $('#ds_endereco').val(),
	'nr_endereco' : $('#nr_endereco').val(),
	'ds_complemento' : $('#ds_complemento').val(),
	'nm_bairro'      : $('#nm_bairro').val(),
	'nr_cep'         : $('#nr_cep').val(),
        // Contatos
	'nr_telefone' : $('#nr_telefone').val(),
	'nr_celular'  : $('#nr_celular').val(),
        'ds_contatos' : $('#ds_contatos').val(),
	'ds_email'    : $('#ds_email').val(),
        // Outras informações
	'cd_convenio'    : $('#cd_convenio').val(),
	'nr_matricula'   : $('#nr_matricula').val(),
	'nm_indicacao'   : $('#nm_indicacao').val(),
	'ds_alergias'    : $('#ds_alergias').val(),
	'ds_observacoes' : $('#ds_observacoes').val(),
        'sn_ativo' : '0'
    };
    
    if ( $('#sn_ativo').is(":checked") ) params.sn_ativo = $('#sn_ativo').val();
    
    try {
        var inserir = ($('#operacao').val() === "inserir");
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_paciente.php',
            // Definimos o tipo de requisição
            type: 'post',
            // Definimos o tipo de retorno
            dataType : 'html',
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
                if (retorno === "OK") {
                    var file = "logs/json/paciente_" + params.estacao + ".json";
                    $.getJSON(file, function(data){
//                        if (inserir) {
//                            $('#cd_paciente').val(data.registro[0].prontuario);
//                            var newRow = $(data.registro[0].tr_table);
//                            $("#tb-pacientes").append(newRow);
//                        } else {
//                            var tr_table = document.getElementById("tr-linha_" + data.registro[0].prontuario); 
//                            var colunas  = tr_table.getElementsByTagName('td');
//                            
//                            colunas[1].firstChild.nodeValue = params.nm_paciente;
//                            colunas[2].firstChild.nodeValue = data.registro[0].fone;
//                            colunas[3].firstChild.nodeValue = data.registro[0].idade;
//                            colunas[4].firstChild.nodeValue = data.registro[0].rg;
//                            colunas[5].firstChild.nodeValue = data.registro[0].cpf;
//                        }

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

function excluir_paciente (codigo, descricao, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'empresa': $('#empresaID').val(),
        'usuario': $('#userID').val(),
        'paciente' : codigo,
        'action'   : 'excluir_paciente'
    };
    
    try {
        show_confirmar("Excluir", "Você confirma a exclusão do paciente <strong>'" + descricao + "'</strong>?");
        
        var botao = document.getElementById("primary_confirm");
        botao.onclick = function() {
            $('#btn_msg_primario').trigger('click');
            
            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : './dist/dao/dao_paciente.php',
                // Definimos o tipo de requisição
                type: 'post',
                // Definimos o tipo de retorno
                dataType : 'html',
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