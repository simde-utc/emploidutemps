var defaultSettings = {
    headers: [],
    tasks: [],

    //cardTemplate: '<div class="time">${horaire}</div><div>${uv}</div><div>(${type} ${groupe})</div><div>${salle}</div><div>${note}</div><div class="interraction" style="opacity: 0; visibility: hidden;">${interraction}</div>',
    cardTemplate: '<div class="time">${horaire}</div><div class="uvType"><span>${uv}</span><h5>${type} ${groupe}</h5></div><div class="uvSalle">${salle}</div><div class="uvNote">${note}</div><div class="interraction" style="opacity: 0; visibility: hidden;">${interraction}</div>',
    // OnClick event handler
    onClick: function (card) {
      if (window.click) {
        window.click = false;
        return;
      }

      if (card.columnPerDay == 1 && window.compare === 0) {
        var type = '';
        if (card.type == 'TD')
          type = 'D';
        else if (card.type == 'TP')
          type = 'T';
        else
          type = 'C';

        $('#zoneGrey').removeClass('focused');
        $('#zoneFocus').removeClass('focused');
        window.card = '';

        seeOthers(card.uv, type, card.idUV);
      }
      else {
        if (window.card === '') {
          window.card = card;

          if (Math.ceil($('#' + card.id).position().top) > 430) // Détecter si la tache ne dépasse pas le calendrier en s'ouvrant
            $('#' + card.id).css('top', 430);
          $('#' + card.id).height(150);

          $('#' + card.id + ' .interraction').css('opacity', '1');
          $('#' + card.id + ' .interraction').css('visibility', 'visible');
          $('#zoneGrey').addClass('focused');
          $('#zoneFocus').addClass('focused');
        }
        else if (window.card.id == card.id) {
          if (Math.ceil($('#' + card.id).position().top) >= 430)
            $('#' + card.id).css('top', Math.ceil(getCardTopPosition(window.card.startTime)));

          $('#' + card.id).height(Math.ceil(getCardHeight(window.card.duration)) - 2).css('z-index: 99'); // Je sais pas pourquoi mais ici height rajoute 2

          $('#' + card.id + ' .interraction').css('opacity', '0');
          $('#' + card.id + ' .interraction').css('visibility', 'hidden');
          $('#zoneGrey').removeClass('focused');
          $('#zoneFocus').removeClass('focused');

          window.card = '';
        }
        else if ($('#' + window.card.id).length === 0) {
          window.card = '';
          $('#' + card.id).addClass('focus');
          $('#' + card.id).click();
        }
        else {
          if (Math.ceil($('#' + window.card.id).position().top) >= 430)
            $('#' + window.card.id).css('top', Math.ceil(getCardTopPosition(window.card.startTime)));
          $('#' + window.card.id).height(Math.ceil(getCardHeight(window.card.duration)) - 2).css('z-index: 99');

          $('#' + window.card.id).toggleClass('focus');
          $('#' + window.card.id + ' .interraction').css('opacity', '0');
          $('#' + window.card.id + ' .interraction').css('visibility', 'hidden');

          window.card = card;

          if (Math.ceil($('#' + card.id).position().top) > 430)
            $('#' + card.id).css('top', 430);
          $('#' + card.id).height(150);

          $('#' + card.id + ' .interraction').css('opacity', '1');
          $('#' + card.id + ' .interraction').css('visibility', 'visible');
        }

        $('#' + card.id).toggleClass('focus');
      }
    },
    // Css classes
    containerCssClass: 'skeduler-container',
    headerContainerCssClass: 'skeduler-headers',
    schedulerContainerCssClass: 'skeduler-main',
    taskPlaceholderCssClass: 'skeduler-task-placeholder',
    cellCssClass: 'skeduler-cell',

    lineHeight: 20,      // height of one half-hour line in grid
    borderWidth: 1,      // width of board of grid cell

    debug: false
};
var settings = {};

function schedule(tasks) {
  $("#skeduler-container").skeduler({
    headers: window.headers,
    tasks: tasks,
  });
/*
  if (window.mode === 'planifier') {
    var cells = $('.skeduler-cell10');
    var length = cells.length;
    var perDay = length / 7;

    cells.each(function(index) {
      var day = Math.floor(index / perDay);
      var begin = window.HOUR_MIN + ((index % perDay) / 2);
      if (begin >= window.HOUR_MAX)
        begin = -1;

      $(cells[index]).on('click', function() {
        createTask(day, begin, 1, 'Créer un nouvel évènement');
      });
    });
  }*/
}
/**
 * Convert double value of hours to zero-preposited string with 30 or 00 value of minutes
 */
function toTimeString(value) {
  return (value < 10 ? '0' : '') + Math.floor(value) + (Math.ceil(value) > Math.floor(value) ? ':30' : ':00');
}

/**
 * Return height of task card based on duration of the task
 * duration - in hours
 */
function getCardHeight(duration) {
  return (settings.lineHeight + settings.borderWidth) * (duration * 2) - 1;
}

/**
 * Return top offset of task card based on start time of the task
 * startTime - in hours
 */
function getCardTopPosition(startTime) {
  return (settings.lineHeight + settings.borderWidth) * (startTime * 2);
}

/**
* Render card template
*/
function renderInnerCardContent(task) {
  var result = settings.cardTemplate;
  for (var key in task) {
    if (task.hasOwnProperty(key))
      result = result.replace('${' + key + '}', task[key]);
  }

  return $(result);
}

/**
 * Generate task cards
 */
function appendTasks(placeholder, tasks) {
  var passed = [];
  var colors = ['#7DC779', '#82A1CA', '#F2D41F', '#457293', '#AB7AC6', '#DF6F53', '#B0CEE9', '#576D7C', '#1C704E'];
  var nbrPassed = 0;

  /*$nbrSameTime = 0;
  // Conversion de minutes en heures
  $exploded = explode(':', $edt['debut'], 2);
  $debut = join('.', array($exploded[0], 100/60*$exploded[1]));
  $exploded = explode(':', $edt['fin'], 2);
  $fin = join('.', array($exploded[0], 100/60*$exploded[1]));

  foreach ($allEdt as $j => $toCompare) {
    if ($edt['jour'] != $toCompare['jour'])
      continue;

    $exploded = explode(':', $toCompare['debut'], 2);
    $debutToCapare = join('.', array($exploded[0], 100/60*$exploded[1]));
    $exploded = explode(':', $toCompare['fin'], 2);
    $finToCapare = join('.', array($exploded[0], 100/60*$exploded[1]));

    if (($debutToCapare >= $debut && $debutToCapare < $fin) || ($debut >= $debutToCapare && $debut < $finToCapare))
      $nbrSameTime++;
  }*/


  var side = false;
  tasks.forEach(function(task) {
    var card = '';
    var classCard = '';
    nbrSameTime = 0;

    if (task.duration >= 14) {
      task.startTime = 14;
      task.duration = 1;
    }

    tasks.forEach(function(toCompare) {
      if (toCompare.duration < 14 && (task.columnPerDay === toCompare.columnPerDay && task.column === toCompare.column) && ((task.startTime >= toCompare.startTime && task.startTime < toCompare.startTime + toCompare.duration) || (toCompare.startTime >= task.startTime && toCompare.startTime < task.startTime + task.duration)))
        nbrSameTime++;
    });

    var top = Math.ceil(getCardTopPosition(task.startTime));
    var height = Math.ceil(getCardHeight(task.duration));
    var style = 'top: ' + top + 'px; height: ' + height + 'px';

    if (typeof task.interraction == 'undefined') {
      if (typeof task.login == 'undefined' && (window.compare == 1 || window.columnPerDay == 1))
        task.interraction = '';
      else
        task.interraction = "<button class='option' style='color:" + task.fgColor + "; background-color:" + task.bgColor + ";' onClick='edtUV(\"" + task.uv + "\");'><i class='fa fa-calendar-o' aria-hidden='true'></i> Voir l'edt de l'UV</button>";

      task.interraction += "<button class='option' style='color:" + task.fgColor + "; background-color:" + task.bgColor + "; width: 59px' onClick='uvMoodle(\"" + task.uv + "\");'><i class='fa fa-external-link' aria-hidden='true'></i> Moodle</button><button class='option' style='color:" + task.fgColor + "; background-color:" + task.bgColor + "; width: 59px' onClick='uvWeb(\"" + task.uv + "\");' ><i class='fa fa-external-link' aria-hidden='true'></i> UVweb</button><button class='option' style=\"color:" + task.fgColor + "; background-color:" + task.bgColor + ";\" onClick=\"seeEtu(" + task.idUV + ");\"><i class='fa fa-user-o' aria-hidden='true'></i> Voir les étudiants</button>";

      if (window.compare === 0 && task.columnPerDay == 2)
        task.interraction += "<button class='option' style='color:" + task.fgColor + "; background-color:" + task.bgColor + "' onClick='askForExchange(" + window.idUV + ", " + task.idUV + ");'><i class='fa fa-handshake-o' aria-hidden='true'></i> Proposer un échange</button>";
    }

    if (task.session) {
      var type = '';
      if (task.type == 'TD')
        type = 'D';
      else if (task.type == 'TP')
        type = 'T';
      else
        type = 'C';

      task.interraction += "<button class='option' style='color:" + task.fgColor + "; background-color:" + task.bgColor + ";' onClick='seeOthers(\"" + task.uv + "\", \"" + type + "\", " + task.idUV + ");'><i class='fa fa-info' aria-hidden='true'></i> Echanger son " + (task.type == 'Cours' ? task.type.toLowerCase() : task.type) + "</button>";

      for (var key in colors) {
        if (colors[key] == task.bgColor)
          task.interraction += "<button class='colorButton' style='background-color:" + colors[key] + "; color: " + task.fgColor + "' onClick='changeColor(" + task.idUV + ", \"#NULL\");' ><i class=\"fa fa-times\" aria-hidden=\"true\"></i></button>";
        else
          task.interraction += "<button class='colorButton' style='background-color:" + colors[key] + "; color: " + colors[key] + "' onClick='changeColor(" + task.idUV + ", \"" + colors[key] + "\");'>0</button>";
      }

      task.interraction += "<i onClick='$(this).next().click();' class='colorButton fa fa-pencil-square-o' style='height: 10px; width: 10px; color:" + task.fgColor + "' aria-hidden='true'></i><input class='colorButton' style='display: none;' onChange='changeColor(" + task.idUV + ", this.value);' type='color'/>";
    }

    if (window.idUV == task.idUV) {
      if (task.columnPerDay == 1)
        style += '; box-shadow: 0em 1em 1em rgba(0,0,0,.5)';
      else {
        style += '; opacity: 0.5';
        task.fgColor = '#000000';
        task.bgColor = '#CCCCCC';
        task.interraction = '';
      }
    }

    style += '; color: ' + task.fgColor + '; background-color: ' + task.bgColor;

    if (window.columnPerDay == 1 && window.compare == 1) {
      classCard = 'card0' + task.semaine;
      style += '; opacity: 0.75; box-shadow: none; cursor: default';

      card = $('<div></div>')
        .attr({
          style: style,
          id: task.id,
          class: classCard
        });
      card.append($('<div>' + task.idUV + '<h6 style="display: inline; padding-left: 2px;">' + task.salle + '</h6></div>')).appendTo(placeholder);
      return;
    }

    if (nbrSameTime == 1)
      classCard = 'card' + task.columnPerDay + task.semaine;
    else {
      var toPass = [task.columnPerDay, task.column, task.startTime, task.duration];
      if ((passed[0] === toPass[0] && passed[1] === toPass[1]) && ((passed[2] >= toPass[2] && passed[2] < toPass[2] + toPass[3]) || (toPass[2] >= passed[2] && toPass[2] < passed[2] + passed[3])))
        nbrPassed += 1;
      else {
        passed = toPass;
        nbrPassed = 0;
        side = !side;
      }

      nbr = (nbrSameTime > 4 ? ((nbrSameTime + 4) % 5) : (nbrSameTime % 5));
      if (nbr == 0) { nbr = 2; }
      classCard = 'card' + task.columnPerDay + ' nbr' + nbr + ((side || nbrSameTime > 4 ? (nbr - (nbrPassed % nbr)) : nbrPassed) % nbr);
    }

    card = $('<div></div>')
      .attr({
        style: style,
        id: task.id,
        class: classCard
      });

    if (window.mode == 'planifier' && window.get.indexOf('salle=') > -1) {
      card.on('click', function () {
        popup('<div id="popupHead">' + task.idUV + ' salle' + (task.idUV == 1 ? '' : 's') + ' disponible' + (task.idUV == 1 ? '' : 's') + ' de ' + task.horaire.replace('-', ' à ').replace(':', 'h').replace(':', 'h') + '</div><table><tr><td>Salle' + (task.note['C'].length == 1 ? '' : 's') + ' de cours</td><td>:</td><td> ' + task.note['C'].join(', ') + '</td></tr><tr><td>Salle' + (task.note['D'].length == 1 ? '' : 's') + ' de TD</td><td>:</td><td> ' + task.note['D'].join(', ') + '</td></tr></table>');
      });
      card.append($('<div class="time">' + (task.startTime < 14 ? toTimeString(task.duration) : 'Journée') + '</div><div class="uvType"><span>' + task.uv + '</span></div>')).appendTo(placeholder);
      return;
    }

    if (window.idUV != task.idUV)
      card.on('click', function () {
        settings.onClick(task);
      });

    card.append(renderInnerCardContent(task)).appendTo(placeholder);
  }, this);
}

/**
* Generate scheduler grid with task cards
* options:
* - headers: string[] - array of headers
* - tasks: Task[] - array of tasks
* - containerCssClass: string - css class of main container
* - headerContainerCssClass: string - css class of header container
* - schedulerContainerCssClass: string - css class of scheduler
* - lineHeight - height of one half-hour cell in grid
* - borderWidth - width of border of cell in grid
*/
$.fn.skeduler = function( options ) {
  settings = $.extend(defaultSettings, options);
  var date = new Date();
  var currentDay = (date.getDay() + 6) % 7;

  if (settings.debug) {
    console.time('skeduler');
  }

  var skedulerEl = $(this);

  skedulerEl.empty();
  skedulerEl.addClass(settings.containerCssClass);

  var div = $('<div></div>');

  // Add headers
  var headerContainer = div.clone().addClass(settings.headerContainerCssClass);
  var d = 0;
  var classDay = '';

  settings.headers.forEach(function(element) {
    if (window.mode === 'planifier') {
      if (d < currentDay)
        classDay = 'passedDay';
      else if (d == currentDay)
        classDay = 'currentDay';
      else
        classDay = 'futureDay';
    }

    div.clone().addClass(classDay).text(element).appendTo(headerContainer);
    d++;
  }, this);

  // Pour améliorer la propreté du tableau
  div.clone().css('flex', '0').appendTo(headerContainer);

  //div.clone().addClass('currentDay').text(settings.headers[currentDay]).appendTo(headerContainer);
  skedulerEl.append(headerContainer);

  // Add schedule
  var scheduleEl = div.clone().addClass(settings.schedulerContainerCssClass);
  var scheduleTimelineEl = div.clone().addClass(settings.schedulerContainerCssClass + '-timeline');
  var scheduleBodyEl = div.clone().addClass(settings.schedulerContainerCssClass + '-body');

  var gridColumnElement = [];
  for (var right = 0; right < window.columnPerDay; right++)
    gridColumnElement[right] = div.clone();

  var hour = date.getHours();
  var passedHour = div.clone().addClass('passedHour');
  var currentHour = div.clone().addClass('currentHour');
  var futureHour = div.clone().addClass('futureHour');

  // Populate timeline
  for (var i = window.HOUR_MIN; i < window.HOUR_MAX; i++) {
    if (window.mode === 'planifier') {
      if (i < hour)
        divHour = passedHour;
      else if (i === hour)
        divHour = currentHour;
      else
        divHour = futureHour;
    }
    else
      divHour = div.clone();

    divHour.clone().text(toTimeString(i)).appendTo(scheduleTimelineEl);
    divHour.clone().appendTo(scheduleTimelineEl);

    for (right = 0; right < window.columnPerDay; right++) {
      gridColumnElement[right].append(div.clone().addClass(settings.cellCssClass + window.columnPerDay + right));
      gridColumnElement[right].append(div.clone().addClass(settings.cellCssClass + window.columnPerDay + right));
    }
  }

  if (window.mode === 'planifier') {
    futureHour.clone().text('').addClass('allDay').appendTo(scheduleTimelineEl);
    gridColumnElement[0].append(div.clone().addClass('allDay').addClass(settings.cellCssClass + '10'));
  }

  // Populate grid
  for (var j = 0; j < settings.headers.length * window.columnPerDay; j++) {
    var placeholder = div.clone().addClass(settings.taskPlaceholderCssClass);
    appendTasks(placeholder, settings.tasks.filter(function (t) { return t.column == (j / window.columnPerDay); }));

    var el = gridColumnElement[j % window.columnPerDay].clone();

    if (window.mode === 'planifier') {
      if (j == currentDay)
        el.addClass('currentDay');
      else if (j > currentDay)
        el.addClass('futureDay');
      else
        el.addClass('passedDay');
    }

    el.addClass('days');
    el.prepend(placeholder);
    el.appendTo(scheduleBodyEl);
    /*
    var el = gridColumnElement.clone();
    el.appendTo(scheduleBodyEl);*/
  }

  scheduleEl.append(scheduleTimelineEl);
  scheduleEl.append(scheduleBodyEl);

  skedulerEl.append(scheduleEl);

  if (settings.debug) {
    console.timeEnd('skeduler');
  }

  return skedulerEl;
};
