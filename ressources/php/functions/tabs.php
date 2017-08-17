<?php
function addActive($element) {
  $in = FALSE;

  if ($element == $_SESSION['login'])
    return FALSE;

  addToOthers($element);
  if (array_keys($_SESSION['active'], $element) == array())
    array_push($_SESSION['active'], $element);

  return TRUE;
}

function delActive($login) {
  $where = array_keys($_SESSION['active'], $login);

  if ($login == $_SESSION['login'])
    return FALSE;

  if ($where != array()) {
    unset($_SESSION['active'][$where[0]]);
    return TRUE;
  }

  return FALSE;
}

function printActiveTabs() {
  foreach ($_SESSION['groups'] as $name => $tab) {

  }
  $GLOBALS['activeTabs'] = $_SESSION['groups'];
}

function printSeparateTab() {
  $GLOBALS['tabs'][array_keys($GLOBALS['tabs'], end($GLOBALS['tabs']))[0]]['separate'] = TRUE;
}

function printMyTab($selected = TRUE) {
  $GLOBALS['tabs']['me'] = array(
    'type' => 'button',
    'text' => ($_SESSION['surname'] == '' ? $_SESSION['login'] : $_SESSION['firstname'].' '.$_SESSION['surname']),
    'action' => 'window.get={mode:"'.$GLOBALS['mode'].'"}; generate();'
  );

  if ($selected)
    $GLOBALS['tabs']['me']['active'] = TRUE;
}

function printRoomTabs($type) {
  $gap = isset($_GET['mode_option']) && is_numeric($_GET['mode_option']) ? ' '.intval($_GET['mode_option']).'h' : '';
  $GLOBALS['tabs']['rooms'] = array(
    'type' => 'select',
    'text' => 'Salles libres',
    'get' => array(
      'mode' => (isset($_GET['mode']) && $_GET['mode'] == 'semaine' ? 'semaine' : 'classique'),
      'mode_type' => 'rooms'
    ),
    'options' => array(
      '1-2' => array(
        'text' => 'De 1 à 2h',
        'active' => $gap == 1,
        'get' => array(
          'mode_option' => 1
        )
      ),
      '3-4' => array(
        'text' => 'De 3 à 4h',
        'active' => $gap == 3,
        'get' => array(
          'mode_option' => 3
        )
      ),
      '5-6' => array(
        'text' => 'De 5 à 6h',
        'active' => $gap == 5,
        'get' => array(
          'mode_option' => 5
        )
      ),
      '7-8' => array(
        'text' => 'De 7 à 8h',
        'active' => $gap == 7,
        'get' => array(
          'mode_option' => 7
        )
      ),
      '9-10' => array(
        'text' => 'De 9 à 10h',
        'active' => $gap == 9,
        'get' => array(
          'mode_option' => 9
        )
      ),
      '+10' => array(
        'text' => 'Plus de 10h',
        'active' => $gap == -10,
        'get' => array(
          'mode_option' => -10
        )
      ),
    )
  );

  if ($type == 'rooms') {
    $GLOBALS['tabs']['rooms']['active'] = TRUE;
    $active = FALSE;

    foreach ($GLOBALS['tabs']['rooms']['options'] as $key => $option) {
      if ($option['get']['mode_option'] == $gap) {
        $GLOBALS['tabs']['rooms']['options'][$key]['active'] = TRUE;
        $active = TRUE;
        $GLOBALS['tabs']['rooms']['text'] .= ' '.strtolower($option['text']);
      }
    }

    if (!$active)
      $GLOBALS['tabs']['rooms']['options']['1-2']['active'] = TRUE;
  }
}

function printModiferTabs($type) {
  printMyTab($type == NULL);

  $UVsFollowed = getUVsFollowed($_SESSION['login']);

  foreach ($UVsFollowed as $UVFollowed) {
    if (!isset($GLOBALS['tabs'][$UVFollowed['uv']]))
      $GLOBALS['tabs'][$UVFollowed['uv']] = array(
        'type' => 'select',
        'text' => 'Echanger '.$UVFollowed['uv'],
        'get' => array(
          'mode' => 'modifier',
          'uv' => $UVFollowed['uv']
        ),
        'options' => array(
          'C' => array(
            'text' => 'Cours',
            'disabled' => TRUE,
            'get' => array(
              'type' => 'C'
            )
          ),
          'D' => array(
            'text' => 'TD',
            'disabled' => TRUE,
            'get' => array(
              'type' => 'D'
            )
          ),
          'T' => array(
            'text' => 'TP',
            'disabled' => TRUE,
            'get' => array(
              'type' => 'T'
            )
          ),
        )
      );

    $GLOBALS['tabs'][$UVFollowed['uv']]['options'][$UVFollowed['type']]['disabled'] = FALSE;
  }

  if (isset($_GET['uv']) && isset($_GET['type']) && isset($GLOBALS['tabs'][$_GET['uv']])) {
    $GLOBALS['tabs'][$_GET['uv']]['color'] = '00FF00';
    $GLOBALS['tabs'][$_GET['uv']]['options'][$_GET['type']]['color'] = '00FF00';
  }

  $receivedAll = count(getExchangesReceived($_SESSION['login'], NULL, NULL));
  $receivedAvailable = count(getExchangesReceived($_SESSION['login'], NULL, NULL, 1, 0));
  $receivedAccepted = count(getExchangesReceived($_SESSION['login'], NULL, NULL, 0, 1));
  $receivedRefused = count(getExchangesReceived($_SESSION['login'], NULL, NULL, 0, 0));
  $sentAll = count(getExchangesSent($_SESSION['login'], NULL, NULL));
  $sentAvailable = count(getExchangesSent($_SESSION['login'], NULL, NULL, 1, 0));
  $sentAccepted = count(getExchangesSent($_SESSION['login'], NULL, NULL, 0, 1));
  $sentRefused = count(getExchangesSent($_SESSION['login'], NULL, NULL, 0, 0));
  $canceled = count(getExchangesCanceled($_SESSION['login']));
  $changements = count(getUVsFollowed($_SESSION['login'], 0, 1));

  $GLOBALS['tabs']['received'] = array(
    'type' => 'select',
    'text' => 'Demandes reçus',
    'get' => array(
      'mode' => 'comparer',
      'mode_type' => 'received'
    ),
    'options' => array(
      'all' => array(
        'text' => $receivedAll.' au total',
        'color' => '#FFFF00'
      ),
      'available' => array(
        'text' => $receivedAvailable.' en attente',
        'get' => array(
          'option' => 'available'
        ),
        'color' => '#0000FF'
      ),
      'accepted' => array(
        'text' => $receivedAccepted.' accepté'.($receivedAccepted > 1 ? 's' : ''),
        'get' => array(
          'option' => 'accepted'
        ),
        'color' => '#00FF00'
      ),
      'refused' => array(
        'text' => $receivedRefused.' refusé'.($receivedRefused > 1 ? 's' : ''),
        'get' => array(
          'option' => 'refused'
        ),
        'color' => '#FF0000'
      ),
    )
  );

  if ($type == 'received') {
    $get_option = (isset($_GET['mode_option']) && is_string($_GET['mode_option']) ? $_GET['mode_option'] : 'all');
    $GLOBALS['tabs']['received']['active'] = TRUE;
    $active = FALSE;

    foreach ($GLOBALS['tabs']['received']['options'] as $key => $option) {
      if (isset($option['get']['option']) && $option['get']['option'] == $get_option) {
        $GLOBALS['tabs']['received']['options'][$key]['active'] = TRUE;
        $GLOBALS['tabs']['received']['color'] = $GLOBALS['tabs']['received']['options'][$key]['color'];
        $active = TRUE;
      }
    }

    if (!$active) {
      $GLOBALS['tabs']['received']['options']['all']['active'] = TRUE;
      $GLOBALS['tabs']['received']['color'] = $GLOBALS['tabs']['received']['options'][$key]['color'];
    }
  }

  if ($receivedAll == 0) {
    $GLOBALS['tabs']['received']['disabled'] = TRUE;
    $GLOBALS['tabs']['received']['options']['all']['disabled'] = TRUE;
  }
  if ($receivedAvailable == 0)
    $GLOBALS['tabs']['received']['options']['available']['disabled'] = TRUE;
  if ($receivedAccepted == 0)
    $GLOBALS['tabs']['received']['options']['accepted']['disabled'] = TRUE;
  if ($receivedRefused == 0)
    $GLOBALS['tabs']['received']['options']['refused']['disabled'] = TRUE;

  $GLOBALS['tabs']['sent'] = array(
    'type' => 'select',
    'text' => 'Demandes envoyés',
    'get' => array(
      'mode' => 'comparer',
      'mode_type' => 'sent'
    ),
    'options' => array(
      'all' => array(
        'text' => $sentAll.' au total',
        'color' => '#FFFF00'
      ),
      'available' => array(
        'text' => $sentAvailable.' en attente',
        'get' => array(
          'option' => 'available'
        ),
        'color' => '#0000FF'
      ),
      'accepted' => array(
        'text' => $sentAccepted.' accepté'.($sentAccepted > 1 ? 's' : ''),
        'get' => array(
          'option' => 'accepted'
        ),
        'color' => '#00FF00'
      ),
      'refused' => array(
        'text' => $sentRefused.' refusé'.($sentRefused > 1 ? 's' : ''),
        'get' => array(
          'option' => 'refused'
        ),
        'color' => '#FF0000'
      ),
    )
  );

  if ($type == 'sent') {
    $get_option = (isset($_GET['mode_option']) && is_string($_GET['mode_option']) ? $_GET['mode_option'] : 'all');
    $GLOBALS['tabs']['sent']['active'] = TRUE;
    $active = FALSE;

    foreach ($GLOBALS['tabs']['sent']['options'] as $key => $option) {
      if (isset($option['get']['option']) && $option['get']['option'] == $get_option) {
        $GLOBALS['tabs']['sent']['options'][$key]['active'] = TRUE;
        $GLOBALS['tabs']['sent']['color'] = $GLOBALS['tabs']['sent']['options'][$key]['color'];
        $active = TRUE;
      }
    }

    if (!$active) {
      $GLOBALS['tabs']['sent']['options']['all']['active'] = TRUE;
      $GLOBALS['tabs']['sent']['color'] = $GLOBALS['tabs']['sent']['options'][$key]['color'];
    }
  }

  if ($sentAll == 0) {
    $GLOBALS['tabs']['sent']['disabled'] = TRUE;
    $GLOBALS['tabs']['sent']['all']['disabled'] = TRUE;
  }
  if ($sentAvailable == 0)
    $GLOBALS['tabs']['sent']['options']['available']['disabled'] = TRUE;
  if ($sentAccepted == 0)
    $GLOBALS['tabs']['sent']['options']['accepted']['disabled'] = TRUE;
  if ($sentRefused == 0)
    $GLOBALS['tabs']['sent']['options']['refused']['disabled'] = TRUE;

  $GLOBALS['tabs']['canceled'] = array(
    'type' => 'button',
    'text' => 'Echanges en annulation',
    'get' => array(
      'mode' => 'comparer',
      'mode_type' => 'canceled'
    )
  );

  if ($type == 'canceled')
    $GLOBALS['tabs']['canceled']['active'] = TRUE;

  if ($canceled == 0)
    $GLOBALS['tabs']['canceled']['disabled'] = TRUE;

  printSeparateTab();

  $GLOBALS['tabs']['original'] = array(
    'type' => 'button',
    'text' => 'Emploi du temps original',
    'get' => array(
      'mode' => 'comparer',
      'mode_type' => 'original'
    )
  );

  if ($type == 'original')
    $GLOBALS['tabs']['original']['active'] = TRUE;

  $GLOBALS['tabs']['changement'] = array(
    'type' => 'button',
    'text' => 'Changements',
    'get' => array(
      'mode' => 'comparer',
      'mode_type' => 'changement'
    )
  );

  if ($type == 'changement')
    $GLOBALS['tabs']['changement']['active'] = TRUE;

  if ($changements == 0) {
    $GLOBALS['tabs']['original']['disabled'] = TRUE;
    $GLOBALS['tabs']['changement']['disabled'] = TRUE;
  }
}

function printSemaineTabs($type) {
  printMyTab($type == NULL);
  printSeparateTab();

  $GLOBALS['tabs']['uvs_followed'] = array(
    'type' => 'button',
    'text' => 'Cours',
    'get' => array(
      'mode' => 'semaine',
      'mode_type' => 'uvs_followed'
    )
  );

  if ($type == 'uvs_followed')
    $GLOBALS['tabs']['uvs_followed']['active'] = TRUE;

  $GLOBALS['tabs']['events'] = array(
    'type' => 'button',
    'text' => 'Evènements',
    'get' => array(
      'mode' => 'semaine',
      'mode_type' => 'events'
    )
  );

  if ($type == 'events')
    $GLOBALS['tabs']['events']['active'] = TRUE;

  $GLOBALS['tabs']['meetings'] = array(
    'type' => 'button',
    'text' => 'Réunions',
    'get' => array(
      'mode' => 'semaine',
      'mode_type' => 'meetings'
    )
  );

  if ($type == 'meetings')
    $GLOBALS['tabs']['meetings']['active'] = TRUE;

  printRoomTabs($type);

  printSeparateTab();
  $gap = isset($_GET['mode_option']) && is_numeric($_GET['mode_option']) ? ' '.intval($_GET['mode_option']).'h' : '';
}
