var HOUR_MIN = 7;
var HOUR_MAX = 21;
var headers = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi",  "Samedi", 'Dimanche'];
var sessionLogin = '';
var get = {};
var colors = [];
var task = null;

getRequest = function (url, get, callback) {
  request = ''
  for (var key in get) {
    if (typeof get[key] == 'string' || typeof get[key] == 'number')
      request += '&' + key + '=' + get[key];
    else {
      for (var key2 in get[key])
        request += '&' + key + '[]=' + get[key][key2];
    }
  }

  $.getJSON('https://' + window.location.hostname + '/emploidutemps/ressources/php/' + url + '?' + request.substr(1), function(data) { callback(data) });
}

addGet = function (get) {
  for (var key in get) {
    window.get[key] = get[key];
  };
}

changeMode = function (mode) {
  window.get.mode = mode;
  generate();
}

generate = function () {
  console.time('generate');
  popupClose();
  loading();

  $('#zoneGrey').removeClass('focused');
  $('#zoneFocus').removeClass('focused');
  $('#zonePopup').removeClass('focused');

  getRequest('getData.php', window.get, function (data) {
    console.timeEnd('generate');
    window.sessionLogin = data.infos.login;
    window.colors = data.infos.colors;
    window.get = data.infos.get;
    window.get.addActiveTabs = undefined;
    window.get.setActiveTabs = undefined;
    window.get.delActiveTabs = undefined;

    $('#sTitle').text(data.title);
    generateTabs(data.tabs);
    generateCalendar(data.tasks, data.infos.sides, data.infos.uvs);
    unFocus();
    window.task = null;
    $('#option option[value="' + window.get.mode + '"]').prop('selected', true);
    setCalendar();
  });
}

loading = function () {
  $('<img>').attr('id', 'loading').attr('src', 'https://' + window.location.hostname + '/emploidutemps' + '/ressources/img/loading.gif').appendTo($('#calendar-container'))
}

endLoading = function () {
  $('#loading').remove();
}

popup = function (popupHead, html, bgColor, fgColor) {
  window.click = true;
  $('#zonePopup').addClass('focused');

  bgColor = bgColor || '#BBBBBB';
  fgColor = fgColor || '#000000';
  $('#popup').css('border', '5px SOLID' + bgColor);

  $('#popup').html($('<div></div>').append($('<div></div>').attr('id', 'popupHead').css('border', '5px SOLID' + bgColor).css('background-color', bgColor).css('color', fgColor).html(popupHead)).html()).append(html);
  $('#popup').css('visibility', 'visible');
  $('#popup').css('opacity', '1');

  if ($('.focusedInput').length != 0)
    $('.focusedInput')[0].focus();

  if ($(".submitedInput").length != 0 && $(".submitedButton").length != 0)
    $(".submitedInput").last().keyup(function (event) {
      code = event.keyCode || event.which;
      if(code == 13)
        $(".submitedButton")[0].click();
    });
}

unFocus = function () {
  $('#zoneGrey').removeClass('focused');
  $('#zoneFocus').removeClass('focused');

  if (window.task != null)
    $('#' + window.task.id).click();
}

seeUVCards = function (uv) {
  if (window.get.mode == 'comparaison')
    window.get = { 'mode': 'comparaison' };
  else
    window.get = { 'mode': 'classique' };

  window.get.uv = uv;

  generate();
}

seeUVInformations = function (task) {
  getRequest('getInfos.php', {
    'idUV': task.idUV
  }, function (data) {
    popupHead = 'Liste des ' + task.nbrEtu + ' étudiants en ' + (task.type == 'D' ? 'TD' : (task.type == 'T' ? 'TP' : 'cours')) + ' de ' + task.subject + ' de ' + task.timeText.replace('-', ' à ');
    corps = $('<div></div>').attr('id', 'allCards');
    optionCards = $('<div></div>').addClass('optionCards');
    button = $('<button></button>');
    button.clone().html('<i class="fa fa-external-link" aria-hidden="true"></i> Moodle').on('click', function () { moodle(task.subject); }).appendTo(optionCards);
    button.clone().html('<i class="fa fa-external-link" aria-hidden="true"></i> UVWeb').on('click', function () { UVWeb(task.subject); }).appendTo(optionCards);
    optionCards.appendTo(corps);
    popup(popupHead, $('<div></div>').append(corps).html(), task.bgColor, task.fgColor);
  });
}

seeOthers = function (uv, type, idUV) {
  window.get = {
    'mode': 'modifier',
    'uv': uv,
    'type': type,
    'idUV': idUV
  };

  generate();
}

changeColor = function(idUV, color) {
  getRequest('setColor.php', {
    'idUV': idUV,
    'color': color.substr(1)
  }, function (data) {
    generate();
  });
}


/* Echanges */

exchange = function (get) {
  getRequest('exchange.php', get, function(data) {
    popup(data);
  });
}

askForExchange = function (idUV, idUV2) {
  exchange({
    'idUV': idUV,
    'idUV2': idUV2
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


getFgColor = function (bgColor) {
  if ((((parseInt(bgColor.substr(1, 2), 16) * 299) + (parseInt(bgColor.substr(3, 4), 16) * 587) + (parseInt(bgColor.substr(5, 6), 16) * 114))) > 127000)
    return '#000000';
  else
    return '#FFFFFF';
}



generateTabs = function (tabs) {
  console.time('tabs');
  $('#tabs').empty();

  $.each(tabs, function (key, tab) {
    console.log(tab)

    if (tab.type == 'button') {
      container = $('<button></button>').clone().html(tab.text);

      if (key == 'me')
        container.attr('id', 'myTab');

      if (tab.action == undefined) {
        container.on('click', function() {
          addGet(tab.get);

          generate();
        });
      }
      else {
        container.attr('onClick', tab.action);
      }
    }
    else if (tab.type == 'select') {
      container = $('<select></select>').append($('<option></option>').attr('disabled', true).attr('selected', true).val('disabled').text(tab.text)).attr('id', 'tab-' + key).on('change', function () {
        if (tab.get != undefined)
          addGet(tab.get);

        toAdd = tab.options[$( this ).val()].get;
        if (toAdd != undefined)
          addGet(toAdd);

          console.log(get)
        $( this ).val('disabled');
        generate();
      });

      for (var value in tab.options) {
        option = $('<option></option>').attr('value', value);
        text = tab.options[value].text;

        if (tab.options[value].active)
          text += ' ✓';

        option.html(text).appendTo(container);
      }
    }

    if (tab.active)
      container.addClass('active');

    container.appendTo($('#tabs'));

    if (tab.separate)
      $('<div></div>').addClass('separatorTab').appendTo($('#tabs'));
  });
  console.timeEnd('tabs');
}

cardClick = function (task) {
  var end = (window.get.mode == 'semaine' ? 629 : 588);

  if (window.task == null) {
    if (task.top + 150 > end) // Détecter si la tache ne dépasse pas le calendrier en s'ouvrant
      $('#' + task.id).css('top', end - 150);
    if (task.height < 150)
      $('#' + task.id).css('height', 150);
    $('#' + task.id).css('left', -2).css('width', 135).addClass('focus');
    $('#' + task.id + ' .interraction').css('display', 'block');

    $('#zoneGrey').addClass('focused');
    $('#zoneFocus').addClass('focused');

    window.task = task;
  }
  else if (window.task.id == task.id) {
    $('#' + task.id).css('top', task.top).css('left', task.left).css('height', task.height).css('width', task.width).removeClass('focus');
    $('#' + task.id + ' .interraction').css('display', 'none');

    $('#zoneGrey').removeClass('focused');
    $('#zoneFocus').removeClass('focused');

    window.task = null;
  }
  else {
    if (window.task != null) {
      $('#' + window.task.id).css('top', window.task.top).css('left', window.task.left).css('height', window.task.height).css('width', window.task.width).removeClass('focus');
      $('#' + window.task.id + ' .interraction').css('display', 'none');
    }

    if (task.top + 150 > end) // Détecter si la tache ne dépasse pas le calendrier en s'ouvrant
      $('#' + task.id).css('top', end - 150);
    if (task.height < 150)
      $('#' + task.id).css('height', 150);
    $('#' + task.id).css('left', -2).css('width', 135).addClass('focus');
    $('#' + task.id + ' .interraction').css('display', 'block');

    $('#zoneGrey').addClass('focused');
    $('#zoneFocus').addClass('focused');

    window.task = task;
  }
}

cardRoomClick = function (task) {
  toAdd = (task.subject > 1 ? 's' : '');
  popupHead = task.subject + ' salle' + toAdd + ' disponible' + toAdd + (task.timeText == 'Journée' ? ' toute la journée' : (' vers ' + Math.ceil(task.startTime) + 'h pour ' + task.timeText));
  table = $('<table></table').css('padding', '1%').css('width', '100%');

  for (key in task.description) {
    rooms = task.description[key]
    tr = $('<tr></tr>');
    $('<td></td>').css('width', '15%').text('Salle' + (rooms.length == 1 ? '' : 's') + ' de ' + (key == 'D' ? 'TD' : (key == 'C' ? 'cours' : 'TP'))).appendTo(tr);
    $('<td></td>').css('width', '85%').text(rooms.join(', ')).appendTo(tr);
    tr.appendTo(table);
  }

  popup(popupHead, $('<div></div>').append(table).html(), task.bgColor, task.fgColor);
}

generateCards = function (schedulerTasks, tasks, day, sides, uvs) {
  console.time('cards');
  var passed = [];
  var toPass = [];
  var nbrPassed = 0;
  var nbrSameTime = 1;
  var div = $('<div></div>');
  var button = $('<button/>');

  tasks.forEach(function(group) {
    group.data.forEach(function(task) {
      if (task.day != day)
        return;

      task.top = Math.ceil(21 * ((task.startTime - window.HOUR_MIN) * 2));
      task.height = Math.ceil(21 * (task.duration * 2) - 1);

      if (task.duration - task.startTime == 24) {
        task.top = Math.ceil(21 * ((window.HOUR_MAX - window.HOUR_MIN) * 2));
        task.height = Math.ceil(21 * (1 * 2) - 1);
      }

      card = div.clone().attr({
        'id': task.id,
        'class': 'card',
      });

      style = {
        'top': task.top,
        'height': task.height,
        'background-color': task.bgColor,
        'color': getFgColor(task.bgColor)
      };

      // Il faut vérifier si des cards coincident
      nbrSameTime = 1;
      tasks.forEach(function(groupToCompare) {
        groupToCompare.data.forEach(function(toCompare) {
          if (toCompare.day != day || task.id == toCompare.id)
            return;

          if ((group.side === groupToCompare.side && ((task.startTime >= toCompare.startTime && task.startTime < toCompare.startTime + toCompare.duration) || (toCompare.startTime >= task.startTime && toCompare.startTime < task.startTime + task.duration))) && ((task.duration < (window.HOUR_MAX - window.HOUR_MIN) && toCompare.duration < (window.HOUR_MAX - window.HOUR_MIN)) || task.duration == toCompare.duration))
            nbrSameTime++;

          if ((window.get.mode == 'comparaison' || window.get.mode == 'modifier') && task.idUV == toCompare.idUV)
            card.addClass('sameCard');
        });
      });

      if (group.type == 'organize') {
        $('<div></div>').append($('<span></span>').text(task.subject)).append($('<h6></h6>').css('display', 'inline').css('padding-left', '2px').text(task.location)).appendTo(card);
        style.opacity = 0.5;
        style['box-shadow'] = 'none';
      }
      else {
        toPass = [group.side, task.day, task.startTime, task.duration];
        nbrPassed = 0;
        passed.forEach(function (toCompare) {
          if (((toCompare[0] === toPass[0] && toCompare[1] === toPass[1]) && ((toCompare[2] >= toPass[2] && toCompare[2] < toPass[2] + toPass[3]) || (toPass[2] >= toCompare[2] && toPass[2] < toCompare[2] + toCompare[3]))) && ((toPass[3] < (window.HOUR_MAX - window.HOUR_MIN) && toCompare[3] < (window.HOUR_MAX - window.HOUR_MIN)) || toPass[3] == toCompare[3]))
            nbrPassed += 1;
        });
        passed.push(toPass);

        task.width = 133 / ((sides * nbrSameTime) + (nbrSameTime == 1 && task.week != undefined));
        task.left = (group.side == undefined ? 0 : ((group.side - 1) * 69)) - sides + (nbrPassed * task.width) + ((nbrSameTime == 1 && task.week != undefined) * 33);

        style.width = task.width;
        style.left = task.left;

        isUV = group.type == 'uv_followed' || group.type == 'uv' || group.type == 'exchange_received' || group.type == 'exchange_sent' || group.type == 'exchange_canceled';
        subject = div.clone().addClass('subject');

        $('<span></span>').text(task.subject + (group.type == 'room' ? ' dispo' + (task.subject > 1 ? 's' : '') : '')).appendTo(subject);

        if (isUV) {
          type = (task.type == 'D' ? 'TD' : (task.type == 'T' ? 'TP' : 'Cours'));
          $('<h5></h5>').text(type + ' ' + task.groupe).appendTo(subject);
        }

        div.clone().addClass('time').text(task.timeText).appendTo(card);
        subject.appendTo(card);
        div.clone().addClass('location').text(task.location).appendTo(card);

        if (task.note != null)
          div.clone().addClass('note').text(task.note).appendTo(card);

        if (window.get.mode == 'modifier') {
          if (card.hasClass('sameCard')) {
            style.opacity = 0.5;
            style['box-shadow'] = 'none';
          }
          else {
            if (group.side == 1) {
              card.on('click', function () {
                seeOthers(task.subject, task.type, task.idUV);
              });
            }
            else {
              interraction = div.clone().addClass('interraction');
              infosExchange = div.clone().addClass('infosExchange');
              option = button.clone().addClass('option').css('background-color', task.bgColor).css('color', getFgColor(task.bgColor));

              if (window.get.mode_type == null) {
                option.clone().html("<i class='fa fa-calendar-o' aria-hidden='true'></i> Voir l'edt de l'UV").on('click', function() { seeUVCards(task.subject); }).appendTo(interraction);
                option.clone().html("<i class='fa fa-info' aria-hidden='true'></i> Informations").on('click', function() { seeUVInformations(task); }).appendTo(interraction);
                option.clone().html('<i class="fa fa-handshake-o" aria-hidden="true"></i> Proposer un échange').on('click', function() {
                  askForExchange();
                }).appendTo(interraction);
              }

              interraction.appendTo(card);
              card.on('click', function () {
                cardClick(task);
              });
            }
          }
        }
        else if (group.type == 'room') {
          card.on('click', function () {
            cardRoomClick(task);
          });
        }
        else if (isUV || group.type == 'calendar') {
          interraction = div.clone().addClass('interraction');
          option = button.clone().addClass('option').css('background-color', task.bgColor).css('color', getFgColor(task.bgColor));

          if (group.type == 'uv_followed') {
            option.clone().html("<i class='fa fa-calendar-o' aria-hidden='true'></i> Voir l'edt de l'UV").on('click', function() { seeUVCards(task.subject); }).appendTo(interraction);
            option.clone().html("<i class='fa fa-info' aria-hidden='true'></i> Echanger cet UV").on('click', function() { seeOthers(task.subject, task.type, task.idUV); }).appendTo(interraction);
            option.clone().html("<i class='fa fa-info' aria-hidden='true'></i> Informations").on('click', function() { seeUVInformations(task); }).appendTo(interraction);

            if (window.sessionLogin == group.info) {
              colorButton = button.clone().addClass('colorButton');
              window.colors.forEach(function (color) {
                if (color == task.bgColor)
                  colorButton.clone().html('<i class="fa fa-times" aria-hidden="true"></i>').on('click', function() { changeColor(task.idUV, '#NULL'); }).css('background-color', color).css('color', getFgColor(color)).appendTo(interraction);
                else
                  colorButton.clone().text('0').on('click', function() { changeColor(task.idUV, color); }).css('background-color', color).css('color', color).appendTo(interraction);
              });

              $('<i></i>').addClass('colorButton fa fa-pencil-square-o').on('click', function() { $( this ).next().click(); }).css('color', '#000000').appendTo(interraction);
              $('<input>').addClass('colorButton').on('change', function() { changeColor(task.idUV, this.value); }).attr('type', 'color').css('display', 'none').appendTo(interraction);
            }
          }
          else if (group.type == 'uv') {
            if (uvs.search(task.subject) != -1)
              option.clone().html("<i class='fa fa-info' aria-hidden='true'></i> Echanger cet UV").on('click', function() { seeOthers(task.subject, task.type, task.idUV); }).appendTo(interraction);

            option.clone().html("<i class='fa fa-info' aria-hidden='true'></i> Informations").on('click', function() { seeUVInformations(task); }).appendTo(interraction);
          }
          else if (group.type == 'calendar')
            interraction.text(task.description);

          interraction.appendTo(card);
          card.on('click', function () {
            cardClick(task);
          });
        }
      }

      for (var key in style) {
        card.css(key, style[key]);
      }

      card.appendTo(schedulerTasks);
    });
  });
  console.timeEnd('cards');
}

generateCalendar = function(tasks, sides, uvs) {
  console.time('calendar');
  var div = $('<div></div>');
  var schedule = div.clone().addClass('calendar-container');
  var scheduleHeader = div.clone().addClass('calendar-headers');
  var scheduleMain = div.clone().addClass('calendar-main');
  var scheduleTimeline = div.clone().addClass('calendar-main-timeline');
  var scheduleBody = div.clone().addClass('calendar-main-body');

  var currentDay = (date.getDay() + 6) % 7;
  var hour = date.getHours();

  // On check si aujourd'hui fais parti de la semaine choisie
  if (Date.parse(window.get.week) + (1000*60*60*24*(currentDay + 2)) < Date.now())
    currentDay = 7;
  else if (Date.parse(window.get.week) > Date.now())
    currentDay = -1;

  // Préparation du header
  var classDay = '';
  window.headers.forEach(function(element, day) {
    if (window.get.mode === 'semaine') {
      if (day < currentDay)
        classDay = 'passedDay';
      else if (day == currentDay)
        classDay = 'currentDay';
      else
        classDay = 'futureDay';
    }

    div.clone().addClass(classDay).text(element).appendTo(scheduleHeader);
  }, this);
  // Pour améliorer la propreté du tableau
  div.clone().css('flex', '0').appendTo(scheduleHeader);

  // Ajout du header
  schedule.append(scheduleHeader);

  // Préparation des colonnes de chaque jour
  var gridColumnElement = [];
  for (var side = 0; side < sides; side++)
    gridColumnElement[side] = div.clone();

  // Création des heures
  var classHour = '';
  for (var i = window.HOUR_MIN; i < window.HOUR_MAX; i++) {
    if (window.get.mode === 'semaine') {
      if (currentDay == 7 || i < hour)
        classHour = 'passedHour';
      else if (currentDay == -1 || i > hour)
        classHour = 'futureHour';
      else
        classHour = 'currentHour';
    }
    else
      classHour = '';

    div.clone().addClass(classHour).text((i < 10 ? '0' : '') + Math.floor(i) + (Math.ceil(i) > Math.floor(i) ? ':30' : ':00')).appendTo(scheduleTimeline);
    div.clone().addClass(classHour).appendTo(scheduleTimeline);

    // Pour chaque heure, on a deux cases
    for (side = 0; side < sides; side++) {
      gridColumnElement[side].append(div.clone().addClass('calendar-cell' + sides + side));
      gridColumnElement[side].append(div.clone().addClass('calendar-cell' + sides + side));
    }
  }

  // Ajout de la case toute la journée
  if (window.get.mode === 'semaine') {
    if (classHour == 'currentHour')
      classHour = 'futureHour';
    div.clone().text('').addClass(classHour).addClass('allDay').appendTo(scheduleTimeline);
    gridColumnElement[0].append(div.clone().addClass('allDay').addClass('calendar-cell' + '10'));
  }

  // On peuple l'affichage
  for (var j = 0; j < window.headers.length * sides; j++) {
    var schedulerTasks = div.clone().addClass('calendar-tasks');
    generateCards(schedulerTasks, tasks, j / sides, sides, uvs);

    var grid = gridColumnElement[j % sides].clone();

    if (window.get.mode === 'semaine') {
      if (j == currentDay) {
        grid = div.clone();

        for (var i = window.HOUR_MIN; i < window.HOUR_MAX; i++) {
          if (i < hour)
            classHour = 'passedHour';
          else if (i === hour)
            classHour = 'currentHour';
          else
            classHour = 'futureHour';

          grid.append(div.clone().addClass(classHour).addClass('calendar-cell10'));
          grid.append(div.clone().addClass(classHour).addClass('calendar-cell10'));
        }

        grid.append(div.clone().addClass('allDay').addClass(classHour).addClass('calendar-cell10'));
      }
      else if (j > currentDay)
        grid.addClass('futureDay');
      else
        grid.addClass('passedDay');
    }

    grid.addClass('days');
    grid.prepend(schedulerTasks);
    grid.appendTo(scheduleBody);
  }

  scheduleMain.append(scheduleTimeline);
  scheduleMain.append(scheduleBody);

  schedule.append(scheduleMain);

  $('#calendar-container').html(schedule);
  console.timeEnd('calendar');
};

// Il faut modifier l'affichage du calendrier en fonction de la taille de l'écran
function setCalendar(day) {
  var headers = $('.calendar-headers div');
  var days = $('.calendar-main-body .days');
  var sides = days.length / (headers.length - 1);
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

  $('#calendar-container').width(numbers[number - 1] || 0);
  $('#otherDay').css('display', 'block').css('padding-right', numbers[number - 1] - 60);

  if (number >= length) {
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
      if ($( this ).css('display') === 'block')
        diff = true;

      $( this ).css('display', 'none');
      $(days[index * sides]).css('display', 'none');
      if (sides === 2)
        $(days[index * sides + 1]).css('display', 'none');
    }
    else {
      if ($( this ).css('display') === 'none')
        diff = true;

      $( this ).css('display', 'block');
      $(days[index * sides]).css('display', 'block');
      if (sides === 2)
        $(days[index * sides + 1]).css('display', 'block');
    }
  });

  // On ne change pas de jour focus si l'affichage ne change pas, par contre on le réduit au min pour appliquer un changement (éviter d'appuyer 5 fois sur le bouton pour rien par ex)
  if (diff)
    window.focusedDay = focusedDay;
  else {
    if (window.focusedDay - focusedDay === 1) {
      for (var i = focusedDay - 1; i > 0; i--) {
        setCalendar(i);
        if (focusedDay - window.focusedDay != 1)
          break;
      }
    }
    else if (focusedDay - window.focusedDay === 1) {
      for (var i = focusedDay + 1; i < length; i++) {
        setCalendar(i);
        if (window.focusedDay - focusedDay != 1)
          break;
      }
    }
  }
}
