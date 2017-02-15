var get = '';
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

function newRequest(get, tab) {
  loading();
  window.get = get;

  $('#zoneGrey').removeClass('focused');
  $('#zoneFocus').removeClass('focused');
  $('#zonePopup').removeClass('focused');

  console.log('https://' + window.location.hostname + '/ressources/php/getTabs.php?mode=' + window.mode + get + tab);

  $('#bar').load('https://' + window.location.hostname + '/ressources/php/getTabs.php?mode=' + window.mode + get + tab, function() {
    $('#sTitle').load('https://' + window.location.hostname + '/ressources/php/getTitle.php?&mode=' + window.mode + get, function () {
        $.get('https://' + window.location.hostname + '/ressources/php/getTasks.php?mode=' + window.mode + get, function (tasks) {
        schedule(JSON.parse(tasks));
        setSkeduler();
      });
    });
  });
}

function setSkeduler(day) {
  var headers = $('.skeduler-headers div');
  var days = $('.skeduler-main-body .days');
  var width = $(window).width();

  var numbers = [200.4, 339.2, 478, 616.8, 755.6, 894.4, 1033.2];
  var number = headers.length;

  var focusedDay = day;

  if (focusedDay === undefined || focusedDay < 0 || focusedDay >= headers.length)
    focusedDay = window.focusedDay;

  var indexs = [focusedDay];

  for (var i = 0; i < headers.length; i++) {
    if (numbers[i] > width) {
      number = i;
      break;
    }
  }

  $('#skeduler-container').width(numbers[number - 1] || 0);
  //$('#toPDF').css('display', 'none');
  $('#otherDay').css('display', 'block').css('padding-right', numbers[number - 1] - 60);

  if (number >= headers.length) {
    //$('#toPDF').css('display', 'block').css('padding-right', numbers[number - 1] - 60);
    $('#otherDay').css('display', 'none');

    for (var i = 0; i < headers.length; i++)
      indexs.push(i);
  }
  else if (number === 2) {
    if (focusedDay + 1 === headers.length)
      indexs.push(focusedDay - 1);
    else
      indexs.push(focusedDay + 1);
  }
  else if (number === 3) {
    if (focusedDay + 1 === headers.length) {
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
    if (focusedDay + 1 === headers.length) {
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
        if (focusedDay + 2 === headers.length)
          indexs.push(focusedDay - 2);
        else
          indexs.push(focusedDay + 2);
      }
    }
  }
  else if (number === 5) {
    if (focusedDay + 1 === headers.length) {
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
        if (focusedDay + 2 === headers.length) {
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
    if (focusedDay + 1 === headers.length) {
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
        if (focusedDay + 2 === headers.length) {
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
            if (focusedDay + 3 === headers.length)
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
      for (var i = focusedDay + 1; i < headers.length; i++) {
        setSkeduler(i);
        if (window.focusedDay - focusedDay != 1)
          break;
      }
    }
  }
}

function selectMode(get, mode) {
  if (mode !== '')
    window.mode = mode;

  if (window.mode == 'afficher') {
    window.columnPerDay = 1;
    window.compare = 0;
    window.idUV = '';

    newRequest('&login=' + window.login + '&uv=' + window.uv, '');
  }
  else if (window.mode == 'comparer') {
    window.columnPerDay = 2;
    window.compare = 1;
    window.idUV = '';

    newRequest('&login=' + window.login + '&uv=' + window.uv, '');

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
  }
  else
    selectMode('', 'afficher');
  /*
  else if (mode == 'planifier') {
    window.columnPerDay = 2;
    window.compare = 0;
  }*/
}

function loading() {
  var img = document.createElement('img');
  img.id = 'loading';
  img.src = 'https://' + window.location.hostname + '/ressources/img/loading.gif';

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
    $.get('https://' + window.location.hostname + '/ressources/php/setColor.php?idUV=' + idUV + '&color=' + color.substr(1), function () {
      newRequest(window.get, '');
    });
  }, 200);
}

function edtUV(uv) {
  window.uv = uv;
  window.idUV = '';
  window.login = '';

  if (window.mode != 'comparer')
    window.mode = 'afficher';

  newRequest('&uv=' + uv, '&addTab=' + uv);
}

function edtEtu(login) {
  window.uv = '';
  window.login = login;

  unFocus();
  popupClose();
  newRequest('&login=' + login, '&addTab=' + login);
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
  console.log(4);
}

function popupClose() {
  window.click = false;
  $('#popup').css('visibility', 'hidden');
  $('#popup').css('opacity', '0');

  $('#zonePopup').removeClass('focused');

  console.log(5);
}

function unFocus() {
  window.click = false;

  $('#zoneGrey').removeClass('focused');
  $('#zoneFocus').removeClass('focused');
  console.log(6);

  $('#' + window.card.id).click();
}

function seeEtu(idUV) {
  window.click = true;
  $.get('https://' + window.location.hostname + '/ressources/php/getEtuList.php?idUV=' + idUV, function (etus) {
    popup(etus);
  });
}

function searchTab() {
  window.tab = 0;

  if (!$('#addTab').hasClass("blocked")) {
    popup("<div id='popupHead'>\
      <div style='margin-bottom: 2px;'>Ajouter un étudiant ou une UV</div>\
      <input type='text' autofocus='autofocus'' onInput='checkEtuAndUVList(this.value);' id='addTabText' />\
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

function printEtuAndUVList() {
  if (window.toSearch != window.search) {
    loading();
    window.search = window.toSearch;

    var search = window.toSearch;
    console.log(window.toSearch + ' ' + window.search);

    $('#searchResult').load('https://' + window.location.hostname + '/ressources/php/getEtuAndUVList.php?search=' + search.replace(/^\s+|\s+$/g, '').replace(/_/g, '').replace(/\s/, '%\\_%\\'), function () {
      endLoading();
    });
  }
}

function askForExchange(idUV, forIdUV) {
  window.click = true;
  $.get('https://' + window.location.hostname + '/ressources/php/exchange.php?ask=1&idUV=' + idUV + '&for=' + forIdUV, function (info) {
    popup(info);
  });
}

function addExchange(idUV, forIdUV, note) {
  $.post('https://' + window.location.hostname + '/ressources/php/exchange.php?add=1&idUV=' + idUV + '&for=' + forIdUV, {note: note}, function (info) {
    newRequest(window.get, '');
    popup(info);
  });
}

function delExchange(idExchange) {
  window.click = true;
  $.get('https://' + window.location.hostname + '/ressources/php/exchange.php?del=1&idExchange=' + idExchange, function (info) {
    newRequest(window.get, '');
    popup(info);
  });
}

function infosExchange(idExchange) {
  window.click = true;
  $.get('https://' + window.location.hostname + '/ressources/php/exchange.php?infos=1&idExchange=' + idExchange, function (info) {
    popup(info);
  });
}

function acceptExchange(idExchange) {
  $.get('https://' + window.location.hostname + '/ressources/php/exchange.php?accept=1&idExchange=' + idExchange, function (info) {
    newRequest(window.get, '');
    popup(info);
  });
}

function refuseExchange(idExchange) {
  $.get('https://' + window.location.hostname + '/ressources/php/exchange.php?refuse=1&idExchange=' + idExchange, function (info) {
    newRequest(window.get, '');
    popup(info);
  });
}

function desinscription() {
  $.get('https://' + window.location.hostname + '/ressources/php/desinscription.php', function (info) {
    popup(info);
  });
}

function reinscription() {
  $.get('https://' + window.location.hostname + '/ressources/php/reinscription.php', function (info) {
    popup(info);
  });
}
