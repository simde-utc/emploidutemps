var HOUR_MIN = 7;
var HOUR_MAX = 21;
var get = '';
var phpGet = false;
var card = '';
var columnPerDay = 1;
var mode = 'afficher';
var compare = 0;
var idUV = '';
var begin = 0;
var login = '';
var uv = '';
var search = '';
var toSearch = '';
var click = false;
var headers = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi",  "Samedi", 'Dimanche'];
var date = new Date();
var focusedDay = (date.getDay() + 6) % 7;
var planifierGet = '';
var week = '';

function newRequest(get, tab) {
  loading();
  window.get = get;

  $('#zoneGrey').removeClass('focused');
  $('#zoneFocus').removeClass('focused');
  $('#zonePopup').removeClass('focused');

  $('#bar').load('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/getTabs.php?mode=' + window.mode + get + tab, function() {
    $('#sTitle').load('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/getTitle.php?&mode=' + window.mode + get, function () {
      $.get('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/getTasks.php?mode=' + window.mode + get, function (tasks) {
        schedule(JSON.parse(tasks));
        setSkeduler();
      });
    });
  });
}

function selectMode(get, mode) {
  if (mode !== '')
    window.mode = mode;

   if (window.mode == 'afficher') {
    window.columnPerDay = 1;
    window.compare = 0;
    window.idUV = '';

    if (window.phpGet)
      window.location.href = 'https://' + window.location.hostname + '/emploidutemps/';

    newRequest('&login=' + window.login + '&uv=' + window.uv + get, '');
  }
  else if (window.mode == 'comparer') {
    window.columnPerDay = 2;
    window.compare = 1;
    window.idUV = '';

    newRequest('&login=' + window.login + '&uv=' + window.uv + get, '');

    setTimeout(function () {
      if ($('#menu button').length === 1)
        searchTab();
      else if (window.login == '')
        $('#menu button')[1].click();
    }, 500);
  }
  else if (window.mode == 'modifier') {
    window.columnPerDay = 2;
    window.compare = 0;

    newRequest(get, '');
  }
  else if (window.mode == 'organiser') {
    window.columnPerDay = 1;
    window.compare = 1;
    window.idUV = '';

    newRequest(get, '');

    setTimeout(function () {
      if ($('#menu button').length === 1)
        searchTab();
    }, 500);
  }
  else if (window.mode == 'planifier') {
    window.columnPerDay = 1;
    window.compare = 0;
    window.idUV = '';

    newRequest(window.planifierGet + (window.week === '' ? '' : '&week=' + window.week), '');
  }
  else
    selectMode('', 'afficher');
}

function planifier(get, week) {
  if (get == '')
    window.week = week;
  else
    window.planifierGet = '&' + get;

  selectMode('', 'planifier');
}

function loading() {
  var img = document.createElement('img');
  img.id = 'loading';
  img.src = 'https://' + window.location.hostname + '/emploidutemps' + '/ressources/img/loading.gif';

  var src = document.getElementById('skeduler-container');
  src.appendChild(img);
}

function endLoading() {
  document.getElementById('loading').remove();
}

function addTab() {
  newRequest(window.get, '&addTab=' + document.getElementById('addTabText').value);
}

function delTab(toDel) {
  // Redirection vers son edt si on supprime un onglet alors qu'on est dessus
  if (window.login == toDel)
    window.login = '';
  else if (window.uv == toDel)
    window.uv = '';

  newRequest(window.get.replace('&login=' + toDel, '').replace('&uv=' + toDel, '').replace('&addTab=' + toDel, ''), '&delTab=' + toDel);
}

function addEtuActive(login) {
  newRequest(window.get, '&addEtuActive=' + login);
}

function delEtuActive(login) {
  newRequest(window.get, '&delEtuActive=' + login);
}

function seeOthers(uv, type, idUV) {
  window.uv = '';
  window.login = '';
  window.idUV = idUV;

  selectMode('&uv=' + uv + '&type=' + type, 'modifier');
}

function changeColor(idUV, color) {
  setTimeout(function () { // Attendre la fin de l'animation pour actualisr la couleur ^^'
    $.get('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/setColor.php?idUV=' + idUV + '&color=' + color.substr(1), function () {
      newRequest(window.get, '');
    });
  }, 200);
}

function afficher(idUV) {
  selectMode('&login=' + window.login, 'afficher');
  $.get('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/getTasks.php?mode=afficher&login=' + window.login, function (tasks) {
    tasks = JSON.parse(tasks);
    tasks.forEach(function(task) {
      if (task.idUV == idUV)
        setTimeout(function() {
          $('#' + task.id).click();
        }, 500);
    });
  });
}

function edtUV(uv) {
  window.uv = uv;
  window.idUV = '';
  window.login = '';

  if (window.mode != 'comparer') {
    window.mode = 'afficher';
    window.columnPerDay = 1;
  }

  setTimeout(function () {
    unFocus();
    popupClose(); }, 100);
    newRequest('&uv=' + uv, '&addTab=' + uv);
}

function edtEtu(login) {
  window.uv = '';
  window.idUV = '';
  window.login = login;
  window.planifierGet = '';

  unFocus();
  popupClose();
  newRequest('&week=' + window.week + '&login=' + login, '&addTab=' + login);
}

function compareEtu(login) {
  window.uv = '';
  window.login = login;
  newRequest('&login=' + login, '');
}

function compareUV(uv) {
  window.uv = uv;
  window.login = '';
  newRequest('&uv=' + uv, '');
}

function seeOriginal() {
  window.idUV = '';
  window.columnPerDay = 1;
  window.compare = 0;
  newRequest('&original=1', '');
}

function seeChangement() {
  window.idUV = '';
  window.columnPerDay = 2;
  window.compare = 0;
  newRequest('&changement=1', '');
}

function seeExchanges(type, stat) {
  window.idUV = '';
  window.columnPerDay = 2;
  window.compare = 0;
  newRequest('&' + type + '=' + stat, '');
}

function seeRecues() {
  window.idUV = '';
  window.columnPerDay = 2;
  window.compare = 0;
  newRequest('&recu=1', '');
}

function seeEnvoies() {
  window.idUV = '';
  window.columnPerDay = 2;
  window.compare = 0;
  newRequest('&envoi=1', '');
}

function uvWeb(uv) {
  window.click = true;
  window.open('https://assos.utc.fr/uvweb/uv/' + uv);
}

function uvMoodle(uv) {
  window.click = true;
  window.open('http://moodle.utc.fr/course/search.php?search=' + uv);
}

function popup(info) {
  window.click = true;
  $('#popup').html(info);
  $('#popup').css('visibility', 'visible');
  $('#popup').css('opacity', '1');
  $('#zonePopup').addClass('focused');

  if ($('.focusedInput').length != 0)
    $('.focusedInput')[0].focus();

  if ($(".submitedInput").length != 0 && $(".submitedButton").length != 0)
    $(".submitedInput").last().keyup(function (event) {
      code = event.keyCode || event.which;
      if(code == 13)
        $(".submitedButton")[0].click();
    });
}

function popupInfo(info) {
  newRequest(window.get, '');
  window.card = '';
  popup(info);
}

function popupClose() {
  window.click = false;
  window.toSearch = '';

  $('#popup').css('visibility', 'hidden');
  $('#popup').css('opacity', '0');

  $('#zonePopup').removeClass('focused');
}

function unFocus() {
  window.click = false;

  $('#zoneGrey').removeClass('focused');
  $('#zoneFocus').removeClass('focused');

  $('#' + window.card.id).click();
}

function seeEtu(idUV) {
  window.click = true;
  $.get('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/getEtuList.php?idUV=' + idUV, function (etus) {
    popup(etus);
  });
}

function searchTab() {
  window.tab = 0;
  window.search = '';

  if (!$('#addTab').hasClass("blocked")) {
    popup("<div id='popupHead'>\
      <div style='margin-bottom: 2px;'>Chercher un étudiant ou une UV pour l'ajouter</div>\
      <input type='text' autofocus='autofocus'' onInput='checkEtuAndUVList(this.value);' value='" + window.toSearch + "' id='addTabText' />\
      <button onClick='printEtuAndUVList();'>Chercher</button>\
    </div>\
    <div id='searchResult'></div>");

    $("#addTabText").keyup(function (event) {
      if(event.keyCode == 13){
        printEtuAndUVList();
      }
    });

    setTimeout(function () { // La fonction ne marche pas sans Timeout..
      $("#addTabText").focus();
    }, 100);
  }
}

function checkEtuAndUVList(search) {
  var text = search.replace(/\s+/g, ' ').replace(/^\s+/g, '').replace(/(\s.+)\s$/g, '$1');
  $('#addTabText').val(text);

  window.toSearch = text;
}

function printEtuAndUVList(begin) {
  if (window.toSearch != window.search) {
    loading();

    searchTab();
    window.search = window.toSearch;

    checkEtuAndUVList(window.search);
    $('#popup').scrollTop(0);

    var search = window.toSearch;

    if (begin == undefined)
      begin = 0;

    $('#searchResult').load('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/getEtuAndUVList.php?search=' + search.replace(/^\s+|\s+$/g, '').replace(/_/g, '').replace(/\s/, '%\\_%\\') + '&begin=' + begin, function () {
      endLoading();
    });
  }
}

function askForExchange(idUV, forIdUV) {
  window.click = true;
  $.get('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/exchange.php?ask=1&idUV=' + idUV + '&for=' + forIdUV, function (info) {
    popup(info);
  });
}

function addExchange(idUV, forIdUV, note) {
  $.post('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/exchange.php?add=1&idUV=' + idUV + '&for=' + forIdUV, {note: note}, function (info) {
    popupInfo(info);
  });
}

function delExchange(idExchange) {
  window.click = true;
  $.get('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/exchange.php?del=1&idExchange=' + idExchange, function (info) {
    popupInfo(info);
  });
}

function cancelExchange(idExchange, note) {
  window.click = true;
  if (note === undefined) {
    popup("<div id='popupHead'>Annuler un échange</div>\
    <div class='parameters'>En annulant un échange effectué, un mail de demande d\'annulation sera envoyé à la personne ayant échangé ce créneau. Tant que celle-ci n'a pas accepté l'annulation, les emplois du temps reste inchangés<br /> \
      Lorsque l'annulation sera effective, des demandes d'échange pour le créneau pourront être reçues et envoyées<br />\
      <textarea maxlength=\"500\" cols=\"30\" rows=\"5\" id=\"noteExchange\" placeholder=\"Explique pourquoi tu souhaites annuler l'échange\" contenteditable></textarea><br />\
      <button style='background-color: #FF0000' onClick=\"cancelExchange(" + idExchange + ", $('#noteExchange').val());\">Demander l'annulation</button>\
    </div>");
  }
  else {
    $.post('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/exchange.php?cancel=1&idExchange=' + idExchange, {note: note}, function (info) {
      popupInfo(info);
    });
  }
}

function infosExchange(idExchange) {
  window.click = true;
  $.get('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/exchange.php?infos=1&idExchange=' + idExchange, function (info) {
    popup(info);
  });
}

function acceptExchange(idExchange, confirm) {
  if (confirm === undefined) {
    popup("<div id='popupHead'>Accepter un échange</div>\
    <div class='parameters'>En acceptant l'échange, un mail de confirmation sera envoyé pour signaler que l'échange a bien été pris en compte. Le nom et le prénom ainsi que l'adresse mail de la personne avec qui tu as échangé te sera donné pour que vous puissiez par la suite contacter les responsables TDs/TPs pour échanger<br /><br />\
      Si l'échange n'est pas effectué, il faudra demander l'annulation de l'échange en cliquant sur le créneau que tu viens d'échanger (dans le menu 'Modifier') pour que vos emplois du temps soient réinitialisés comme avant l'échange<br />\
      <button style='background-color: #00FF00' onClick='acceptExchange(" + idExchange + ", 1);'>Accepter l'échange</button>\
    </div>");
  }
  else {
    $.get('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/exchange.php?accept=1&idExchange=' + idExchange, function (info) {
      popupInfo(info);
    });
  }
}

function refuseExchange(idExchange) {
  $.get('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/exchange.php?refuse=1&idExchange=' + idExchange, function (info) {
    popupInfo(info);
  });
}

function parameters(param) {
  var get = '';

  if (param != undefined)
    get = '?param=' + param;

  $.get('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/parameters.php' + get, function (info) {
    popup(info);

    if (param === 'pdf')
        $('#pdfTitle').val($('#sTitle').text());
  });
}

function createTask(day, begin, duration, description) {
  popup("<div id='popupHead'>Créer un évènement</div>\
  <div class='parameters'>Jour: " + window.week + " " + day + "<br />Début: " + toTimeString(begin) + "<br />Fin: " + toTimeString(begin + duration) + "<br />Description: " + description + "<br />\
    <button style='background-color: #00FF00' onClick='createTask(" +  + ", 1);'>Créer</button>\
  </div>");
}

function getICal() {
  var get = '?begin=' + ($('#beginICS').val() === '' ? $('#beginICS').attr('placeholder') : $('#beginICS').val()) + '&end=' + ($('#endICS').val() === '' ? $('#endICS').attr('placeholder') : $('#endICS').val());

  if ($('#alarmICS').val() !== '')
    get += '&alarm=' + $('#alarmICS').val();

  $.get('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/getICal.php' + get, function (file) {
    window.location.href = 'https://' + window.location.hostname + file;
  });
}

function getPDF() {
  var headers = $('.skeduler-headers div');
  var days = $('.skeduler-main-body .days');
  var length = window.headers.length;
  var displays = [];
  var hidden = 0;
  doc = new jsPDF('l', 'mm', [297, 210]);

  doc.text($('#pdfTitle').val(), 149, 8, null, null, 'center');
  if ($('#pdfCheckTabs').prop('checked')) {
    html2canvas($('#menu'), { onrendered: function(canvas) { doc.addImage(canvas.toDataURL('image/png', 1.0), 'PNG', 10, 15); }});
  }

  for (var i = 0; i < length; i++) {
    displays[i] = $(headers[i]).css('display');

    if ($('#pdfCheck' + i).prop('checked')) {
      $(days[i]).css('display', 'block');
      $(headers[i]).css('display', 'block');
    }
    else {
      $(days[i]).css('display', 'none');
      $(headers[i]).css('display', 'none');
      hidden += 1;
    }
  }

  var calendar = $('#skeduler-container');
  var width = calendar.css('width');
  calendar.css('width', (1036 - (hidden * 32)) + 'px');

  html2canvas(calendar[0], { onrendered: function(canvas) {
    doc.addImage(canvas.toDataURL('image/png', 1.0), 'PNG', 10 + (hidden * 18), 25 + ($('#pdfCheckTabs').prop('checked') ? 10 : 0));

    for (var i = 0; i < length; i++) {
      $(days[i]).css('display', displays[i]);
      $(headers[i]).css('display', displays[i]);
    }

    doc.save($('#pdfName').val() + '.pdf');
  }});
}

function setSkeduler(day) {
  var headers = $('.skeduler-headers div');
  var days = $('.skeduler-main-body .days');
  var width = $(window).width();

  var numbers = [200.4, 339.2, 478, 616.8, 755.6, 894.4, 1033.2];
  var length = window.headers.length;
  var number = length;

  var focusedDay = day;

  if (focusedDay === undefined || focusedDay < 0 || focusedDay >= length)
    focusedDay = window.focusedDay;

  var indexs = [focusedDay];

  for (var i = 0; i < length; i++) {
    if (numbers[i] > width) {
      number = i;
      break;
    }
  }

  $('#skeduler-container').width(numbers[number - 1] || 0);
  //$('#toPDF').css('display', 'none');
  $('#otherDay').css('display', 'block').css('padding-right', numbers[number - 1] - 60);

  if (number >= length) {
    //$('#toPDF').css('display', 'block').css('padding-right', numbers[number - 1] - 60);
    $('#otherDay').css('display', 'none');

    for (var i = 0; i < length; i++)
      indexs.push(i);
  }
  else if (number === 2) {
    if (focusedDay + 1 === length)
      indexs.push(focusedDay - 1);
    else
      indexs.push(focusedDay + 1);
  }
  else if (number === 3) {
    if (focusedDay + 1 === length) {
      indexs.push(focusedDay - 1);
      indexs.push(focusedDay - 2);
    }
    else {
      indexs.push(focusedDay + 1);
      if (focusedDay === 0)
        indexs.push(focusedDay + 2);
      else
        indexs.push(focusedDay - 1);
    }
  }
  else if (number === 4) {
    if (focusedDay + 1 === length) {
      indexs.push(focusedDay - 1);
      indexs.push(focusedDay - 2);
      indexs.push(focusedDay - 3);
    }
    else {
      indexs.push(focusedDay + 1);
      if (focusedDay === 0) {
        indexs.push(focusedDay + 2);
        indexs.push(focusedDay + 3);
      }
      else {
        indexs.push(focusedDay - 1);
        if (focusedDay + 2 === length)
          indexs.push(focusedDay - 2);
        else
          indexs.push(focusedDay + 2);
      }
    }
  }
  else if (number === 5) {
    if (focusedDay + 1 === length) {
      indexs.push(focusedDay - 1);
      indexs.push(focusedDay - 2);
      indexs.push(focusedDay - 3);
      indexs.push(focusedDay - 4);
    }
    else {
      indexs.push(focusedDay + 1);
      if (focusedDay === 0) {
        indexs.push(focusedDay + 2);
        indexs.push(focusedDay + 3);
        indexs.push(focusedDay + 4);
      }
      else {
        indexs.push(focusedDay - 1);
        if (focusedDay + 2 === length) {
          indexs.push(focusedDay - 2);
          indexs.push(focusedDay - 3);
        }
        else {
          indexs.push(focusedDay + 2);
          if (focusedDay - 1 === 0)
            indexs.push(focusedDay + 3);
          else
            indexs.push(focusedDay - 2);
        }
      }
    }
  }
  else if (number === 6) {
    if (focusedDay + 1 === length) {
      indexs.push(focusedDay - 1);
      indexs.push(focusedDay - 2);
      indexs.push(focusedDay - 3);
      indexs.push(focusedDay - 4);
      indexs.push(focusedDay - 5);
    }
    else {
      indexs.push(focusedDay + 1);
      if (focusedDay === 0) {
        indexs.push(focusedDay + 2);
        indexs.push(focusedDay + 3);
        indexs.push(focusedDay + 4);
        indexs.push(focusedDay + 5);
      }
      else {
        indexs.push(focusedDay - 1);
        if (focusedDay + 2 === length) {
          indexs.push(focusedDay - 2);
          indexs.push(focusedDay - 3);
          indexs.push(focusedDay - 4);
        }
        else {
          indexs.push(focusedDay + 2);
          if (focusedDay - 1 === 0) {
            indexs.push(focusedDay + 3);
            indexs.push(focusedDay + 4);
          }
          else {
            indexs.push(focusedDay - 2);
            if (focusedDay + 3 === length)
              indexs.push(focusedDay - 3);
            else {
              indexs.push(focusedDay + 3);
            }
          }
        }
      }
    }
  }

  var diff = false;
  headers.each(function(index) {
    if (index >= length)
      return

    if (indexs.indexOf(index) === -1) {
      if ($(this).css('display') === 'block')
        diff = true;

      $(this).css('display', 'none');
      $(days[index * window.columnPerDay]).css('display', 'none');
      if (window.columnPerDay === 2)
        $(days[index * window.columnPerDay + 1]).css('display', 'none');
    }
    else {
      if ($(this).css('display') === 'none')
        diff = true;

      $(this).css('display', 'block');
      $(days[index * window.columnPerDay]).css('display', 'block');
      if (window.columnPerDay === 2)
        $(days[index * window.columnPerDay + 1]).css('display', 'block');
    }
  });

  // On ne change pas de jour focus si l'affichage ne change pas, par contre on le réduit au min pour appliquer un changement (éviter d'appuyer 5 fois sur le bouton pour rien par ex)
  if (diff)
    window.focusedDay = focusedDay;
  else {
    if (window.focusedDay - focusedDay === 1) {
      for (var i = focusedDay - 1; i > 0; i--) {
        setSkeduler(i);
        if (focusedDay - window.focusedDay != 1)
          break;
      }
    }
    else if (focusedDay - window.focusedDay === 1) {
      for (var i = focusedDay + 1; i < length; i++) {
        setSkeduler(i);
        if (window.focusedDay - focusedDay != 1)
          break;
      }
    }
  }
}
