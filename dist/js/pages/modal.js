/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function show_informe(titulo, mensagem) {
    var title   = '#info_title';
    var message = '#info_msg';
    var button  = '#btn_msg_informe';
    if ( (typeof($(title)) !== "undefined") && (typeof($(message)) !== "undefined") && (typeof($(button)) !== "undefined") ) {
        $(title).html(titulo);
        $(message).html(mensagem);
        $(button).trigger('click');
    } else {
        alert(mensagem);
    }
}

function show_alerta(titulo, mensagem) {
    var title   = '#warning_title';
    var message = '#warning_msg';
    var button  = '#btn_msg_alerta';
    if ( (typeof($(title)) !== "undefined") && (typeof($(message)) !== "undefined") && (typeof($(button)) !== "undefined") ) {
        $(title).html(titulo);
        $(message).html(mensagem);
        $(button).trigger('click');
    } else {
        alert(mensagem);
    }
}

function show_erro(titulo, mensagem) {
    var title   = '#danger_title';
    var message = '#danger_msg';
    var button  = '#btn_msg_erro';
    if ( (typeof($(title)) !== "undefined") && (typeof($(message)) !== "undefined") && (typeof($(button)) !== "undefined") ) {
        $(title).html(titulo);
        $(message).html(mensagem);
        $(button).trigger('click');
    } else {
        alert(mensagem);
    }
}

function show_confirmar(titulo, mensagem) {
    var title   = '#primary_title';
    var message = '#primary_msg';
    var button  = '#btn_msg_primario';
    if ( (typeof($(title)) !== "undefined") && (typeof($(message)) !== "undefined") && (typeof($(button)) !== "undefined") ) {
        $(title).html(titulo);
        $(message).html(mensagem);
        $(button).trigger('click');
    } else {
        alert(mensagem);
    }
}

function show_campos_requeridos(titulo, cadastro, campos_requeridos) {
    var title   = '#warning_title';
    var message = '#warning_msg';
    var button  = '#btn_msg_alerta';
    if ( (typeof($(title)) !== "undefined") && (typeof($(message)) !== "undefined") && (typeof($(button)) !== "undefined") ) {
        $(title).html(titulo);
        $(message).html("<p>O <strong>" + cadastro + "</strong> requer o(s) seguinte(s) dado(s) informado(s):</p>" + campos_requeridos);
        $(button).trigger('click');
    } else {
        alert("O " + cadastro + " requer o(s) seguinte(s) dado(s) informado(s):\n" + campos_requeridos);
    }
}