<?php

require('inc/shared.inc.php');

// version [x.x.x.x] -> kUpdate
function kupdate_version($nr) {
  // makin' version
  $ver = explode('.', $nr);
  // checkin' data
  $count = count($ver);
  for($g = $count; $g < 4; $g++) $ver[$g] = 0;
  // temp variables
  $res = 0; $mul = 1; $last = count($ver) - 1;
  // loop
  for($i = $last; $i >= 0; $i--) {
    $res += $ver[$i] * $mul;
    $mul *= 256;
    if($i == $last) $mul *= 16;
  }
  // end of story
  return $res;
}

// Generating salt, defining query states 
$_av = array('login', 'md5hash', 'player', 'version', 'kversion', 'os', 'state');
$_sp = array('[$v^vv$]', '[$v^v$]', '[$vv^v$]');
$_avc = count($_av)-1; $salt = null;

for($i = 0; $i <= $_avc; $i++) {
  if(!$i) $salt .= '^';
  elseif($i == 1) $salt .= $_sp[0];
  elseif($i == $_avc) $salt .= $_sp[2];
  else $salt .= $_sp[1];

  $salt .= $_ARG[$_av[$i]];
  if($i == $_avc) $salt .= '$';
}

$states = array('INIT', 'PING', 'EXIT');
$_ARG['state'] = strtoupper($_ARG['state']);

// Checkin' data
if(!__($_ARG['login'], $_ARG['md5hash'], $_ARG['version'], $_ARG['kversion'], $_ARG['os'], $_ARG['salt']) && in_array($_ARG['state'], $states)) reply(false, 'incomplete input data');
if($_ARG['salt'] != md5($salt)) reply(false, 'bad salt');

// SQL executing & response
$query = @mysql_query('SELECT login FROM stats WHERE login = \''.$_ARG['login'].'\'') or reply(false, 'internal query error #1', 1);
$row = @mysql_fetch_array($query);

/* if(!$row['stats']) $row['stats'] = '0|0|0';

// Updating counters
$count = explode('|', $row['stats']);
switch($_ARG['state']) {
  case 'INIT':
    $count[0]++;
    break;
  case 'PING':
    $count[1]++;
    break;
  case 'EXIT':
    $count[2]++;
    break;
}
$count = implode('|', $count); */

// Playing with some variables
$ip = $_SERVER['REMOTE_ADDR'];
$_ARG['kversion'] = kupdate_version($_ARG['kversion']);
// $_ARG['version'] = kupdate_version($_ARG['version']);

// Insert [or update] row with data
if(!@mysql_num_rows($query)) {
  reply(@mysql_query('INSERT INTO stats (login, password, version, kversion, player, os, first_run, last_run, last_ip, last_state) VALUES (\''.$_ARG['login'].'\', \''.$_ARG['md5hash'].'\', \''.$_ARG['version'].'\', \''.$_ARG['kversion'].'\', '.(($_ARG['player']) ? '\''.$_ARG['player'].'\'' : 'NULL').', \''.$_ARG['os'].'\', NOW(), NOW(), \''.$ip.'\', \''.$_ARG['state'].'\')'), 'internal query error #2', 1);
} else {
  reply(@mysql_query('UPDATE stats SET password = \''.$_ARG['md5hash'].'\''.(($_ARG['player']) ? ', player = \''.$_ARG['player'].'\'' : null).', version = \''.$_ARG['version'].'\', kversion = \''.$_ARG['kversion'].'\', os = \''.$_ARG['os'].'\', last_run = NOW(), last_ip = \''.$ip.'\', last_state = \''.$_ARG['state'].'\' WHERE login = \''.$_ARG['login'].'\''), 'internal query error #3', 1);
}

// Die bitch
d13();

?>