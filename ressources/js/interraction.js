

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
