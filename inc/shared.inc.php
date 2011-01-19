<?php

/*************************
/* Shared functions
/************************/

// Starting output buffering
ob_start('exceptions');

// If we messed up with script
function exceptions($buffer) {
  global $err, $errmsg;
  if(preg_match('/(Fatal error: .+ in .+? on line \d+)/', strip_tags($buffer), $matches)) {
    header('X-Response: 0');
    header('X-Error: '.$matches[0]);
  } elseif($err) {
    header('HTTP/1.0 '.$err.' '.ucwords($errmsg));
    header('X-Error: '.$err.' '.ucwords($errmsg));
  }

  if(strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
    header('Content-Encoding: gzip');
    $buffer = gzencode($buffer);
  }

  return($buffer);
}

// Authorize users
function auth($l, $p) {
  $query = mysql_query('SELECT login, admin FROM stats WHERE login = \''.$l.'\' AND password = \''.$p.'\' AND ((DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= last_run AND admin = \'0\') OR (admin = \'1\'))');

  if(mysql_num_rows($query)) {
    $row = mysql_fetch_array($query);

    if(session_id()) {
      $_SESSION['logged'] = '1';
      $_SESSION['admin'] = $row['admin'];
      $_SESSION['login'] = $row['login'];
    }
    return(true);
  }
  return(false);
}

// Data for kPilot
function reply($what = true, $error = 'unknown error', $sql_error = false) {
  // Response & exit
  header('X-Response: '.(($what) ? '1' : '0'));
  if(!$what) header('X-Error: '.$error.(($sql_error) ? ' ['.mysql_error().']' : null));
  if(strpos($_SERVER['HTTP_USER_AGENT'], 'kPilot') === false && !$what && $sql_error)
    echo('<div style="font-family: Tahoma, Verdana, Arial; font-size: 11px"><b>ERROR</b> { '.$error.(($sql_error) ? ' ['.mysql_error().']' : null).' } <b>!!!</b></div>');
  d13();
}

// Nice exit
function d13($msg = false) {
  global $link;

  // Print some goodbye msg
  if($msg) echo('<br/><br/>'.$msg);  
  // Terminate db connection
  @mysql_close($link);

  // Exit
  exit;
}

// Checking variables for empty ones
function __() {
  $vars = func_get_args();
  foreach($vars AS $v) {
    if(!trim($v)) return(false);
  }
  return(true);
}

// Parsing string
function parse_string($str, $delim = '/') {
  // Parsing variables
  $str = explode($delim, $str);
  foreach($str AS $v) {
    $v = stripslashes(urldecode($v));
    if(preg_match('/^(.+)\[[\'"]?(.*?)[\'"]?\]=(.*)$/i', $v, $_preg2)) {
      $_ARG[$_preg2[1]][$_preg2[2]] = trim($_preg2[3]);
    } else {
      if(!preg_match('/^([a-zA-Z0-9_-]+)=(.*)$/i', $v, $_preg)) continue;
      $_ARG[$_preg[1]] = trim($_preg[2]);
    }
    unset($_preg, $_preg2);
  }
  return($_ARG);
}

// Playing or not ?
function is_playing($last_state, $length, $time) {
  static $init;

  if(!isset($init) && $last_state == 'PLAY') {
    $l = explode(':', $length); $l = round((($l[0] * 60) + $l[1])/* / 2*/);
    if((strtotime($time) + $l) >= time()) {
      $r = true;
    }
  } elseif(isset($init)) {
    $r = false;
  }

  // Return
  $init = true;
  return(($r) ? true : false);
}

/*************************
/* Shared core
/************************/

// Initiating db connection
$link = @mysql_connect('localhost', 'sija_kpilot', 'kpilot4k') or reply(false, 'can\'t connect to database', 1);
@mysql_select_db('sija_kpilot') or reply(false, 'can\'t select database', 1);

// Decoding variables
$_ARG = parse_string($_SERVER['QUERY_STRING']);
if(is_array($_ARG)) foreach($_ARG AS $k => $v) $_ARG[$k] = addslashes($v);

?>