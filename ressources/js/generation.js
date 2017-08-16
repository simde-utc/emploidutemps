var HOUR_MIN = 7;
var HOUR_MAX = 21;
var headers = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi",  "Samedi", 'Dimanche'];
var sessionLogin = '';
var get = {};
var colors = [];
var task = null;

var getRequest = function (url, get, callback) {
  var request = '';

  for (var key in get) {
    if (typeof get[key] == 'string' || typeof get[key] == 'number')
      request += '&' + key + '=' + get[key];
    else {
      for (var key2 in get[key])
        request += '&' + key + '[]=' + get[key][key2];
    }
  }

  console.log('https://' + window.location.hostname + '/emploidutemps/ressources/php/' + url + ($.isEmptyObject(get) ? '' : '?') + request.substr(1));
  $.getJSON('https://' + window.location.hostname + '/emploidutemps/ressources/php/' + url + ($.isEmptyObject(get) ? '' : '?') + request.substr(1), function(data) {
    if (data.error) {
      if ($('#zonePopup').hasClass('focused')) {
        if ($('#popupError').length == 0)
          $('#popupHead').append($('<div></div>').attr('id', 'popupError').html(data.error));
        else
          $('#popupError').html(data.error);
      }
      else
        popup('Erreur', $('<div></div>').attr('id', 'popupError').html(data.error));
    }
    else
      callback(data);
  });
};

var addGet = function (get) {
  for (var key in get) {
    window.get[key] = get[key];
  }
};

var changeMode = function (mode) {
  window.get.mode = mode;
  generate();
};

var generate = function () {
  console.time('generate');
  popupClose();
  loading();

  $('#zoneGrey').removeClass('focused');
  $('#zoneFocus').removeClass('focused');
  $('#zonePopup').removeClass('focused');

  getRequest('calendar.php', window.get, function (data) {
    console.timeEnd('generate');
    console.log(data);
    window.sessionLogin = data.infos.login;
    window.active = data.infos.active;
    window.colors = data.infos.colors;
    window.get = data.infos.get;
    window.sides = data.infos.sides;
    delete window.get.addActive;
    delete window.get.setActiveTabs;
    delete window.get.delActive;

    getRequest('groups.php', {
      'mode': 'get',
    }, function (groups) {
      generatePrinted(groups.groups);
    });
    generateTitle(data.title);
    generateWeeks(data.infos.week, data.infos.get.week);
    generateSubMenu(data.tabs, 'tab');
    generateSubMenu(data.groups, 'group');
    generateCalendar(data.tasks, data.infos.sides, data.infos.uvs);

    unFocus();
    window.task = null;
    setCalendar();

    //  Animer l'affichage d'une UV à échanger lors de la réception du mail
    if (window.get.id != undefined) {
      setTimeout(function () {
        $('#' + window.get.id).click();
        console.log(window.get.id);
      }, 500);
    }
  });
};

var loading = function () {
  $('<img>').attr('id', 'loading').attr('src', 'https://' + window.location.hostname + '/emploidutemps' + '/ressources/img/loading.gif').appendTo($('#calendar-container'));
};

var endLoading = function () {
  $('#loading').remove();
};

var submited = function () {
  setTimeout(function() {
    if ($('.focusedInput').length != 0)
      $('.focusedInput').first().focus();

    if ($(".submitedInput").length != 0 && $(".submitedButton").length != 0)
      $(".submitedInput").keyup(function (event) {
        var code = event.keyCode || event.which;

        console.log($(".submitedInput:focus").parents().find('.submitedButton'))

        if (code == 13)
          $(".submitedInput:focus").parents().find('.submitedButton').first().click();
      });
  }, 100);
};

var setPopupButtons = function (enable) {
  $('#popup').find('button').each(function () {
    $(this).prop('disabled', !enable);
  });
}

var popup = function (popupHead, content, bgColor, fgColor) {
  window.click = true;
  $('#zonePopup').addClass('focused');
  $('#nav').removeClass('see');
  $('#parameters').removeClass('see');

  bgColor = bgColor || '#BBBBBB';
  fgColor = fgColor || '#000000';
  $('#popup').css('border', '5px SOLID' + bgColor);

  var div = $('<div></div>').attr('id', 'popupHead').css('border', '5px SOLID' + bgColor).css('background-color', bgColor).css('color', fgColor).append($('<b></<b>').html(popupHead));
  $('#popup').empty().append(div).append(content.attr('id', 'popupContent'));
  $('#popup').css('visibility', 'visible');
  $('#popup').css('opacity', '1');

  submited();
  endLoading();
};

var unFocus = function () {
  $('#zoneGrey').removeClass('focused');
  $('#zoneFocus').removeClass('focused');

  if (window.task != null)
    $('#' + window.task.id).click();
};


/* Groupes */

var addGroup = function () {
  var corps = $('<div></div>');

  if ($('#group').length > 0) {
    var name = $('#group').val();
    getRequest('groups.php', {
      'mode': 'add',
      'group': encodeURIComponent(name)
    }, function (data) {
      console.log(data);
      if (data.error === undefined)
        generate();
      else {
        corps.append($('<div></div>').text(data.error));
        $('#group').val(name);
      }
    });
  }

  popup('Création d\'un nouveau groupe', corps.append(
    $('<div></div>').addClass('optionCards')
      .append($('<input /><br />').attr('id', 'group').addClass('focusedInput').addClass('submitedInput'))
      .append($('<button></button>').text('Créer').attr('onClick', 'addGroup()').addClass('submitedButton'))));
};

var delGroup = function (idGroup) {
  getRequest('groups.php', {
    'mode': 'del',
    'group': idGroup,
  }, function (data) {
    if (data.status == 'ok') {
      $('#group-' + idGroup).remove();
      popupClose();
    }
    else
      console.log(data.error);
  });
};

var seeGroup = function (group, edit) {
  getRequest('groups.php', {
    'mode': 'get',
    'group': group
   }, function (data) {
    console.log(data);
    var corps = $('<div></div>');
    var groupElements = {};

    if (data[group].type == 'asso') {
      var optionCards = $('<div></div>').addClass('optionCards');
      $('<button></button>').html('<i class="fa fa-external-link" aria-hidden="true"></i> Voir sur le portail').attr('onClick', 'assoPortail("' + group + '")').appendTo(optionCards);
      $('<button></button>').html('<i class="fa fa-send" aria-hidden="true"></i> Envoyer un email à l\'asso').attr('onClick', 'assoEmail("' + group + '")').appendTo(optionCards);
      optionCards.appendTo(corps);
    }

    var div;
    $.each(data[group].subgroups, function (name, sub_group) {
      var subGroupElements = {};
      var actives = [];

      div = $('<div></div>').addClass('studentCards');

      if (sub_group.elements.length == 0) {
        $('<div></div>').addClass('subCard').attr('id', 'sub-' + name).css('color', (edit && sub_group.type == 'asso' ? '#FF0000' : '#000000'))
          .append($('<b></b>').text(sub_group.name))
          .append($('<button></button>').html('<i class="fa fa-' + (edit ? 'edit' : 'eye') + '"></i>').prop('disabled', (!edit || (edit && sub_group.type != 'custom')).on('click', edit ? function () {
            setSubGroup(group, name, 'sub-' + name);
          } : function () {}))).appendTo(corps);

        if (edit)
          return div.addClass('optionCards').append($('<button></button>').html('<i class="fa fa-remove" aria-hidden="true"></i> Supprimer ce sous-groupe vide').prop('disabled', sub_group.type != 'custom').attr('onClick', 'delSubGroup("' + group + '", "' + name + '")')).appendTo(corps);
        else
          return div.append(div.addClass('voidCard').css('margin-top', 0).text(name == 'resps' ? 'Aucun responsable' : (name == 'members' ? 'Aucun membre' : (name == 'admins' ? 'Aucun breau (il faudrait que quelqu\'un fasse la demande de passation)' : 'Sous-groupe vide')))).appendTo(corps);
      }

      $.each(sub_group.elements, function (login, infos) {
        if (!edit) {
          groupElements[login] = infos.active;
          subGroupElements[login] = infos.active;
        }

        if (infos.uv == undefined) {
          infos.login = login;
          if (edit)
            generateStudentCard(infos, undefined, group, name, sub_group.type).appendTo(div);
          else
            generateStudentCard(infos, undefined, window.get.mode == 'organiser' ? group : undefined).appendTo(div);
        }
        else {
          if (edit)
            generateUVCard(infos, undefined, undefined, group, name, sub_group.type).appendTo(div);
          else
            generateUVCard(infos, undefined, undefined, window.get.mode == 'organiser' ? group : undefined).appendTo(div);
        }
      });

      $.each(subGroupElements, function (login, active) {
        if (active == sub_group.active)
          actives.push(login);
      });

      var active = sub_group.active && window.get.mode == 'organiser' ? 'delActive' : 'addActive';
      $('<div></div>').addClass('subCard').attr('id', 'sub-' + name).css('color', (edit && sub_group.type == 'asso' ? '#FF0000' : '#000000'))
        .append($('<b></b>').text(sub_group.name))
        .append($('<button></button>').html('<i class="fa fa-' + (edit ? 'edit' : (sub_group.active && window.get.mode == 'organiser' ? 'eye-slash' : 'eye')) + '"></i>').prop('disabled', (!edit && Object.keys(actives).length == 0) || (edit && sub_group.type != 'custom')).on('click', edit ? function () {
          setSubGroup(group, name, 'sub-' + name);
        } : function () {
          window.get = {
            'mode': 'organiser'
          };
          window.get[active] = actives;

          generate();
        })).appendTo(corps);
      div.appendTo(corps);
    });

    if (data[group].subgroups.length == 0) {
      if (edit)
        corps = $('<div></div>').append($('<div></div>').addClass('optionCards').append($('<button></button>').html('<i class="fa fa-remove" aria-hidden="true"></i> Supprimer ce groupe vide').prop('disabled', data[group].type != 'custom').attr('onClick', 'delGroup("' + group + '")')));
      else
        corps = $('<div></div>').append($('<div></div>').addClass('voidCard').text('Vide'));
    }

    var optionCards = $('<div></div>').addClass('optionCards');
    if (group.type != 'others')
      $('<button></button>').html('<i class="fa fa-plus" aria-hidden="true"></i> Créer un sous-groupe').attr('onClick', 'addSubGroup("' + group + '", "' + data[group].name + '")').appendTo(optionCards);

    if (edit)
      $('<button></button>').html('<i class="fa fa-eye" aria-hidden="true"></i> Afficher le groupe').attr('onClick', 'seeGroup("' + group + '")').appendTo(optionCards);
    else
      $('<button></button>').html('<i class="fa fa-gear" aria-hidden="true"></i> Paramétrer le groupe').attr('onClick', 'seeGroup("' + group + '", true)').appendTo(optionCards);
    optionCards.appendTo(corps);

    var actives = [];
    $.each(groupElements, function (login, active) {
      if (active == data[group].active)
        actives.push(login);
    });

    var active = data[group].active && window.get.mode == 'organiser' ? 'delActive' : 'addActive';
    popup(data[group].name, corps);
    $('#popupHead').append($('<button></button>').html('<i class="fa fa-' + (edit ? 'edit' : (data[group].active && window.get.mode == 'organiser' ? 'eye-slash' : 'eye')) + '"></i>').prop('disabled', (!edit && Object.keys(actives).length == 0) || (edit && data[group].type != 'custom')).on('click', edit ? function () {
      setGroup(group, 'popupHead');
    } : function () {
      window.get = {
        'mode': 'organiser'
      };
      window.get[active] = actives;

      generate();
    }));
  });
};

var setGroup = function (idGroup, id) {
  setPopupButtons(false);

  $('#' + id + ' b').replaceWith($('<input />').addClass('focusedInput').addClass('submitedInput').val($('#' + id + ' b').text()));
  $('#' + id + ' button').first().replaceWith($('<button></button>').html('<i class="fa fa-send"></i>').addClass('submitedButton').on('click', function () {
    setPopupButtons(true);
    getRequest('groups.php', {
      'mode': 'set',
      'group': idGroup,
      'info': encodeURIComponent($('#' + id + ' input').val())
    }, function (data) {
      $('#' + id + ' input').replaceWith($('<b></b>').text($('#' + id + ' input').val()));
      $('#' + id + ' button').first().replaceWith($('<button></button>').html('<i class="fa fa-edit"></i>').on('click', function () {
        setGroup(idGroup, id);
      }));
    });
  }));

  submited();
};

var addSubGroup = function (idGroup, group) {
/*  var corps = $('<div></div>');

  if ($('#subGroup').length) {
    var name = $('#subGroup').val();
    getRequest('groups.php', {
      'mode': 'add',
      'group': idGroup,
      'sub_group': encodeURIComponent(name)
    }, function (data) {
      console.log(data);
      if (data.error === undefined)
        generate();
      else {
        corps.append($('<div></div>').text(data.error));
        $('#subGroup').val(name);
      }
    });*/

    setPopupButtons(false);

    $('<div></div>').addClass('subCard').attr('id', 'sub-create')
      .append($('<input />').addClass('focusedInput').addClass('submitedInput'))
      .append($('<button></button>').addClass('submitedButton').html('<i class="fa fa-send"></i>').on('click', function () {
        getRequest('groups.php', {
          'mode': 'add',
          'group': idGroup,
          'sub_group': encodeURIComponent($('#sub-create input').last().val())
        }, function (data) {
          seeGroup(idGroup, true);
        });
      }))
      .insertBefore($('.optionCards').last().before());

    submited();
    /*
  }
  popup('Création d\'un sous-groupe pour le groupe ' + group, corps
    .append($('<input />').attr('id', 'subGroup').addClass('focusedInput').addClass('submitedInput'))
    .append($('<button></button>').text('Créer').attr('onClick', 'addSubGroup("' + idGroup + '")').addClass('submitedButton')));*/
};

var delSubGroup = function (idGroup, idSubGroup) {
  getRequest('groups.php', {
    'mode': 'del',
    'group': idGroup,
    'sub_group': idSubGroup,
  }, function (data) {
    if (data.status == 'ok')
      seeGroup(idGroup, true);
    else
      console.log(data.error);
  });
};

var setSubGroup = function (idGroup, idSubGroup, id) {
  setPopupButtons(false);

  $('#' + id + ' b').replaceWith($('<input />').addClass('focusedInput').addClass('submitedInput').val($('#' + id + ' b').text()));
  $('#' + id + ' button').first().replaceWith($('<button></button>').html('<i class="fa fa-send"></i>').addClass('submitedButton').on('click', function () {
    setPopupButtons(false);

    getRequest('groups.php', {
      'mode': 'set',
      'group': idGroup,
      'sub_group': idSubGroup,
      'info': encodeURIComponent($('#' + id + ' input').val())
    }, function (data) {
      $('#' + id + ' input').replaceWith($('<b></b>').text($('#' + id + ' input').val()));
      $('#' + id + ' button').first().replaceWith($('<button></button>').html('<i class="fa fa-edit"></i>').on('click', function () {
        setSubGroup(idGroup, idSubGroup, id);
      }));

      setPopupButtons(true);
    });
  }));

  submited();
};

var addToGroup = function (element, text) {
  getRequest('groups.php', {
    'mode': 'get',
   }, function (data) {
    console.log(data);
    var corps = $('<div></div>');

    var groups = $('<select></select>').attr('id', 'selectedGroup').on('change', function () {
      $($( this ).data('selected')).css('display', 'none');
      $('#sub_' + this.value).css('display', 'block');
      $( this ).data('selected', '#sub_' + this.value);

      if ($('#sub_' + this.value).prop('disabled'))
        $('#sub1').click();
      else
        $('#sub0').click();
    });
    var subgroups = $('<div></div>');
    $.each(data.groups, function (name, group) {
      if (name == 'others')
        return;

      $('<option></option>').attr('value', name).text(group.name).appendTo(groups);

      var hidden = $('<select></select>').css('display', 'none').prop('disabled', true).attr('id', 'sub_' + name);
      var option;
      $.each(group.subgroups, function (subname, subgroup) {
        option = $('<option></option>').attr('value', subname).text(subgroup.name);

        if (subgroup.type == 'asso')
          option.prop('disabled', true);
        else
          hidden.prop('disabled', false);

        option.appendTo(hidden);
      });
      hidden.appendTo(subgroups);
    });

    $(groups).data('selected', '#' + $(subgroups).children().first().css('display', 'block').attr('id'));

    groups.appendTo(corps);
    $('<input></input').attr('type', 'radio').attr('id', 'sub0').attr('name', 'sub').prop("checked", true).val(0).appendTo(corps);
    subgroups.attr('onClick', '$("#sub0").prop("checked", true)').appendTo(corps);
    $('<input></input').attr('type', 'radio').attr('id', 'sub1').attr('name', 'sub').val(1).appendTo(corps);
    $('<input></input').attr('id', 'newSubGroup').attr('onClick', '$("#sub1").prop("checked", true)').attr('placeholder', 'Nouveau sous-groupe').appendTo(corps);
    $('<input></input').attr('id', 'info').attr('placeholder', 'Commentaire').appendTo(corps);
    $('<button></button>').html('<i class="fa fa-send" aria-hidden="true"></i> Ajouter').on('click', function () {
      var group = $('#selectedGroup').val();
      var subGroup = $('#sub_' + group).val();
      var createSubGroup = $('input[name=sub]:checked').val();
      var info = $('#info').val();

      if (createSubGroup == 1)
        subGroup = $('#newSubGroup').val();

      getRequest('groups.php', {
        'mode': 'add',
        'group': group,
        'sub_group': encodeURIComponent(subGroup),
        'createSubGroup': createSubGroup,
        'element': element,
        'info': encodeURIComponent(info)
      }, function (data) {
        if (data.status == 'ok')
          popupClose();
        else
          console.log(data.error);
      });
    }).appendTo(corps);
    popup(text, corps);
  });
};

var delFromGroup = function (idGroup, idSubGroup, element) {
  getRequest('groups.php', {
    'mode': 'del',
    'group': idGroup,
    'sub_group': idSubGroup,
    'element': encodeURIComponent(element),
  }, function (data) {
    if (data.status == 'ok')
      seeGroup(idGroup, true);
    else
      console.log(data.error);
  });
};

var setToGroup = function (idGroup, idSubGroup, element, text, id) {
  setPopupButtons(false);

  $('#' + id + ' .infosCard span').replaceWith($('<input />').addClass('focusedInput').addClass('submitedInput').val($('#' + id + ' .infosCard span').text()));
  $('#' + id + ' .optionsCard button').first().replaceWith($('<button></button>').html('<i class="fa fa-send"></i>').addClass('submitedButton').on('click', function () {
    setPopupButtons(false);

    getRequest('groups.php', {
      'mode': 'set',
      'group': idGroup,
      'sub_group': idSubGroup,
      'element': element,
      'info': encodeURIComponent($('#' + id + ' .infosCard input').val())
    }, function (data) {
      $('#group-' + idGroup + ' button').val($('#group-' + idGroup + ' button').val().replace(element, $('#' + id + ' .infosCard input').val()));
      $('#' + id + ' .infosCard input').replaceWith($('<span></span>').text($('#' + id + ' .infosCard input').val()));
      $('#' + id + ' .optionsCard button').first().replaceWith($('<button></button>').html('<i class="fa fa-edit"></i>').attr('disabled',  type == 'asso').on('click', function () {
        setToGroup(idGroup, idSubGroup, element, text, id);
      }));

      setPopupButtons(true);
    });
  }));

  submited();
};

var addActive = function (element) {
  window.get.addActive = [element];
  delete window.get.delActive;
  generate();
};

var delActive = function (element) {
  window.get.delActive = [element];
  delete window.get.addActive;
  generate();
};

var seeStudent = function (login, info) {
  if (window.get.mode == 'comparer' || window.get.mode == 'modifier')
    window.get = { 'mode': 'comparer' };
  else
    window.get = { 'mode': 'classique' };

  window.get.login = login;


  if (info != undefined)
    window.get.info = info;

  generate();
};

var seeUV = function (uv) {
  if (window.get.mode == 'comparer')
    window.get = { 'mode': 'comparer' };
  else if (window.get.mode == 'modifier')
    window.get = { 'mode': 'modifier' };
  else if (window.get.mode == 'semaine')
    window.get = { 'mode': 'semaine' };
  else
    window.get = { 'mode': 'classique' };

  window.get.uv = uv;

  generate();
};

var seeUVInformations = function (task) {
  loading();
  getRequest('lists.php', {
    'idUV': task.idUV
  }, function (data) {
    var popupHead = 'Liste des ' + task.nbrEtu + ' étudiants en ' + (task.type == 'D' ? 'TD' : (task.type == 'T' ? 'TP' : 'cours')) + ' de ' + task.subject + ' de ' + task.timeText.replace('-', ' à ');
    var corps = $('<div></div>');
    var optionCards = $('<div></div>').addClass('optionCards');
    $('<button></button>').html('<i class="fa fa-external-link" aria-hidden="true"></i> Moodle').attr('onClick', 'uvMoodle("' + task.subject + '")').appendTo(optionCards);
    $('<button></button>').html('<i class="fa fa-external-link" aria-hidden="true"></i> UVWeb').attr('onClick', 'uvWeb("' + task.subject + '")').appendTo(optionCards);
    optionCards.appendTo(corps);
    var div = $('<div></div>').addClass('studentCards');
    data.students.forEach(function (student) {
      generateStudentCard(student).appendTo(div);
    });
    div.appendTo(corps);
    popup(popupHead, corps, task.bgColor, task.fgColor);
  });
};

var seeOthers = function (uv, type, idUV) {
  window.get = {
    'mode': 'modifier',
    'uv': uv,
    'type': type,
    'idUV': idUV
  };

  generate();
};

var uvWeb = function (uv) {
  window.open('https://assos.utc.fr/uvweb/uv/' + uv);
};

var uvMoodle = function (uv) {
  window.open('http://moodle.utc.fr/course/search.php?search=' + uv);
};

var changeColor = function(idUV, color) {
  getRequest('colors.php', {
    'idUV': idUV,
    'color': color.substr(1)
  }, function (data) {
    generate();
  });
};


/* Trombi */

var search = function () {
  popup(
    $('<div></div>')
      .append($('<div></div>').text('Chercher un étudiant ou une UV pour l\'ajouter'))
      .append($('<input />').attr('id', 'addTabText').attr('onInput', 'checkSearch(this.value)'))
      .append($('<button></button>').attr('id', 'searchButton').attr('onClick', 'printSearch(this.value)').text('Chercher')).html(),
    $('<div></div>')
      .append($('<div></div>').addClass('studentCardsText'))
      .append($('<div></div>').addClass('studentCards'))
      .append($('<div></div>').addClass('uvCardsText'))
      .append($('<div></div>').addClass('uvCards'))
      .append($('<div></div>').addClass('cardButtons')));

  $("#addTabText").keyup(function (event) {
    if(event.keyCode == 13){
      if (!$('#searchButton').prop('disabled'))
        printSearch();
    }
  });

  $('#searchButton').prop('disabled', true);

  setTimeout(function () { // La fonction ne marche pas sans Timeout..
    $("#addTabText").focus();
  }, 100);
};

var checkSearch = function (input) {
  var text = input.replace(/\s+/g, ' ').replace(/^\s+/g, '').replace(/(\s.+)\s$/g, '$1');
  $('#addTabText').val(text);

  $('#searchButton').prop('disabled', text.length < 2);
};

var printSearch = function (begin) {
  loading();

  if (begin == undefined || begin == '')
    begin = 0;
    console.log({
      'search': $('#addTabText').val().replace(/^\s+|\s+$/g, '').replace(/_/g, '').replace(/\s/, '%\\_%\\'),
      'begin': begin,
      'nbr': 50
    });
  $('#searchButton').prop('disabled', true);
  getRequest('lists.php', {
    'search': $('#addTabText').val().replace(/^\s+|\s+$/g, '').replace(/_/g, '').replace(/\s/, '%\\_%\\'),
    'begin': begin,
    'nbr': 50
  }, function(data) {
    endLoading();
    $('.studentCards').empty();
    $('.studentCardsText').text(data.students.length + ' étudiant' + (data.students.length > 1 ? 's' : '') + ' trouvé' + (data.students.length > 1 ? 's' : ''));
    data.students.forEach(function (student) {
      $('.studentCards').append(generateStudentCard(student, 'Ajouté.e depuis le trombi'));
    });

    $('.uvCards').empty();
    $('.uvCardsText').text(data.uvs.length + ' UV' + (data.uvs.length > 1 ? 's' : '') + ' trouvée' + (data.uvs.length > 1 ? 's' : ''));
    data.uvs.forEach(function (uv) {
      $('.uvCards').append(generateUVCard(uv, data.infos.uvs));
    });

    $('.cardButtons').empty();
    if (data.infos.begin != 0 || data.infos.more)
      $('.cardButtons')
        .append($('<button></button>').text('Précédent').attr('onClick', 'printSearch(' + (data.infos.begin - 50) + ')').prop('disabled', data.infos.begin == 0))
        .append($('<button></button>').text('Suivant').attr('onClick', 'printSearch(' + (data.infos.begin + 50) + ')').prop('disabled', !data.infos.more));

    $('#popup').scrollTop(0);
  });
};


/* Outils */

var exportDownload = function (type) {
  if (type) {
    if (type == 'ical') {

    }
    else if (type == 'pdf') {
      popup('Obtenir sous format PDF', $('<div></div>').addClass('parameters')
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck0').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck0').text('Afficher le lundi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck1').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck1').text('Afficher le mardi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck2').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck2').text('Afficher le mercredi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck3').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck3').text('Afficher le jeudi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck4').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck4').text('Afficher le vendredi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck5').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck5').text('Afficher le samedi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'pdfCheck6').prop('checked', true)).append($('<label></label><br />').attr('for', 'pdfCheck6').text('Afficher le dimanche'))
        .append($('<input></input>').addClass('focusedInput').addClass('submitedInput').attr('id', 'pdfTitle').val($('#title').text()))
        .append($('<input></input>').addClass('submitedInput').attr('id', 'pdfName').val('edt_' + window.get.mode + '_' + (window.get.login ? window.get.login : window.sessionLogin)))
        .append($('<button></button>').addClass('submitedButton').text('Générer et télécharger mon emploi du temps').attr('onClick', 'getPDF()'))
      );
    }
    else if (type == 'img') {
      popup('Obtenir sous format image', $('<div></div>').addClass('parameters')
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck0').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck0').text('Afficher le lundi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck1').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck1').text('Afficher le mardi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck2').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck2').text('Afficher le mercredi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck3').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck3').text('Afficher le jeudi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck4').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck4').text('Afficher le vendredi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck5').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck5').text('Afficher le samedi'))
        .append($('<input></input>').attr('type', 'checkbox').attr('id', 'imgCheck6').prop('checked', true)).append($('<label></label><br />').attr('for', 'imgCheck6').text('Afficher le dimanche'))
        .append($('<select></select>').attr('id', 'imgType')
          .append($('<option></option>').val('png').text('PNG'))
          .append($('<option></option>').val('jpeg').text('JPEG')))
        .append($('<button></button>').addClass('submitedButton').text('Générer mon emploi du temps').attr('onClick', 'getImg()'))
        .append($('<div></div>').attr('id', 'generatedImg'))
      );
    }
  }
  else
    popup('Exporter/Télécharger', $('<div></div>').addClass('parameters')
      .append($('<button></button>').attr('onClick', 'exportDownload("ical")').text('Obtenir son calendrier sous format iCal (.ics)').prop('disabled', true))
      .append($('<button></button>').attr('onClick', 'exportDownload("pdf")').text('Obtenir son calendrier sous format PDF (.pdf)'))
      .append($('<button></button>').attr('onClick', 'exportDownload("img")').text('Obtenir son calendrier sous format image (.png/.jpg)'))
      .append($('<button></button>').attr('onClick', 'window.location.href = "http://wwwetu.utc.fr/sme/EDT/' + window.sessionLogin + '"').text('Obtenir son calendrier sous format SME (mail reçu)'))
      .append($('<button></button>').attr('onClick', 'window.location.href = "https://' + window.location.hostname + '/emploidutemps' + '/ressources/pdf/alternances.pdf"').text('Télécharger le calendrier des alternances'))
      .append($('<button></button>').attr('onClick', 'window.location.href = "https://' + window.location.hostname + '/emploidutemps' + '/ressources/pdf/infosRentree.pdf"').text('Télécharger l\'info rentrée'))
    );

/*  <div onClick="parameters()" style="cursor: pointer" id="popupHead">Exporter/Télécharger</div>
    <div class="parameters" style="text-align: center;">
      <button onClick="parameters(\'ical\');">Obtenir son calendrier sous format iCal (.ics)</button>
      <button onClick="parameters(\'pdf\');">Obtenir son calendrier sous format PDF (.pdf)</button>
      <button onClick="parameters(\'img\');">Obtenir son calendrier sous format image (.png/jpg)</button>
      <button onClick="window.location.href = \'http://wwwetu.utc.fr/sme/EDT/', $_SESSION['login'], '.edt\';"></button>
      <button onClick="window.location.href = \'https://\' + window.location.hostname + \'/emploidutemps\' + \'/ressources/pdf/alternances.pdf\';"></button>
      <button onClick="window.location.href = \'https://\' + window.location.hostname + \'/emploidutemps\' + \'/ressources/pdf/infosRentree.pdf\';"></button>
    </div>*/
};

var getImg = function () {
  var headers = $('.calendar-headers div');
  var days = $('.calendar-main-body .days');
  var length = window.headers.length;
  var displays = [];
  var hidden = 0;
  var type = $('#imgType').val();
  doc = new jsPDF('l', 'mm', [297, 210]);

  for (var i = 0; i < length; i++) {
    displays[i] = $(headers[i]).css('display');

    if ($('#imgCheck' + i).prop('checked')) {
      $(headers[i]).css('display', 'block');
      $(days[i * window.sides]).css('display', 'block');

      if (window.sides == 2)
        $(days[(i * window.sides) + 1]).css('display', 'block');
    }
    else {
      $(headers[i]).css('display', 'none');
      $(days[i * window.sides]).css('display', 'none');

      if (window.sides == 2)
        $(days[(i * window.sides) + 1]).css('display', 'none');
      hidden += 1;
    }
  }

  var calendar = $('#calendar-container');
  calendar.css('width', 1032 + 'px').addClass('calendar-exported');

  html2canvas(calendar[0], { onrendered: function(canvas) {
    if (type == 'jpeg') {
      var ctx = canvas.getContext('2d')
      var imgData=ctx.getImageData(0,0,canvas.width,canvas.height);
      var data=imgData.data;
      for(var i=0;i<data.length;i+=4){
          if(data[i+3]<255){
              data[i]=255;
              data[i+1]=255;
              data[i+2]=255;
              data[i+3]=255;
          }
      }
      ctx.putImageData(imgData,0,0);
    }

    for (var i = 0; i < length; i++) {
      $(headers[i]).css('display', displays[i]);
      $(days[i * window.sides]).css('display', displays[i]);

      if (window.sides == 2)
        $(days[(i * window.sides) + 1]).css('display', displays[i]);
    }

    calendar.removeClass('calendar-exported');
    setCalendar(window.focusedDay);
    $('#generatedImg').html('<img src="' + canvas.toDataURL('image/' + type || 'png', 1.0) + '">');
  }});
};

var getPDF = function () {
  var headers = $('.calendar-headers div');
  var days = $('.calendar-main-body .days');
  var length = window.headers.length;
  var displays = [];
  var hidden = 0;
  doc = new jsPDF('l', 'mm', [297, 210]);

  doc.text($('#pdfTitle').val(), 148, 14, null, null, 'center');
  for (var i = 0; i < length; i++) {
    displays[i] = $(headers[i]).css('display');

    if ($('#pdfCheck' + i).prop('checked')) {
      $(headers[i]).css('display', 'block');
      $(days[i * window.sides]).css('display', 'block');

      if (window.sides == 2)
        $(days[(i * window.sides) + 1]).css('display', 'block');
    }
    else {
      $(headers[i]).css('display', 'none');
      $(days[i * window.sides]).css('display', 'none');

      if (window.sides == 2)
        $(days[(i * window.sides) + 1]).css('display', 'none');
      hidden += 1;
    }
  }

  var calendar = $('#calendar-container');
  calendar.css('width', 1036 + 'px').addClass('calendar-exported');

  html2canvas(calendar[0], { onrendered: function(canvas) {
    doc.addImage(canvas.toDataURL('image/png', 1.0), 'PNG', 12 + (hidden * 18), 26);

    for (var i = 0; i < length; i++) {
      $(headers[i]).css('display', displays[i]);
      $(days[i * window.sides]).css('display', displays[i]);

      if (window.sides == 2)
        $(days[(i * window.sides) + 1]).css('display', displays[i]);
    }

    calendar.removeClass('calendar-exported');
    setCalendar(window.focusedDay);
    doc.save($('#pdfName').val() + '.pdf');
  }});
};


/* Echanges */

var exchange = function (get) {
  getRequest('exchange.php', get, function(data) {
    popup(data);
  });
};

var askForExchange = function (idUV, idUV2) {
  exchange({
    'idUV': idUV,
    'idUV2': idUV2
  });
};
/*
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

*/
var getFgColor = function (bgColor) {
  if (bgColor == null)
    return null;

  if ((((parseInt(bgColor.substr(1, 2), 16) * 299) + (parseInt(bgColor.substr(3, 2), 16) * 587) + (parseInt(bgColor.substr(5, 2), 16) * 114))) > 127000)
    return '#000000';
  else
    return '#FFFFFF';
};


/* Génération */
  /*
  popup('Changement de l\'information concernant ' + text, $('<div></div>')
    .append($('<input />').attr('id', 'info').val(info).addClass('focusedInput').addClass('submitedInput'))
    .append($('<button></button>').text('Modifier').on('click', function () {
      getRequest('groups.php', {
        'mode': 'del',
        'group': idGroup,
        'sub_group': idSubGroup,
        'element': element
      }, function (data) {
        getRequest('groups.php', {
          'mode': 'add',
          'group': idGroup,
          'sub_group': idSubGroup,
          'element': element,
          'info': $('#info').val()
        }, function (data) {
            seeGroup(idGroup);
        });
      });
    }).addClass('submitedButton')));*/



var generateStudentCard = function (infos, info, idGroup, idSubGroup, type) {
  var text = infos.firstname + ' ' + infos.surname;
  var id = 'card-' + infos.login + (idSubGroup ? '-' + idSubGroup : '');
  var option;

  if (idGroup && idSubGroup && type) {
    option = $('<div></div>').addClass('optionsCard')
      .append($('<button></button>').html('<i class="fa fa-edit"></i>').attr('disabled',  type == 'asso').on('click', function () {
        setToGroup(idGroup, idSubGroup, infos.login, text, id);
      }))
      .append($('<button></button>').html('<i class="fa fa-remove"></i>').attr('disabled',  type == 'asso').attr('onClick', 'delFromGroup("' + idGroup + '", "' + idSubGroup + '", "' + infos.login + '", "' + infos.info + '")'));
  }
  else if (window.get.mode == 'organiser' && infos.info) {
    option = $('<div></div>').addClass('optionsCard')
      .append($('<button></button>').html('<i class="fa fa-' + (infos.active ? 'eye-slash' : 'eye') + '"></i>').attr('disabled', infos.login == window.sessionLogin || infos.extern).attr('onClick', (infos.active ? 'delActive' : 'addActive') + '("' + infos.login + '"); seeGroup("' + idGroup + '")'))
      .append($('<button></button>').html('<i class="fa fa-plus"></i>').attr('disabled', infos.login == window.sessionLogin || infos.extern).attr('onClick', 'addToGroup("' + infos.login + '", "' + text + '")'));
  }
  else {
    option = $('<div></div>').addClass('optionsCard')
      .append($('<button></button>').html('<i class="fa fa-eye"></i>').attr('disabled', infos.login == window.sessionLogin || infos.extern).attr('onClick', 'seeStudent("' + infos.login + (info == undefined ? '' : '", "' + info) + '")'))
      .append($('<button></button>').html('<i class="fa fa-plus"></i>').attr('disabled', infos.login == window.sessionLogin || infos.extern).attr('onClick', 'addToGroup("' + infos.login + '", "' + text + '")'));
  }

  var card = $('<div></div>').addClass('studentCard').attr('id', id)
    .append($('<div></div>').addClass('imgCard')
      .append($('<i class="fa fa-4x fa-user-o"></i>').css('padding-top', '3px').css('padding-left', '1px'))
      .append($('<img />').attr('src', 'https://demeter.utc.fr/pls/portal30/portal30.get_photo_utilisateur?username=' + infos.login)))
    .append($('<div></div>').addClass('infosCard')
      .append($('<b></b>').text(text)).append($('<br />'))
      .append($('<span></span>').text(infos.info === undefined ? infos.semester + ' - ' + infos.login : infos.info)).append($('<br />'))
      .append($('<a></a>').attr('href', 'mailto:' + infos.email).text(infos.email)))
    .append(option);

  if ((infos.active || infos.login == window.sessionLogin) && window.active[infos.login])
    card.css('background-color', window.active[infos.login] + 'CC').css('color', getFgColor(window.active[infos.login]));

  return card;
};

var generateUVCard = function (infos, uvs, info, idGroup, idSubGroup, type) {
  var id = 'card-' + infos.uv + (idSubGroup ? '-' + idSubGroup : '');
  var option;

  if (idGroup && idSubGroup && type) {
    option = $('<div></div>').addClass('optionsCard')
      .append($('<button></button>').html('<i class="fa fa-edit"></i>').attr('disabled',  type == 'asso').on('click', function () {
        setToGroup(idGroup, idSubGroup, infos.uv, infos.uv, id);
      }))
      .append($('<button></button>').html('<i class="fa fa-remove"></i>').attr('disabled',  type == 'asso').attr('onClick', 'delFromGroup("' + idGroup + '", "' + idSubGroup + '", "' + infos.uv + '")'));
  }
  else if (window.get.mode == 'organiser' && infos.info) {
    option = $('<div></div>').addClass('optionsCard')
      .append($('<button></button>').html('<i class="fa fa-' + (infos.active ? 'eye-slash' : 'eye') + '"></i>').attr('onClick', (infos.active ? 'delActive' : 'addActive') + '("' + infos.uv + '"); seeGroup("' + idGroup + '")'))
      .append($('<button></button>').html('<i class="fa fa-plus"></i>').attr('onClick', 'addToGroup("' + infos.uv + '", "' + infos.uv + '")'));
  }
  else {
    option = $('<div></div>').addClass('optionsCard')
      .append($('<button></button>').html('<i class="fa fa-eye"></i>').attr('onClick', 'seeUV("' + infos.uv + '")'))
      .append($('<button></button>').html('<i class="fa fa-plus"></i>').attr('onClick', 'addToGroup("' + infos.uv + '", "' + infos.uv + '")'));
  }

  var card = $('<div></div>').addClass('uvCard').attr('id', id)
    .append($('<div></div>').addClass('imgCard')
      .append($('<i class="fa fa-4x fa-book"></i>').css('padding-top', '3px').css('padding-left', '1px')))
    .append($('<div></div>').addClass('infosCard').css('padding-left', '5px')
      .append($('<b></b>').text(infos.uv))
      .append($('<br />'))
      .append($('<span></span>').text(uvs == undefined ? infos.info : (uvs.search(infos.uv) != -1 ? 'UV suivie' : ''))))
    .append(option);

    if (infos.active && window.active[infos.uv])
      card.css('background-color', window.active[infos.uv] + 'CC').css('color', getFgColor(window.active[infos.uv]));

    return card;
};

var generateTitle = function (title) {
  $('#title').text(title);
};

var generateWeeks = function (weeks, week) {
  console.log(weeks)
  if (window.get.mode == 'semaine') {
    $('#mode_semaine').prop('checked', true);
    $('#mode_week').text(' Semaine du ' + week.split('-')[2] + '/' + week.split('-')[1]);
  }
  else if (window.get.mode == 'comparer')
    $('#mode_comparer').prop('checked', true);
  else if (window.get.mode == 'modifier')
    $('#mode_modifier').prop('checked', true);
  else if (window.get.mode == 'organiser') {
    $('#mode_organiser').prop('checked', true);
    $('#mode_week').text(' Semaine du ' + week.split('-')[2] + '/' + week.split('-')[1]);
  }
  else
    $('#mode_classique').prop('checked', true);

  if (!weeks.before)
    $('#before').prop('disabled', true);
  else
    $('#before').prop('disabled', false).attr('onClick', 'window.get.week="' + weeks.before + '"; generate()');

  if (!weeks.actual)
    $('#actual').prop('disabled', true);
  else
    $('#actual').prop('disabled', false).attr('onClick', 'window.get.week="' + weeks.actual + '"; generate()');

  if (!weeks.after)
    $('#after').prop('disabled', true);
  else
    $('#after').prop('disabled', false).attr('onClick', 'window.get.week="' + weeks.after + '"; generate()');
};

var generateSubMenu = function (tabs, id) {
  var submenu = $('#' + id + 's');
  submenu.empty();

  if ($.isEmptyObject(tabs))
    $('#affichage_' + id + 's').prop('checked', false).css('display', 'none');
  else
    $('#affichage_' + id + 's').prop('checked', true).css('display', 'block');

  var div, menu, text;
  $.each(tabs, function (key, tab) {
    div = $('<div></div>').attr('id', id + '-' + key);
    menu = $('<button></button>');

    if (tab.name == undefined)
      text = tab.text;
    else
      text = tab.name;

    if (tab.type == 'asso')
      text = '<i class="fa fa-address-book" aria-hidden="true"></i> ' + text;
    else if (tab.type == 'custom')
      text = '<i class="fa fa-user-circle" aria-hidden="true"></i> ' + text;
    else if (tab.type == 'others')
      text = '<i class="fa fa-eye" aria-hidden="true"></i> ' + text;
    else if (tab.type == 'new')
      text = '<i class="fa fa-plus" aria-hidden="true"></i> ' + text;

    if (key == 'me')
      menu.attr('id', 'myTab');

    if (tab.active)
      menu.addClass('active');
    else if (tab.partialyActive)
      menu.addClass('partialyActive');

    if (tab.disabled)
      menu.prop('disabled', true);

    if (tab.options == undefined) {
      if (tab.action == undefined) {
        menu.on('click', function () {
          addGet(tab.get);

          generate();
        });
      }
      else {
        if (tab.action == 'paramGroup')
          menu.on('click', function () {
            paramGroup(tab);
          });
        else
          menu.attr('onClick', tab.action);
      }

      menu.html(text).appendTo(div);
    }
    else {
      menu.html(text + '<i class="fa fa-caret-up" aria-hidden="true"></i>').on('click', function () {
        if ($( this ).children().last().hasClass('fa-caret-up')) {
          $( this ).children().last().removeClass('fa-caret-up').addClass('fa-caret-down');
          $( this ).next().css('height', 'auto');
        }
        else {
          $( this ).children().last().removeClass('fa-caret-down').addClass('fa-caret-up');
          $( this ).next().css('height', 0);
        }
      });

      var container = $('<div></div>').addClass('options');

      $.each(tab.options, function (value, option) {
        var button = $('<button></button>').html(option.text).addClass('option');

        if (option.action == undefined) {
          button.on('click', function () {
            if (tab.get != undefined)
              addGet(tab.get);
            if (option.get != undefined)
              addGet(option.get);

            generate();
          });
        }
        else {
          if (button.action == 'paramGroup')
            button.on('click', function () {
              paramGroup(tab);
            });
          else
            button.attr('onClick', option.action);
        }

        if (option.active || (tab.active && value != 'more'))
          button.addClass('active');
        else if (option.partialyActive)
          button.addClass('partialyActive');

        if (option.disabled)
          button.prop('disabled', true);

        button.appendTo(container);
      });

      menu.appendTo(div);
      div.append(container);
    }

    div.appendTo(submenu);

    if (tab.separate)
      $('<div></div>').addClass('separatorTab').appendTo(submenu);
  });
}

var generatePrinted = function (groups) {
  $('#printed').empty();
  $('#printedTools').empty();

  if (window.get.mode == 'organiser') {
    $('#affichage_printed').css('display', 'block');

    if (window.active && Object.keys(window.active).length > 1)
      $('#printedText').text(Object.keys(window.active).length + ' affichés:');
    else
      $('#printedText').text('1 seul affiché:');

    $('<div></div>').attr('id', 'printed-' + window.sessionLogin).append($('<button></button>').html('<span>Moi</span>').css('background-color', window.active[window.sessionLogin] || '#222222').css('color', getFgColor(window.active[window.sessionLogin] || '#222222'))).appendTo($('#printed'));

    $.each(window.active, function (element, color) {
      if (element != window.sessionLogin)
      $('<div></div>').attr('id', 'printed-' + element).append($('<button></button>').html('<span>' + element + '</span><i class="fa fa-times" aria-hidden="true"></i>').css('background-color', window.active[element] || '#222222').css('color', getFgColor(window.active[element] || '#222222')).on('click', function () {
        window.get.delActive = [element];

        generate();
      })).appendTo($('#printed'));
    });

    var elem;
    var emails = {};
    $.each(groups, function (name, group) {
      if (!group.partialyActive)
        return;
      $.each(group.subgroups, function (sub_name, sub_group) {
        $.each(sub_group.elements, function (element, infos) {
          if (!infos.active && element != window.sessionLogin)
            return;

          if (infos.email)
            emails[element] = infos.email;

          elem = $('#printed-' + element).children();

          elem.children().first().text(infos.firstname + ' ' + infos.surname);
          if (name != 'others')
            $('<div></div>').html('<i class="fa fa-angle-right" aria-hidden="true"></i> ' + group.name + ' - ' + sub_group.name + (infos.info != '' ? ' (' + infos.info + ')' : '')).appendTo(elem);
        });
      });
    });

    $('<button></button>').on('click', function () {
      var mailto = '';

      $.each(emails, function (element, email) {
        mailto += ',' + email;
      });

      window.open('mailto:' + mailto.substr(1));
    }).html('<i class="fa fa-send" aria-hidden="true"></i> Envoyer un mail aux étudiant.e.s affiché.e.s ').prop('disabled', Object.keys(emails).length == 0).appendTo($('#printedTools'));
  }
  else {
    $('#affichage_printed').css('display', 'none');
    var text = window.get.login ? groups.others.subgroups.students.elements[window.get.login].firstname + ' ' + groups.others.subgroups.students.elements[window.get.login].surname : (window.get.uv ? groups.others.subgroups.uvs.elements[window.get.uv].uv : '');

    if (window.get.login || window.get.uv)
      $('<button></button>').attr('onClick', 'addToGroup("' + (window.get.login || window.get.uv) + '")').html('<i class="fa fa-plus" aria-hidden="true"></i> Ajouter ' + text).appendTo($('#printedTools'));

    if (window.get.login)
      $('<button></button>').on('click', function () {
        window.open('mailto:' + groups.others.subgroups.students.elements[window.get.login].email);
      }).html('<i class="fa fa-send" aria-hidden="true"></i> Envoyer un mail à ' + text).appendTo($('#printedTools'));
  }
}

var cardClick = function (task) {
  var end = (window.get.mode === 'semaine' || window.get.mode === 'organiser' ? 629 : 588);

  $('#nav').removeClass('see');
  $('#parameters').removeClass('see');

  if (window.task == null) {
    if (task.top + 150 > end) // Détecter si la tache ne dépasse pas le calendrier en s'ouvrant
      $('#' + task.id).css('top', end - 150);
    $('#' + task.id).css('left', -2).css('height', 150).css('width', 135).addClass('focus');
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
    $('#' + task.id).css('left', -2).css('height', 150).css('width', 135).addClass('focus');
    $('#' + task.id + ' .interraction').css('display', 'block');

    $('#zoneGrey').addClass('focused');
    $('#zoneFocus').addClass('focused');

    window.task = task;
  }
}

var cardRoomClick = function (task) {
  var toAdd = (task.subject > 1 ? 's' : '');
  var popupHead = task.subject + ' salle' + toAdd + ' disponible' + toAdd + (task.timeText == 'Journée' ? ' toute la journée' : (' vers ' + Math.ceil(task.startTime) + 'h pour ' + task.timeText));
  var table = $('<table></table').css('padding', '1%').css('width', '100%');

  var rooms, tr;
  for (key in task.description) {
    rooms = task.description[key]
    tr = $('<tr></tr>');
    $('<td></td>').css('width', '15%').text('Salle' + (rooms.length == 1 ? '' : 's') + ' de ' + (key == 'D' ? 'TD' : (key == 'C' ? 'cours' : 'TP'))).appendTo(tr);
    $('<td></td>').css('width', '85%').text(rooms.join(', ')).appendTo(tr);
    tr.appendTo(table);
  }

  popup($('<div></div>').text(popupHead), $('<div></div>').append(table), task.bgColor, task.fgColor);
}

var generateCards = function (schedulerTasks, tasks, day, sides, uvs) {
  var passed = [];
  var toPass = [];
  var nbrPassed = 0;
  var nbrSameTime = 1;
  var div = $('<div></div>');
  var button = $('<button/>');

  var card, style;
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

          if ((window.get.mode == 'comparer' || window.get.mode == 'modifier') && task.idUV == toCompare.idUV)
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

        task.width = 133 / ((sides * nbrSameTime) + (nbrSameTime == 1 && task.week != undefined) + (sides == 2 && nbrSameTime == 1 && task.week != undefined)) - ((sides * nbrSameTime == 2) || (nbrSameTime == 1 && task.week != undefined));
        task.left = (group.side == undefined ? 0 : ((group.side - 1) * 69)) - sides + (nbrPassed * task.width) + ((nbrSameTime == 1 && task.week == 'B') * 66 / sides);
/*
        if (card.hasClass('sameCard')) {
          if (window.get.mode == 'comparer') {
            if (group.side == 1) {
              if (task.week == undefined) {
                task.width = 133;
                task.left = -1;
              }
              else {
                task.width = 66;
                task.left = 33;
              }
            }
            else
            return;
          }
          else {
            card.addClass('sameCard' + group.side);
            task.width += 2;

            if (group.side == 2)
              task.left -= 2;
          }
        }
*/
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

              if (window.get.mode_type === null) {
                option.clone().html("<i class='fa fa-calendar-o' aria-hidden='true'></i> Voir l'edt de l'UV").on('click', function() { seeUV(task.subject); }).appendTo(interraction);
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
            option.clone().html("<i class='fa fa-calendar-o' aria-hidden='true'></i> Voir l'edt de l'UV").on('click', function() { seeUV(task.subject); }).appendTo(interraction);

            if (uvs.search(task.subject) != -1)
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
}

var generateCalendar = function(tasks, sides, uvs) {
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
    if (window.get.mode === 'semaine' || window.get.mode === 'organiser') {
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
    if (window.get.mode === 'semaine' || window.get.mode === 'organiser') {
      if (currentDay == -1 || i > hour)
        classHour = 'futureHour';
      else if (currentDay == 7 || i < hour)
        classHour = 'passedHour';
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
  if (window.get.mode === 'semaine' || window.get.mode === 'organiser' || window.get.mode_type === 'rooms') {
    if (window.get.mode === 'semaine' || window.get.mode === 'organiser') {
      if (classHour == 'currentHour')
      classHour = 'futureHour';
    }
    else
    classHour = '';

    div.clone().text('').addClass(classHour).addClass('allDay').appendTo(scheduleTimeline);
    gridColumnElement[0].append(div.clone().addClass('allDay').addClass('calendar-cell' + '10'));
  }

  // On peuple l'affichage
  for (var j = 0; j < window.headers.length * sides; j++) {
    var schedulerTasks = div.clone().addClass('calendar-tasks');
    generateCards(schedulerTasks, tasks, j / sides, sides, uvs);

    var grid = gridColumnElement[j % sides].clone();

    if (window.get.mode === 'semaine' || window.get.mode === 'organiser') {
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
var setCalendar = function (day) {
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
