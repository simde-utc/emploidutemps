var HOUR_MIN = 7;
var HOUR_MAX = 21;
var headers = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi",  "Samedi", 'Dimanche'];
var date = new Date();
var focusedDay = (date.getDay() + 6) % 7;

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

function changeColor(idUV, color) {
  setTimeout(function () { // Attendre la fin de l'animation pour actualisr la couleur ^^'
    $.get('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/setColor.php?idUV=' + idUV + '&color=' + color.substr(1), function () {
      newRequest(window.get, '');
    });
  }, 200);
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


function parameters(param) {
  var get = '?mode=' + window.get.mode + (window.get.login == undefined ? '' : '&login=' + window.get.login) + (window.get.uv == undefined ? '' : '&uv=' + window.get.uv);

  if (param != undefined)
    get += '&param=' + param;

  $.get('https://' + window.location.hostname + '/emploidutemps' + '/ressources/php/parameters.php' + get, function (info) {
    popup(info);

    if (param === 'pdf')
        $('#pdfTitle').val($('#title').text());
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
  var headers = $('.calendar-headers div');
  var days = $('.calendar-main-body .days');
  var sides = days.length / (headers.length - 1);
  var length = window.headers.length;
  var displays = [];
  var hidden = 0;
  var type = $('#imgType').val();

  for (var i = 0; i < length; i++) {
    displays[i] = $(headers[i]).css('display');

    if ($('#imgCheck' + i).prop('checked')) {
      $(headers[i]).css('display', 'block');
      $(days[i * sides]).css('display', 'block');

      if (sides == 2)
        $(days[(i * sides) + 1]).css('display', 'block');
    }
    else {
      $(headers[i]).css('display', 'none');
      $(days[i * sides]).css('display', 'none');

      if (sides == 2)
        $(days[(i * sides) + 1]).css('display', 'none');
      hidden += 1;
    }
  }

  var calendar = $('#calendar-container');

  var width = calendar.css('width');
  calendar.css('width', (1036 - (hidden * 139)) + 'px');

  html2canvas(calendar[0], { onrendered: function(canvas) {
    for (var i = 0; i < length; i++) {
      $(headers[i]).css('display', displays[i]);
      $(days[i * sides]).css('display', displays[i]);

      if (sides == 2)
        $(days[(i * sides) + 1]).css('display', displays[i]);
    }

    setCalendar(window.focusedDay);
    $('#generatedImg').html('<img src="' + canvas.toDataURL('image/' + type, 1.0) + '">');
  }});
}
