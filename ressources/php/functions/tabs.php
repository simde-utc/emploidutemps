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
    'action' => 'window.get={mode:"'.$GLOBALS['mode'].'"}; generate();',
    'active' => $selected
  );
}

function printRoomTabs($type) {
  $gap = isset($_GET['mode_option']) && is_numeric($_GET['mode_option']) ? ' '.intval($_GET['mode_option']).'h' : '';
  $GLOBALS['tabs']['rooms'] = array(
    'type' => 'select',
    'text' => 'Salles de cours libres',
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

function printModifierTabs($type) {
  $UVsFollowed = getUVsFollowed($_SESSION['login']);
  $uv = (isset($_GET['uv']) && is_string($_GET['uv']) ? $_GET['uv'] : NULL);
  $uvType = (isset($_GET['type']) && is_string($_GET['type']) ? $_GET['type'] : NULL);
  printMyTab($type == NULL && $uv == NULL && $uvType == NULL);

  foreach ($UVsFollowed as $UVFollowed) {
    if (!isset($GLOBALS['tabs'][$UVFollowed['uv']]))
      $GLOBALS['tabs'][$UVFollowed['uv']] = array(
        'type' => 'select',
        'text' => 'Echanger '.$UVFollowed['uv'],
        'active' => $uv == $UVFollowed['uv'] && $type == 'uvs_followed',
        'get' => array(
          'mode' => 'modifier',
          'mode_type' => 'uvs_followed',
          'uv' => $UVFollowed['uv']
        ),
        'options' => array(
          'C' => array(
            'text' => 'Cours',
            'disabled' => TRUE,
            'active' => $uv == $UVFollowed['uv'] && $uvType == 'C' && $type == 'uvs_followed',
            'get' => array(
              'type' => 'C'
            )
          ),
          'D' => array(
            'text' => 'TD',
            'disabled' => TRUE,
            'active' => $uv == $UVFollowed['uv'] && $uvType == 'D' && $type == 'uvs_followed',
            'get' => array(
              'type' => 'D'
            )
          ),
          'T' => array(
            'text' => 'TP',
            'disabled' => TRUE,
            'active' => $uv == $UVFollowed['uv'] && $uvType == 'T' && $type == 'uvs_followed',
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

  $sentAll = count(getSentExchanges($_SESSION['login'], NULL, NULL));
  $sentAvailable = count(getSentExchanges($_SESSION['login'], NULL, NULL, 1, 0));
  $sentAccepted = count(getSentExchanges($_SESSION['login'], NULL, NULL, NULL, 1));
  $sentRefused = count(getSentExchanges($_SESSION['login'], NULL, NULL, 0, 0));
  $receivedAll = count(getReceivedExchanges($_SESSION['login'], NULL, NULL));
  $receivedAvailable = count(getReceivedExchanges($_SESSION['login'], NULL, NULL, 1, 0));
  $receivedAccepted = count(getReceivedExchanges($_SESSION['login'], NULL, NULL, NULL, 1));
  $receivedRefused = count(getReceivedExchanges($_SESSION['login'], NULL, NULL, 0, 0));
  $sentCanceled = count(getCanceledExchanges($_SESSION['login']));
  $receivedCanceled = count(getCanceledExchanges(NULL, NULL, NULL, 1, $_SESSION['login']));
  $changements = count(getUVsFollowed($_SESSION['login'], 0, 1));

  $GLOBALS['tabs']['sent'] = array(
    'type' => 'select',
    'text' => 'Propositions d\'échange envoyées',
    'get' => array(
      'mode' => 'modifier',
      'mode_type' => 'sent'
    ),
    'active' => ($type == 'sent'),
    'options' => array(
      'all' => array(
        'text' => $sentAll.' au total',
        'get' => array(
          'mode_option' => 'all'
        ),
        'active' => ($type == 'sent' && (isset($_GET['mode_option']) && $_GET['mode_option'] == 'all'))
      ),
      'available' => array(
        'text' => $sentAvailable.' en attente',
        'get' => array(
          'mode_option' => 'available'
        ),
        'active' => ($type == 'sent' && (isset($_GET['mode_option']) && $_GET['mode_option'] == 'available'))
      ),
      'accepted' => array(
        'text' => $sentAccepted.' acceptée'.($sentAccepted > 1 ? 's' : ''),
        'get' => array(
          'mode_option' => 'accepted'
        ),
        'active' => ($type == 'sent' && (isset($_GET['mode_option']) && $_GET['mode_option'] == 'accepted'))
      ),
      'refused' => array(
        'text' => $sentRefused.' refusée'.($sentRefused > 1 ? 's' : ''),
        'get' => array(
          'mode_option' => 'refused'
        ),
        'active' => ($type == 'sent' && (isset($_GET['mode_option']) && $_GET['mode_option'] == 'refused'))
      ),
    )
  );

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

  $GLOBALS['tabs']['received'] = array(
    'type' => 'select',
    'text' => 'Propositions d\'échange reçues',
    'get' => array(
      'mode' => 'modifier',
      'mode_type' => 'received'
    ),
    'active' => ($type == 'received'),
    'options' => array(
      'all' => array(
        'text' => $receivedAll.' au total',
        'get' => array(
          'mode_option' => 'all'
        ),
        'active' => ($type == 'received' && (isset($_GET['mode_option']) && $_GET['mode_option'] == 'all'))
      ),
      'available' => array(
        'text' => $receivedAvailable.' en attente',
        'get' => array(
          'mode_option' => 'available'
        ),
        'active' => ($type == 'received' && (isset($_GET['mode_option']) && $_GET['mode_option'] == 'available'))
      ),
      'accepted' => array(
        'text' => $receivedAccepted.' acceptée'.($receivedAccepted > 1 ? 's' : ''),
        'get' => array(
          'mode_option' => 'accepted'
        ),
        'active' => ($type == 'received' && (isset($_GET['mode_option']) && $_GET['mode_option'] == 'accepted'))
      ),
      'refused' => array(
        'text' => $receivedRefused.' refusée'.($receivedRefused > 1 ? 's' : ''),
        'get' => array(
          'mode_option' => 'refused'
        ),
        'active' => ($type == 'received' && (isset($_GET['mode_option']) && $_GET['mode_option'] == 'refused'))
      ),
    )
  );

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

  $GLOBALS['tabs']['canceled'] = array(
    'type' => 'select',
    'text' => 'Echanges en cours d\'annulation',
    'get' => array(
      'mode' => 'modifier',
      'mode_type' => 'canceled'
    ),
    'active' => ($type == 'canceled'),
    'options' => array(
      'sent' => array(
        'text' => $sentCanceled.' demande'.($sentCanceled > 1 ? 's' : '').' d\'annulation en cours',
        'get' => array(
          'mode_option' => 'sent'
        ),
        'active' => ($type == 'canceled' && (isset($_GET['mode_option']) && $_GET['mode_option'] == 'sent'))
      ),
      'received' => array(
        'text' => $receivedCanceled.' demande'.($receivedCanceled > 1 ? 's' : '').' d\'annulation reçue'.($receivedCanceled > 1 ? 's' : '').' en attente',
        'get' => array(
          'mode_option' => 'received'
        ),
        'active' => ($type == 'canceled' && (isset($_GET['mode_option']) && $_GET['mode_option'] == 'received'))
      ),
    )
  );

  if ($sentCanceled + $receivedCanceled == 0)
    $GLOBALS['tabs']['canceled']['disabled'] = TRUE;
  if ($sentCanceled == 0)
    $GLOBALS['tabs']['canceled']['options']['sent']['disabled'] = TRUE;
  if ($receivedCanceled == 0)
    $GLOBALS['tabs']['canceled']['options']['received']['disabled'] = TRUE;

  printSeparateTab();

  $GLOBALS['tabs']['original'] = array(
    'type' => 'button',
    'text' => 'Emploi du temps original',
    'get' => array(
      'mode' => 'modifier',
      'mode_type' => 'original'
    ),
    'active' => ($type == 'original')
  );

  $GLOBALS['tabs']['changement'] = array(
    'type' => 'button',
    'text' => 'Changements',
    'get' => array(
      'mode' => 'modifier',
      'mode_type' => 'changement'
    ),
    'active' => ($type == 'changement')
  );

  if ($changements == 0) {
    $GLOBALS['tabs']['original']['disabled'] = TRUE;
    $GLOBALS['tabs']['changement']['disabled'] = TRUE;
  }
}

function printSemaineTabs($type) {
  printMyTab($type == NULL);
  printSeparateTab();

  $GLOBALS['tabs']['uvs_followed'] = array(
    'type' => 'select',
    'text' => 'Cours',
    'get' => array(
      'mode' => 'semaine',
      'mode_type' => 'uvs_followed',
      'type' => NULL
    ),
    'options' => array(
      'all' => array(
        'text' => 'Toutes mes UVs',
        'active' => isset($_GET['mode_type']) && $_GET['mode_type'] == 'uvs_followed' && !isset($_GET['uv']),
        'get' => array(
          'uv' => NULL,
        )
      )
    )
  );

  $uvs = explode(',', $_SESSION['uvs']);
  foreach ($uvs as $key => $uv) {
    if (isAnUV($uv)) {
      $GLOBALS['tabs']['uvs_followed']['options'][$uv] = array (
        'text' => $uv,
        'active' => isset($_GET['mode_type']) && $_GET['mode_type'] == 'uvs_followed' && isset($_GET['uv']) && $_GET['uv'] == $uv,
        'get' => array(
          'uv' => $uv
        )
      );
    }
  }

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
