var HOUR_MIN = 7;
var HOUR_MAX = 21;
var search = '';
var toSearch = '';
var headers = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi",  "Samedi", 'Dimanche'];
var date = new Date();
var focusedDay = (date.getDay() + 6) % 7;

function selectMode(get, mode) {
  window.mode = mode;

  if (mode == 'afficher') {
    window.columnPerDay = 1;
    window.compare = 0;
    window.idUV = '';

    if (window.phpGet)
      window.location.href = 'https://' + window.location.hostname + '/emploidutemps/';

    newRequest('&login=' + window.login + '&uv=' + window.uv + get, '');
  }
  else if (mode == 'comparer') {
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
  else if (mode == 'modifier') {
    window.columnPerDay = 2;
    window.compare = 0;

    newRequest(get, '');
  }
  else if (mode == 'organiser') {
    window.columnPerDay = 1;
    window.compare = 1;
    window.idUV = '';

    newRequest(get + (window.week === '' ? '' : '&week=' + window.week), '');

    setTimeout(function () {
      if ($('#menu button').length === 1)
        searchTab();
    }, 500);
  }
  else if (mode == 'planifier') {
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

  selectMode('', window.mode);
}

function changeMode(mode, weekForOrganiser) {
  window.task = null;

  if (mode == 'organiser')
    window.week = weekForOrganiser;

  selectMode('', mode);
}

function deconnexion() {
  popup('<iframe id="Example" \
  name="Example2" \
  title="Example2" \
  frameborder="0" \
  scrolling="no" \
  height="100%" \
  width="100%" \
  src="https://assos.utc.fr/emploidutemps/deconnexion.php"> \
</iframe>');

  $('#popup').css('height', '100%').css('overflow', 'hidden');

  setTimeout(function() {
    window.location.reload();
  }, 3000);
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


function popupInfo(info) {
  newRequest(window.get, '');
  window.task = null;
  popup(info);
}

function popupClose() {
  window.click = false;
  window.toSearch = '';

  $('#popup').css('visibility', 'hidden');
  $('#popup').css('opacity', '0');

  $('#zonePopup').removeClass('focused');
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

function parameters(param) {
  var get = '?mode=' + window.mode + '&login=' + window.login + '&uv=' + window.uv;

  if (param != undefined)
    get += '&param=' + param;

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

function getImg() {
  var headers = $('.skeduler-headers div');
  var days = $('.skeduler-main-body .days');
  var length = window.headers.length;
  var displays = [];
  var hidden = 0;
  var type = $('#imgType').val();

  for (var i = 0; i < length; i++) {
    displays[i] = $(headers[i]).css('display');

    if ($('#imgCheck' + i).prop('checked')) {
      $(headers[i]).css('display', 'block');
      $(days[i * window.columnPerDay]).css('display', 'block');

      if (window.columnPerDay == 2)
        $(days[(i * window.columnPerDay) + 1]).css('display', 'block');
    }
    else {
      $(headers[i]).css('display', 'none');
      $(days[i * window.columnPerDay]).css('display', 'none');

      if (window.columnPerDay == 2)
        $(days[(i * window.columnPerDay) + 1]).css('display', 'none');
      hidden += 1;
    }
  }

  var calendar = $('#skeduler-container');

  var width = calendar.css('width');
  calendar.css('width', (1036 - (hidden * 139)) + 'px');

  html2canvas(calendar[0], { onrendered: function(canvas) {
    for (var i = 0; i < length; i++) {
      $(headers[i]).css('display', displays[i]);
      $(days[i * window.columnPerDay]).css('display', displays[i]);

      if (window.columnPerDay == 2)
        $(days[(i * window.columnPerDay) + 1]).css('display', displays[i]);
    }

    setSkeduler(window.focusedDay);
    $('#generatedImg').html('<img src="' + canvas.toDataURL('image/' + type, 1.0) + '">');
  }});
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
      $(headers[i]).css('display', 'block');
      $(days[i * window.columnPerDay]).css('display', 'block');

      if (window.columnPerDay == 2)
        $(days[(i * window.columnPerDay) + 1]).css('display', 'block');
    }
    else {
      $(headers[i]).css('display', 'none');
      $(days[i * window.columnPerDay]).css('display', 'none');

      if (window.columnPerDay == 2)
        $(days[(i * window.columnPerDay) + 1]).css('display', 'none');
      hidden += 1;
    }
  }

  var calendar = $('#skeduler-container');
  var width = calendar.css('width');
  calendar.css('width', 1036 + 'px');

  html2canvas(calendar[0], { onrendered: function(canvas) {
    doc.addImage(canvas.toDataURL('image/png', 1.0), 'PNG', 10 + (hidden * 18), 25 + ($('#pdfCheckTabs').prop('checked') ? 10 : 0));

    for (var i = 0; i < length; i++) {
      $(headers[i]).css('display', displays[i]);
      $(days[i * window.columnPerDay]).css('display', displays[i]);

      if (window.columnPerDay == 2)
        $(days[(i * window.columnPerDay) + 1]).css('display', displays[i]);
    }

    setSkeduler(window.focusedDay);
    doc.save($('#pdfName').val() + '.pdf');
  }});
}
