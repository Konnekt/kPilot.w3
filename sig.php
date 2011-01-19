<?php

require('inc/shared.inc.php');

// JS function
$js_func = <<< END

function kP2Get(id, content) {
  var r = document.getElementById(id); // container

  try {
    if(typeof(kP2_Error) != 'undefined') {
      r.innerHTML = '<strong>Error:</strong> ' + kP2_Error;
      return(false);
    } // check for kP2 errors

    toReplace = [
      [
        /\\{link\\}(.*)\\{\\/link\\}/g,
        '<a href="http://kpilot.info/stats.xml?login={$_ARG['login']}&amp;profile={$_ARG['profile']}" target="_blank" title="' + title + '">\$1</a>'
      ],
      ['artist'], ['album'], ['track'],
      ['combined'], ['title'], ['length'],
      ['bitrate']
    ]; // array with replacements [ key, value ]

    content = content.replace(/\\n/g, '<br/>'); // nl2br
    for(i = 0; i < toReplace.length; i++) {
      var key = toReplace[i][0]; var val = toReplace[i][1]; // define key & value

      key = (typeof(key) != 'string') ? key : '{' + key + '}'; // insert prefix & suffix
      val = (typeof(val) != 'undefined') ? val : eval(key); // there is no value defined, use key as variable name
      content = content.replace(key, (val) ? val : '---'); // check for empty values
    }

    r.innerHTML = content; // update container content
  } catch(e) { // catch JS errors
    if(!r) { // if there's no container defined
      alert('JS Error in kP2 code: ' + e.message);
    } else { // we have both - container and error ;>
      r.innerHTML = '<strong><acronym title="JavaScript">JS</acronym> Error:</strong> ' + e.message;
    }
  }
}
END;

// Print error
function js_error($msg) {
  global $js_func;

  echo('var kP2_Error = "'.htmlspecialchars($msg.(($e = mysql_error()) ? ' ['.$e.']' : '')).'";'."\n");
  echo($js_func);
  d13();
}

// Setting js headers
header('Content-Type: text/javascript; charset=UTF-8');
header('Content-Disposition: inline; filename='.$_ARG['login'].'_'.$_ARG['profile'].'.js');

// Basic info
echo('// __ACCOUNT: ['.$_ARG['login'].' -> '.$_ARG['profile'].']; __REFERER: '.(($ref = $_SERVER['HTTP_REFERER']) ? $ref : '-')."\n\n");

// Checkin' data
if(!__($_ARG['login'], $_ARG['profile'])) {
  js_error('You must provide account login and profile name.');
}

// DB query
$query = @mysql_query('SELECT artist, title, album, time, length, bitrate, last_state FROM info WHERE login = \''.$_ARG['login'].'\' AND profile = \''.$_ARG['profile'].'\' ORDER BY id DESC LIMIT 0,1') or js_error('internal query error #1');

// If nothin', show the error
if(!mysql_num_rows($query)) {
  js_error('This user doesn\'t have any logged entries.');
}

// JS safe conversion
$row = mysql_fetch_array($query);
foreach($row AS $k => $v) {
  $row[$k] = htmlspecialchars($v);
}

// Defining some bullshit
$title = (is_playing($row['last_state'], $row['length'], $row['time'])) ? 
          'Track currently playing' : 
          'Last track played @ '.date('H:i o\n Y/m/d', strtotime($row['time']));

$js_vars = array(
  'login' => $_ARG['login'],
  'profile' => $_ARG['profile'],
  null,
  'artist' => $row['artist'],
  'album' => $row['album'],
  'track' => $row['title'],
  'combined' => $row['artist'].' - '.$row['title'],
  null,
  'title' => $title,
  'length' => $row['length'],
  'bitrate' => $row['bitrate']
);

foreach($js_vars AS $k => $v) {
  echo((is_numeric($k)) ? "\n" : 'var '.$k.' = "'.$v.'";'."\n");
}

echo($js_func);

// Die bitch
d13();

?>