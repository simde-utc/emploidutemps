function schedule(tasks) {
  $("#skeduler-container").skeduler({
    headers: window.headers,
    tasks: tasks,
  });
}

(function ( $ ) {
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

            if ($('#' + card.id).position().top > 435) // Détecter si la tache ne dépasse pas le calendrier en s'ouvrant
              $('#' + card.id).css('top', 435);
            $('#' + card.id).height(150);

            $('#' + card.id + ' .interraction').css('opacity', '1');
            $('#' + card.id + ' .interraction').css('visibility', 'visible');
            $('#zoneGrey').addClass('focused');
            $('#zoneFocus').addClass('focused');
          }
          else if (window.card.id == card.id) {
            if ($('#' + card.id).position().top == 435)
              $('#' + card.id).css('top', getCardTopPosition(window.card.startTime));
            $('#' + card.id).height(getCardHeight(window.card.duration) - 2).css('z-index: 99'); // Je sais pas pourquoi mais ici height rajoute 2

            $('#' + card.id + ' .interraction').css('opacity', '0');
            $('#' + card.id + ' .interraction').css('visibility', 'hidden');
            $('#zoneGrey').removeClass('focused');
            $('#zoneFocus').removeClass('focused');

            window.card = '';
          }
          else {
            if ($('#' + window.card.id).position().top == 435)
              $('#' + window.card.id).css('top', getCardTopPosition(window.card.startTime));
            $('#' + window.card.id).height(getCardHeight(window.card.duration) - 2).css('z-index: 99');

            $('#' + window.card.id).toggleClass('focus');
            $('#' + window.card.id + ' .interraction').css('opacity', '0');
            $('#' + window.card.id + ' .interraction').css('visibility', 'hidden');

            window.card = card;

            if ($('#' + card.id).position().top > 435)
              $('#' + card.id).css('top', 435);
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

  /**
   * Convert double value of hours to zero-preposited string with 30 or 00 value of minutes
   */
  function toTimeString(value) {
    return (value < 10 ? '0' : '') + Math.ceil(value) + (Math.ceil(value) > Math.floor(value) ? ':30' : ':00');
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

    tasks.forEach(function(task) {
      var card = '';
      var classCard = '';
      var top = getCardTopPosition(task.startTime);
      var height = getCardHeight(task.duration);
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

        task.interraction += "<button class='option' style='color:" + task.fgColor + "; background-color:" + task.bgColor + ";' onClick='seeOthers(\"" + task.uv + "\", \"" + type + "\", " + task.idUV + ");'><i class='fa fa-info' aria-hidden='true'></i> Autres " + (task.type == 'Cours' ? task.type.toLowerCase() : task.type + 's') + "</button>";

        for (var key in colors) {
          if (colors[key] == task.bgColor)
            task.interraction += "<button class='colorButton' style='position: relative; background-color:" + colors[key] + "; color: " + task.fgColor + "' onClick='changeColor(" + task.idUV + ", \"#NULL\");' ><i class=\"fa fa-times\" aria-hidden=\"true\"></i></button>";
          else
            task.interraction += "<button class='colorButton' style='background-color:" + colors[key] + "' onClick='changeColor(" + task.idUV + ", \"" + colors[key] + "\");'/>";
        }

        task.interraction += "<i onClick='$(this).next().click();' class='colorButton fa fa-pencil-square-o' style='position: relative; height: 10px; width: 10px; color:" + task.fgColor + "' aria-hidden='true'></i><input class='colorButton' style='display: none;' onChange='changeColor(" + task.idUV + ", this.value);' type='color'/>";
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

      if (task.nbrSameTime == 1)
        classCard = 'card' + task.columnPerDay + task.semaine;
      else {
        var toPass = [task.columnPerDay, task.column, task.startTime, task.duration];
        if (passed.toString() === toPass.toString())
          nbrPassed += 1;
        else {
          passed = toPass;
          nbrPassed = 0;
        }

        classCard = 'card' + task.columnPerDay + ' nbr' + task.nbrSameTime + nbrPassed;
      }

      card = $('<div></div>')
        .attr({
          style: style,
          id: task.id,
          class: classCard
        });

      if (window.idUV != task.idUV)
        card.on('click', function () {
          settings.onClick(task);
        });

      card.append(renderInnerCardContent(task))
      .appendTo(placeholder);
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
      if (window.mode === 'afficher') {
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

    for (var i = 7; i < 21; i++) {
      // Populate timeline
      if (window.mode === 'afficher') {
        if (i < hour)
          var divHour = passedHour;
        else if (i === hour)
          var divHour = currentHour;
        else
          var divHour = futureHour;
      }
      else
        divHour = div.clone();

      divHour.clone().text(toTimeString(i)).appendTo(scheduleTimelineEl);
      divHour.clone().addClass('passedDay').appendTo(scheduleTimelineEl);

      for (right = 0; right < window.columnPerDay; right++) {
        gridColumnElement[right].append(divHour.clone().addClass(settings.cellCssClass + window.columnPerDay + right));
        gridColumnElement[right].append(divHour.clone().addClass(settings.cellCssClass + window.columnPerDay + right));
      }
    }

    // Populate grid
    for (var j = 0; j < settings.headers.length * window.columnPerDay; j++) {
      var placeholder = div.clone().addClass(settings.taskPlaceholderCssClass);
      appendTasks(placeholder, settings.tasks.filter(function (t) { return t.column == (j / window.columnPerDay); }));

      var el = gridColumnElement[j % window.columnPerDay].clone();

      if (window.mode == 'afficher') {
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
}( jQuery ));
