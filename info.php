<?php

require('inc/shared.inc.php');

// Grabbing data from UserAgent header
preg_match('/^kPilot (.+) \((.+)=(.+) \[(.+)\]\) \#(.+)/i', $_SERVER['HTTP_USER_AGENT'], $_preg);

// sbd is not welcome here, so... get the fuck out!
if(!$_preg[0])
  reply(false, 'user-agent parse error');

// Setting array with various options
$data = array(
  'version' => $_preg[1],
  'login' => $_preg[2],
  'md5hash' => $_preg[3],
  'profile' => $_preg[4],
  'state' => $_preg[5]
);

// Checkin' data
if(!__($_ARG['length'], $_ARG['bitrate'])) reply(false, 'incomplete input data');
if(!preg_replace('/[^A-Za-z0-9]/i', null, $_ARG['artist'])) $_ARG['artist'] = null;
if(!preg_replace('/[^A-Za-z0-9]/i', null, $_ARG['title'])) $_ARG['title'] = null;
if(!preg_replace('/[^A-Za-z0-9]/i', null, $_ARG['album'])) $_ARG['album'] = null;
if(!__($_ARG['artist'], $_ARG['title'])) reply(false, 'id3 tags missing');

// Block anon-users & authorize other
if($data['login']{0} == '^') reply(false, 'you must have a beta account to use this feature');
if(!auth($data['login'], $data['md5hash'])) reply(false, 'invalid login and/or password');

$data['login'] = str_replace("'", "\\'", $data['login']);
$data['profile'] = str_replace("'", "\\'", $data['profile']);

// Managing states
switch($data['state']) {
  case 'STOP':
    reply(@mysql_query('UPDATE info SET last_state = \'STOP\' WHERE login = \''.$data['login'].'\' AND profile = \''.$data['profile'].'\' AND artist = \''.$_ARG['artist'].'\' AND title = \''.$_ARG['title'].'\' AND album = \''.$_ARG['album'].'\' AND length = \''.$_ARG['length'].'\' AND bitrate = \''.$_ARG['bitrate'].'\' ORDER BY id DESC LIMIT 1'), 'internal query error #1', 1);
    break;
  case 'PAUSE':
    reply(@mysql_query('UPDATE info SET last_state = \'PAUSE\' WHERE login = \''.$data['login'].'\' AND profile = \''.$data['profile'].'\' AND artist = \''.$_ARG['artist'].'\' AND title = \''.$_ARG['title'].'\' AND album = \''.$_ARG['album'].'\' AND length = \''.$_ARG['length'].'\' AND bitrate = \''.$_ARG['bitrate'].'\' ORDER BY id DESC LIMIT 1'), 'internal query error #2', 1);
    break;
  case 'UNPAUSE':
    reply(@mysql_query('UPDATE info SET last_state = \'PLAY\' WHERE login = \''.$data['login'].'\' AND profile = \''.$data['profile'].'\' AND artist = \''.$_ARG['artist'].'\' AND title = \''.$_ARG['title'].'\' AND album = \''.$_ARG['album'].'\' AND length = \''.$_ARG['length'].'\' AND bitrate = \''.$_ARG['bitrate'].'\' ORDER BY id DESC LIMIT 1'), 'internal query error #3', 1);
    break;
  case 'PLAY':
    reply(@mysql_query('INSERT INTO info (login, profile, artist, title, album, time, length, bitrate, last_state) VALUES (\''.$data['login'].'\', \''.$data['profile'].'\', \''.$_ARG['artist'].'\', \''.$_ARG['title'].'\', \''.$_ARG['album'].'\', NOW(), \''.$_ARG['length'].'\', \''.$_ARG['bitrate'].'\', \'PLAY\')'), 'internal query error #4', 1);
    break;
  default:
    reply(false, 'incorrect state');
    break;
}

// Die bitch
d13();

?>