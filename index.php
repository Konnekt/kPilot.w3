<?php

// CSS parsing
if($_GET['theme']) {
  // CSS color values
  $style['blue']['border'] = '#c2cfdf';
  $style['blue']['font_color'] = '#3a4f6c';
  $style['blue']['bg'] = '#d0ddea';
  $style['blue']['bg_img'] = 'img/bg.gif';
  $style['blue']['foot_bg'] = '#e3ebf4';
  $style['blue']['block_border'] = '#d0ddea';

  $style['green']['border'] = '#99cc00';
  $style['green']['font_color'] = '#728d49';
  $style['green']['bg'] = '#e7fbc8';
  $style['green']['bg_img'] = 'img/bg_green.gif';
  $style['green']['foot_bg'] = '#e7fbc8';
  $style['green']['block_border'] = '#99cc00';

  $style['gray']['border'] = '#949494';
  $style['gray']['font_color'] = '#333333';
  $style['gray']['bg'] = '#d1d1d1';
  $style['gray']['bg_img'] = 'img/bg_gray.gif';
  $style['gray']['foot_bg'] = '#d1d1d1';
  $style['gray']['block_border'] = '#949494';

  if(isset($style[$_GET['theme']])) {
    $css_style = file_get_contents('inc/style.css');

    while(preg_match('/(@.+@)/U', $css_style, $matches) == TRUE){
      $matchvar = str_replace('@', null, $matches[1]);
      $css_style = str_replace($matches[1], $style[$_GET['theme']][$matchvar], $css_style);
    }

    header('Content-Type: text/css');
    header('Content-Length: '.strlen($css_style)); 
    echo($css_style);
    exit;
  } else {
    $_GET['module'] = 'error.404';
  }
}

// And now... the common shit.
require('inc/shared.inc.php');

// XHTML compatible
ini_set('arg_separator.output', '&amp;');

// Sessions
session_name('sid');
session_start();

// ICRA statement
header('Pics-Label: (pics-1.1 "http://www.icra.org/ratingsv02.html" l r (nz 1 vz 1 lz 1 oz 1 cz 1) gen true for "http://kpilot.info/" r (nz 1 vz 1 lz 1 oz 1 cz 1))');

// kUpdate -> version [x.x.x.x]
function version_kupdate($nr) {
  // x.0.0.0
  $ver[0] = intval($nr / (16 * 256 * 256 * 256));
  $min = $ver[0] * (16 * 256 * 256 * 256);
  // 0.x.0.0
  $ver[1] = intval(($nr - $min) / (16 * 256 * 256));
  $min += $ver[1] * (16 * 256 * 256);
  // 0.0.x.0
  $ver[2] = intval(($nr - $min) / (16 * 256));
  $min += $ver[2] * (16 * 256);
  // end of story
  return $ver[0].'.'.$ver[1].'.'.$ver[2].'.'.($nr - $min);
}

// bla bla bla
function parse_row($msg) {
  $msg = preg_replace('/<code>(.*?)<\/code>/es',
         "'<blockquote>'.str_replace(' ', '&nbsp;', htmlspecialchars('\\1')).'</blockquote>'", $msg);
  $msg = str_replace('\&quot;', '&quot;', $msg);
  $msg = preg_replace('/<\/blockquote>(([^\n\r])(.*?)([\n\r])?|$)/s', "</blockquote>\n\\2", $msg);
  $msg = preg_replace('/(\r\n|\n){2,}\[\!\] ([^\n]+)/s', "\n\n<div class=\"warning\"><b>Uwaga:</b> \\2</div>", $msg);
  $msg = preg_replace('/(\r\n|\n){2,}\[\?\] ([^\n]+)/s', "\n\n<div class=\"hint\"><b>Warto wiedzieć:</b> \\2</div>", $msg);

  return($msg);
}

// Redirection
function redirect($url = null) {
  if(!$url) {
    header('Location: '.(($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/'));
  } else {
    header('Location: '.$url);
  }
  d13();
}

// Delete data
function del($what, $table, $row) {
  if(!$_SESSION['logged'])
    return(false);

  $data = (isset($_POST[$what])) ? $_POST[$what] : $_GET[$what];
  #$data = array_map('addslashes', $data);
  if(is_array($data)) {
    $str = 'DELETE FROM '.$table.' WHERE '.$row.' IN (\''.implode('\',\'', $data).'\')';
    $str .= ($_SESSION['admin']) ? '' : ' AND login = \''.$_SESSION['login'].'\'';
    mysql_query($str);
  }
}

function time_since($time, $_time = false) {
  // array of time period chunks
  $chunks = array(
    array(60 * 60 * 24 * 365, 'year'),
    array(60 * 60 * 24 * 30, 'month'),
    array(60 * 60 * 24 * 7, 'week'),
    array(60 * 60 * 24, 'day'),
    array(60 * 60, 'hour'),
    array(60, 'minute'),
  );

  $today = ($_time) ? strtotime($_time) : time(); /* Current unix time  */
  $since = $today - strtotime($time);

  // $j saves performing the count function each time around the loop
  $count = count($chunks);
  for($i = 0, $j = $count; $i < $j; $i++) {
    $seconds = $chunks[$i][0];
    $name = $chunks[$i][1];

    // finding the biggest chunk (if the chunk fits, break)
    if(($count = floor($since / $seconds)) != 0) {
      // DEBUG echo("<!-- It's $name -->\n");
      break;
    }
  }

  $print = ($count == 1) ? '1 '.$name : "$count {$name}s";

  if($i + 1 < $j) {
    // now getting the second item
    $seconds2 = $chunks[$i + 1][0];
    $name2 = $chunks[$i + 1][1];

    // add second item if it's greater than 0
    if(($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
      $print .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 {$name2}s";
    }
  }
  return($print);
}

$login_required = array('settings');

// Timer start
$timeparts = explode(' ', microtime());
$starttime = $timeparts[1].substr($timeparts[0], 1);

if($_GET['module'] == 'logout') {
  setcookie('_svars', null);
  session_unset();

  redirect();
}

if($_GET['module'] == 'delete') {
  if($_SESSION['admin']) {
    del('account', 'info', 'login');
    del('account', 'stats', 'login');
    del('profile', 'info', 'profile');
    del('news', 'news', 'id');
    del('faq', 'faq', 'id');
  } else {
    del('profile', 'info', 'profile');
  }

  redirect();
}

if(in_array($_GET['module'], $login_required) && !$_SESSION['login']) {
  redirect('/');
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
  if($_GET['module'] == 'settings') {
    mysql_query('UPDATE stats SET anon =\''.(($_POST['anon']) ? '1' : '0').'\' WHERE login = \''.$_SESSION['login'].'\'');
    setcookie('theme', $_POST['theme'], time() + 3600*24*365);
    redirect();
  } elseif($_GET['module'] == 'news' && $_SESSION['admin']) {
    if(!__($_POST['title'], $_POST['text'])) redirect();
    if($_GET['action'] == 'edit') {
      mysql_query('UPDATE news SET title = \''.$_POST['title'].'\', text = \''.$_POST['text'].'\''.(($_POST['newDate']) ? ', date = NOW()' : null).' WHERE id = \''.$_GET['id'].'\'');
      redirect('/news.xml?id='.$_GET['id']);
    } else {
      mysql_query('INSERT INTO news (id, date, author, title, text) VALUES (\'\', NOW(), \''.$_SESSION['login'].'\', \''.$_POST['title'].'\', \''.$_POST['text'].'\')');
      redirect('/news.xml?id='.mysql_insert_id());
    }
  } elseif($_GET['module'] == 'faq' && $_SESSION['admin']) {
    if(!__($_POST['question'], $_POST['answer'])) redirect();
    if($_GET['action'] == 'edit') {
      mysql_query('UPDATE faq SET question = \''.$_POST['question'].'\', answer = \''.$_POST['answer'].'\' WHERE id = \''.$_GET['id'].'\'');
      redirect('/faq.xml?id='.$_GET['id']);
    } else {
      mysql_query('INSERT INTO faq (id, question, answer) VALUES (\'\', \''.$_POST['question'].'\', \''.$_POST['answer'].'\')');
      redirect('/faq.xml?id='.mysql_insert_id());
    }
  }

  $_S = true;
  if(auth($_POST['login'], md5($_POST['pass']))) {
    if($_POST['remember']) setcookie('_svars', base64_encode($_SESSION['login'].'#'.md5($_POST['pass'])), time() + 3600*24*365);
    redirect();
  }
}

if($_COOKIE['_svars'] && !$_SESSION['logged']) {
  $_s = explode('#', base64_decode($_COOKIE['_svars']));
  if(!auth($_s[0], $_s[1])) {
    setcookie('_svars', null);
    $_S = true;
  }
}

$menu['Home'] = '/';
$menu['News'] = '/news.xml';
$menu['FAQ'] = '/faq.xml';
$menu['Information'] = 'http://www.kplugins.net/plugins.kpilot.xml';
$menu['Bugtraq'] = 'http://kpilot2.wypieki-babuni.info/';
$menu['Downloads'] = 'http://www.kplugins.net/downloads.kpilot.xml';
$menu['Konnekt.info'] = 'http://www.konnekt.info/?ref=kpilot';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head profile="http://gmpg.org/xfn/11">
  <title>kPilot2 frontend</title>

  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <meta name="author" content="Sijawusz Pur Rahnama [://sija.net/]" />
  <meta name="keywords" content="konnekt, kplugins, plugin, kpilot, kp, kpilot2, kp2, pilot, foobar, foobar2000, winamp, wmp" />
  <link href="/<?php echo((isset($_COOKIE['theme']) && $_SESSION['logged']) ? $_COOKIE['theme'] : 'blue'); ?>.css" type="text/css" rel="stylesheet" media="all" title="<?php echo((isset($_COOKIE['theme']) && $_SESSION['logged']) ? ucfirst($_COOKIE['theme']) : 'Blue'); ?>" />
  <link href="/favicon.ico" type="image/ico" rel="shortcut icon" />
</head>

<body xml:lang="en" lang="en">
<div id="main">
  <script type="text/javascript" src="http://www.makepovertyhistory.org/whiteband_small_right.js"></script>
  <div id="header"><div id="title">kPilot2 frontend</div></div>

  <div id="sidebox">
    <div class="block">
      <h4>menu</h4>
<?php
foreach($menu AS $name => $link) {
  echo("- <a href=\"$link\">$name</a><br />\n");
}
?>
    </div>
    <div class="block">
<?php if(!$_SESSION['logged']) { ?>
      <h4>log in</h4>
      <form action="/" method="post">
        <?php if($_S) echo('<div class="error">access denied</div>'); ?>
        <input type="text" name="login" value="<?php echo(stripslashes(htmlspecialchars($_POST['login']))); ?>" title="login" tabindex="1" />
        <input type="checkbox" name="remember" title="remember me" style="border: 0px" tabindex="3" /><br />
        <input type="password" name="pass" value="" title="password" tabindex="2" />
        <input type="submit" value="&raquo;" class="submit" tabindex="4" />
      </form>
<?php } else { ?>
      <h4>welcome</h4>
      <div class="notice">Hello <b><?php echo((($_SESSION['admin']) ? '@' : '~').ucfirst($_SESSION['login'])); ?></b> !</div>
      - <a href="/stats.xml">Music stats</a><br />
      - <a href="/settings.xml">Settings</a><br />
      - <a href="/logout.xml">Log out</a><br />
<?php } ?>
    </div>
    <div class="block"><img src="/img/splash.jpg" alt="kPilot2" /></div>
<?php /* $row = mysql_fetch_array(mysql_query('SELECT version FROM stats ORDER BY version DESC LIMIT 0,1')); ?>
    <div style="position: relative; top: -320px; left: 25px; color: #000000">__last_version: <b><?php echo($row['version']); ?></b></div> <?php */ ?>
  </div>

  <div id="wrap">

<?php
if($_GET['module'] == 'settings') {
  $row = mysql_fetch_array(mysql_query('SELECT anon FROM stats WHERE login = \''.$_SESSION['login'].'\''));
?>
    <h4>Settings</h4>
    <div>
      <form action="/settings.xml" method="post">
        <div class="list">
          <div class="title"><input type="checkbox" name="anon" id="id_1" <?php if($row['anon']) echo('checked="checked" ') ?>/>  <label for="id_1">Anonymity</label></div>
          <div class="content">your login isn't shown in statistics on main page.</div>
        </div>
        <div class="list">
          <div class="title">
            <select name="theme">
              <option value="blue" <?php if($_COOKIE['theme'] == 'blue') echo('selected="selected"'); ?>>Blue</option>
              <option value="green" <?php if($_COOKIE['theme'] == 'green') echo('selected="selected"'); ?>>Green</option>
              <option value="gray" <?php if($_COOKIE['theme'] == 'gray') echo('selected="selected"'); ?>>Gray</option>
            </select>
            Theme</div>
          <div class="content">choose your favorite color theme.</div>
        </div>
        <br />
        <input type="submit" value="Save settings" />
      </form>
<?php
} elseif($_GET['module'] == 'news') {
?>
    <h4><?php if($_SESSION['admin']) echo('[<a href="/news.xml?action=add" title="add"><b>+</b></a>] '); ?>News</h4>
    <div>
<?php
  if($_SESSION['admin'] && in_array($_GET['action'], array('add', 'edit'))) {
    if($_GET['action'] == 'edit') {
      $row = mysql_fetch_array(mysql_query('SELECT * FROM news WHERE id = \''.$_GET['id'].'\''));
    } else {
      $row = array('date' => date('Y-m-d H:i:s'), 'author' => $_SESSION['login'], 'title' => stripslashes($_POST['title']), 'text' => stripslashes($_POST['text']));
    }

    echo('<form action="/news.xml?action='.(($_GET['action'] == 'edit') ? 'edit&amp;id='.$row['id'] : 'add').'" method="post"><div class="list"><div class="info">'.$row['author'].' @ '.$row['date'].' '.(($_GET['action'] == 'edit') ? '<input type="checkbox" name="newDate" style="border: 0px" title="set date to actual" />' : null).'</div><div class="title"><input type="text" name="title" value="'.htmlspecialchars($row['title']).'" maxlength="255" class="input" /> <input type="submit" value="&raquo;" class="submit" /></div><div class="content"><textarea name="text">'.htmlspecialchars($row['text']).'</textarea></div></div></form>');
  } else {
    if($_GET['id']) {
      $query = mysql_query('SELECT * FROM news WHERE id = \''.$_GET['id'].'\'');
    } else {
      include('inc/nav.inc.php');

      $nav = new Navigation;
      $nav->limit = 20;
      $nav->execute('SELECT * FROM news ORDER BY date DESC'); $query = $nav->sql_result;
    }
    
    while($row = mysql_fetch_array($query)) {
      $row['text'] = parse_row($row['text']);

      echo('<div class="list"><div class="info">'.$row['author'].' @ '.$row['date'].'</div><div class="title">'.(($_SESSION['admin']) ? '<span style="font-weight: normal">[<a href="/delete.xml?news[]='.$row['id'].'" title="delete"><b>x</b></a> | <a href="/news.xml?action=edit&amp;id='.$row['id'].'" title="edit"><b>e</b></a>]</span>' : null).' <a href="/news.xml?id='.$row['id'].'" title="permlink">&middot;</a> '.htmlspecialchars($row['title']).'</div><div class="content">'.nl2br($row['text']).'</div></div>');
    }
  }
} elseif($_GET['module'] == 'faq') {
?>
    <h4><?php if($_SESSION['admin']) echo('[<a href="/faq.xml?action=add" title="add"><b>+</b></a>] '); ?><abbr title="Frequently Asked Questions">FAQ</abbr></h4>
    <div>
<?php
  if($_SESSION['admin'] && in_array($_GET['action'], array('add', 'edit'))) {
    if($_GET['action'] == 'edit') {
      $row = mysql_fetch_array(mysql_query('SELECT * FROM faq WHERE id = \''.$_GET['id'].'\''));
    } else {
      $row = array('question' => stripslashes($_POST['title']), 'answer' => stripslashes($_POST['text']));
    }

    echo('<form action="/faq.xml?action='.(($_GET['action'] == 'edit') ? 'edit&amp;id='.$row['id'] : 'add').'" method="post"><div class="list"><div class="title"><input type="text" name="question" value="'.htmlspecialchars($row['question']).'" maxlength="255" class="input" /> <input type="submit" value="&raquo;" class="submit" /></div><div class="content"><textarea name="answer">'.htmlspecialchars($row['answer']).'</textarea></div></div></form>');
  } else {
    if($_GET['id']) {
      $query = mysql_query('SELECT * FROM faq WHERE id = \''.$_GET['id'].'\'');
    } else {
      include('inc/nav.inc.php');

      $nav = new Navigation;
      $nav->limit = 10;
      $nav->execute('SELECT * FROM faq'); $query = $nav->sql_result;
    }
    
    while($row = mysql_fetch_array($query)) {
      $row['answer'] = parse_row($row['answer']);

      echo('<div class="list"><div class="title">'.(($_SESSION['admin']) ? '<span style="font-weight: normal">[<a href="/delete.xml?faq[]='.$row['id'].'" title="delete"><b>x</b></a> | <a href="/faq.xml?action=edit&amp;id='.$row['id'].'" title="edit"><b>e</b></a>]</span>' : null).' <a href="/faq.xml?id='.$row['id'].'" title="permlink">&middot;</a> '.htmlspecialchars($row['question']).'</div><div class="content">'.nl2br($row['answer']).'</div></div>');
    }
  }
} elseif($_GET['module'] == 'stats' && ($_GET['login'] || $_SESSION['logged'])) {
  $i = 0; $profiles = array();
  if($_SESSION['logged'] && !$_GET['login']) $_GET['login'] = $_SESSION['login'];
  $query = mysql_query('SELECT profile, COUNT(*) AS count FROM info WHERE login = \''.$_GET['login'].'\' GROUP BY profile');
  while($row = mysql_fetch_array($query)) {
    $i++;
    if(stripslashes($_GET['profile']) == $row['profile']) $count = $row['count'];
    $profiles[] = array('name' => $row['profile'], 'count' => $row['count']);
  }

  if($i == 1) {
    $_GET['profile'] = $profiles[0]['name'];
    $count = $profiles[0]['count'];
  }

  function _amp2like($v) {
    if(ereg('&', $v)) {
      $v = ereg_replace('_', '\_', $v);
      $v = ereg_replace('%', '\%', $v);
      return("LIKE '".ereg_replace('&', '_', $v)."'");
    } else {
      return("= '$v'");
    }
  }

  if($_GET['a'] && $_GET['r']){
    $add = "WHERE artist "._amp2like($_GET['a'])." AND album "._amp2like($_GET['r']);    
    echo("<h4>Stats of '".stripslashes($_GET['a'])." - ".stripslashes($_GET['r'])."' album</h4>\n");
  } elseif($_GET['a']){
    $add = "WHERE artist "._amp2like($_GET['a']);
    echo("<h4>Stats of '".stripslashes($_GET['a'])."' artist</h4>\n");
  } elseif($_GET['r']){
    $add = "WHERE album "._amp2like($_GET['r']);
    echo("<h4>Stats of '".stripslashes($_GET['r'])."' album</h4>\n");
  } else {
    $add = null;
    if($_GET['profile']) {
      echo("<h4>General stats of '".stripslashes($_GET['profile'])."' profile</h4>\n");
    } else {
      echo('<h4>General stats</h4>'."\n");
    }
  }
  $add .= ($add) ? ' AND ' : 'WHERE ';
  $add .= ' login = \''.$_GET['login'].'\' AND profile = \''.$_GET['profile'].'\'';

  function table($query, $cols = array()) {
    global $count;
?>
      <table border="0" align="center">
        <tr>
          <?php echo((in_array('time', $cols)) ? "<th class=\"selected\">Time</th>\n" : null); ?>
          <th>Artist</th>
          <?php echo((in_array('album', $cols)) ? "<th>Album</th>\n" : null); ?>
          <?php echo((in_array('title', $cols)) ? "<th>Title</th>\n" : null); ?>
          <?php echo((in_array('length', $cols)) ? "<th>Length</th>\n" : null); ?>
          <?php echo((in_array('count', $cols)) ? "<th>Count</th>\n" : null); ?>
          <?php echo((in_array('count', $cols)) ? "<th class=\"selected\">%</th>\n" : null); ?>
        </tr>
<?php
    $query = mysql_query($query);
    if(mysql_num_rows($query)) {
      while($row = mysql_fetch_array($query)) {
        $onair = ($row['last_state'] && !$_GET['a'] && !$_GET['r']) ? is_playing($row['last_state'], $row['length'], $row['time']) : false;
        echo("<tr".(($onair) ? ' class="nowplaying" title="Now playing"' : null).">
            ".((in_array('time', $cols)) ? "<td>".((substr($row['time'], 0, 10) == date('Y-m-d')) ? 'Today, '.substr($row['time'], 10) : date('Y/m/d, H:i', strtotime($row['time'])))."</td>" : null)."
            <td><a href=\"/stats.xml?login={$_GET['login']}&amp;profile=".stripslashes($_GET['profile'])."&amp;a=".urlencode($row['artist'])."\">".htmlspecialchars($row['artist'])."</a></td>
            ".((in_array('album', $cols)) ? "<td><a href=\"/stats.xml?login={$_GET['login']}&amp;profile=".stripslashes($_GET['profile'])."&amp;a=".urlencode($row['artist']).'&amp;r='.urlencode($row['album'])."\">".(($row['album']) ? htmlspecialchars($row['album']) : '&nbsp;')."</a></td>" : null)."
            ".((in_array('title', $cols)) ? "<td><a href=\"http://www.google.com/search?ie=UTF-8&amp;q=allintitle%3A+".urlencode($row['artist'].' '.$row['title'])."+(lyrics|teksty)&amp;btnI=1\" target=\"_blank\">".htmlspecialchars($row['title'])."</a></td>" : null)."
            ".((in_array('length', $cols)) ? "<td>{$row['length']}</td>" : null)."
            ".((in_array('count', $cols)) ? "<td style=\"width: 3em\">{$row['count']}</td>" : null)."
            ".((in_array('count', $cols)) ? "<td style=\"width: 3em\">".round(($row['count'] / $count) * 100, 1)."%</td>" : null)."
          </tr>\n");
      }
    } else {
      echo('<tr>
            <td class="empty" colspan="'.(count($cols) + ((in_array('count', $cols)) ? 2 : 1)).'"> - no stats -</td>
          </tr>'."\n");
    }
?>
      </table>
<?php
  }
?>
    <div id="table">
<?php
  if(count($profiles)) {
    echo("Profiles connected to this account:\n<ul>");
    foreach($profiles AS $profile) {
      echo("<li>".(($_SESSION['admin'] || $_GET['login'] == $_SESSION['login']) ? '[<a href="/delete.xml?profile[]='.$profile['name'].'" title="Delete profile"><b>x</b></a>]' : null)." ".(($profile['name'] == $_GET['profile']) ? "<b>{$profile['name']}</b>" : "<a href=\"/stats.xml?login={$_GET['login']}&amp;profile={$profile['name']}\">{$profile['name']}</a>")." [<b>".number_format($profile['count'])."</b>]</li>\n");
    }
    echo("\n</ul>\n");
  } else {
    echo('This user doesn\'t have any logged entries.');
  }

  $artist['MusicBrainz.org'] = 'http://musicbrainz.org/newsearch.html?limit=25&amp;table=artist&amp;search=%s';
  $artist['Discogs'] = 'http://www.discogs.com/search?type=artists&amp;q=%s';
  $artist['Google'] = 'http://google.com/search?q=&quot;%s&quot;';

  $album['MusicBrainz.org'] = 'http://musicbrainz.org/newsearch.html?limit=25&amp;table=album&amp;search=%s';
  $album['Discogs'] = 'http://www.discogs.com/search?type=releases&amp;q=%s';
  $album['Google'] = 'http://google.com/search?q=&quot;%s&quot;+&quot;%s&quot;';

  if($_GET['profile']) {
?>
      Categories:
      <ul>
        <li><a href="#last">Recently played tracks</a></li>
        <li><a href="#tracks">Top 10 tracks</a></li>
        <?php if(!$_GET['r']) echo('<li><a href="#albums">Top 10 albums</a></li>'."\n"); ?>
        <?php if(!$_GET['a']) echo('<li><a href="#artists">Top 10 artists</a></li>'."\n"); ?>
      </ul>
      Click on artist or album name for more details. To get song's lyrics click on it's name.
      <?php if($_GET['a']) { ?>
      <ul>
          <?php foreach($artist AS $name => $link) echo("<li><a href=\"".sprintf($link, urlencode($_GET['a']))."\" target=\"_blank\"><b>".stripslashes($_GET['a'])."</b> on $name</a></li>\n"); ?>
      </ul>
     <?php } ?>
      <?php if($_GET['a'] && $_GET['r']) { ?>
      <ul>
          <?php foreach($album AS $name => $link) echo("<li><a href=\"".sprintf($link, urlencode($_GET['r']), urlencode($_GET['a']))."\" target=\"_blank\"><b>".stripslashes($_GET['r'])."</b> on $name</a></li>\n"); ?>
      </ul>
      <?php } elseif(!$_GET['a'] && !$_GET['r']) { ?>

      <br/><br/>
      <?php } ?>
      <h4 id="last">Recent tracks</h4> 
<?php table('SELECT artist, album, title, time, length, last_state FROM info '.$add.' ORDER BY time DESC LIMIT 10', array('time', 'album', 'title', 'length')); ?>

      <h4 id="tracks">Top 10 tracks</h4> 
<?php table('SELECT artist, album, title, COUNT(*) AS count FROM info '.$add.' GROUP BY artist, album, title ORDER BY count DESC LIMIT 10', array('album', 'title', 'count')); ?>

<?php if(!$_GET['r']) { ?>
      <h4 id="albums">Top 10 albums</h4> 
<?php table('SELECT artist, album, COUNT(*) AS count FROM info '.$add.' GROUP BY artist, album ORDER BY count DESC LIMIT 10', array('album', 'count')); ?>
<?php } ?>

<?php if(!$_GET['a']) { ?>
      <h4 id="artists">Top 10 artists</h4> 
<?php table('SELECT artist, COUNT(*) AS count FROM info '.$add.' GROUP BY artist ORDER BY count DESC LIMIT 10', array('count')); ?>
<?php }
  }
} elseif(!$_GET['module']) {
  if($_SESSION['admin']) echo('<form action="/delete.xml" method="post">');
?>

    <h4>kPilot2 usage statistics</h4>
    <div id="table">
      <table border="0" align="center">
<?php
  $fields = array(); $i = 0;
  $_c = mysql_query('SHOW COLUMNS FROM stats');
  while($row = mysql_fetch_assoc($_c)) {
    $fields[] = $row['Field'];
  } $fields[] = 'uptime';

  $_o = ($_GET['order'] && in_array($_GET['order'], $fields)) ? $_GET['order'] : 'login';
  $_d = (in_array(strtoupper($_GET['dir']), array('ASC', 'DESC'))) ? $_GET['dir'] : 'ASC';

  function _dir($field, $_t = 0) {
    global $_o, $_d, $nav;
    $_tv = array(
      array('?'.(($_GET[$nav->offset]) ? $nav->offset.'='.$_GET[$nav->offset].'&amp;' : '').'order='.$field.'&amp;dir=ASC', '?'.(($_GET[$nav->offset]) ? $nav->offset.'='.$_GET[$nav->offset].'&amp;' : '').'order='.$field.'&amp;dir=DESC'),
      array('<span class="dir" style="font-size: 9px">v</span>', '<span class="dir" style="font-size: 13px">^</span>'),
      array('', ' class="selected"')
    );
    echo(($_o == $field && ($_d == 'ASC' || $_t == 2)) ? $_tv[$_t][1] : $_tv[$_t][0]);
  }

  include('inc/nav.inc.php');

  $nav = new Navigation;
  $nav->limit = 20;
?>
        <tr>
          <th<?php _dir('login', 2); ?>><a href="<?php _dir('login'); ?>"><b><abbr title="Beta">&beta;</abbr></b> login <?php _dir('login', 1); ?></a></th>
          <th<?php _dir('player', 2); ?>><a href="<?php _dir('player'); ?>">Player <?php _dir('player', 1); ?></a></th>
          <th<?php _dir('version', 2); ?>><a href="<?php _dir('version'); ?>"><b><abbr title="kPilot2">kP</abbr></b> <abbr title="version">v</abbr> <?php _dir('version', 1); ?></a></th>
          <th<?php _dir('kversion', 2); ?>><a href="<?php _dir('kversion'); ?>"><b><abbr title="Konnekt">K</abbr></b> <abbr title="version">v</abbr> <?php _dir('kversion', 1); ?></a></th>
          <th<?php _dir('uptime', 2); ?>><a href="<?php _dir('uptime'); ?>">Uptime <?php _dir('uptime', 1); ?></a></th>
          <th<?php _dir('last_run', 2); ?>><a href="<?php _dir('last_run'); ?>">Last report <?php _dir('last_run', 1); ?></a></th>
          <th align="center">&nbsp;<b><abbr title="Last state">?</abbr></b>&nbsp;</th>
          <th class="onair" style="font-size: 12px"><b><abbr title="Played songs statistics">&#9834;</abbr></b></th>
          <?php if($_SESSION['admin']) { ?>
          <th style="font-size: 12px" align="center"><b><abbr title="Delete account">x</abbr></b></th>
          <?php } ?>
        </tr>
<?php
  $nav->execute('SELECT *, (UNIX_TIMESTAMP(last_run) - UNIX_TIMESTAMP(first_run)) AS uptime FROM stats '.(($_SESSION['admin']) ? null : 'WHERE anon = \'0\' ').'GROUP BY login ORDER BY '.$_o.' '.$_d);
  while($row = mysql_fetch_array($nav->sql_result)) {
    preg_match('/(.*) (.*)$/', $row['player'], $player);
    $stats = explode('|', $row['stats']);
    $player[1] = eregi_replace('Windows Media Player', 'WMP', $player[1]);

    echo("<tr".(($row['login']{0} == '^') ? ' class="anon" title="This user doesn\'t have account in BETA system."' : (($_SESSION['login'] == $row['login']) ? ' class="myself"' : null)).">
          <td class=\"login\">".ucfirst($row['login'])."</td>
          <td>".(($player[1]) ? ucfirst($player[1]) : '&nbsp;')." <span class=\"pversion\">{$player[2]}</span></td>
          <td".((!is_numeric(substr($row['version'], -1))) ? ' class="beta" title="This user is using unstable version."' : null).">{$row['version']}</td>
          <td>".version_kupdate($row['kversion'])."</td>
          <td class=\"time_since\">".time_since($row['first_run'], $row['last_run'])."</td>
          <td class=\"time_since\">".time_since($row['last_run'])." ago</td>
          <td class=\"state".((in_array($row['last_state'], array('INIT', 'PING')) && (time() - strtotime($row['last_run'])) <= 20*60) ? ' online" title="This user seems to be active [last state: '.$row['last_state'].'].">&uarr;' : '" title="This user seems to be inactive or disconnected [last state: '.$row['last_state'].'].">&darr;'.(($row['last_state'] != 'EXIT') ? ' ?' : null))."</td>
          <td class=\"onair\">".(($row['login']{0} != '^') ? "<a href=\"/stats.xml?login={$row['login']}\">&#9834;</a>" : '&nbsp;')."</td>
          ".(($_SESSION['admin']) ? '<td align="center" style="padding: 0px"><input type="checkbox" style="border: 0px" name="account[]" value="'.$row['login'].'" /></td>' : null)."
          <!-- OS: {$row['os']} -->
        </tr>\n");

    unset($player);
  }
  if($_SESSION['admin']) echo('<tr><td colspan="9" align="right"><input type="submit" value="Delete" /></td></tr>');
?>
      </table>
<?php
  if($_SESSION['admin']) echo('</form>');
} else {
  $err = substr($_GET['module'], -3);
  if(substr($_GET['module'], 0, 5) != 'error' || !is_numeric($err) || ereg('\.', $err)) $err = 404;

  switch($err) {
    case '400':
      $errmsg = 'Bad request';
      break;
    case '401':
      $errmsg = 'Authorization required';
      break;
    case '403':
      $errmsg = 'Forbidden';
      break;
    case '404':
      $errmsg = 'Not found';
      break;
    case '500':
      $errmsg = 'Internal server error';
      break;
    default:
      $errmsg = 'Unknown error';
      break;
  }
  echo('<h4>Error '.$err.'</h4>
  <div class="errmsg"><b>Error description</b> { '.$errmsg.' }');
}
?>
    </div>
    <div class="foot">
<?php
if($_GET['module'] == 'stats' && $_GET['login']) {
  echo(($_GET['profile']) ? '<b>'.number_format($count).'</b> songs played. Lyrics search depends on <a href="http://www.google.com/" target="_blank">Google</a>!' : 'Choose user to view detailed stats.');
} elseif(is_object($nav)) {
  if($nav->show_num_pages('<< prev', 'next >>', '|', 'class="pages"')) echo('<br/>');
  $nav->show_info();
} else {
  echo('&nbsp;');
}

$timeparts = explode(' ', microtime());
$endtime = $timeparts[1].substr($timeparts[0], 1);
echo("\n<!-- Script execution time: ".bcsub($endtime, $starttime, 6)." -->\n");
?>
    </div>
  </div>
  <div id="copy"><b>&copy;</b>2004-<?php echo(date('Y')); ?> <a href="http://sija.info/" title="[://sija.net/]">Sijawusz Pur Rahnama</a></div>
</div>

<script type="text/javascript">
  <!--
    document.writeln('<'+'scr'+'ipt type="text/javascript" src="http://s3.hit.stat.pl/_'+(new Date()).getTime()+'/script.js?id=<?php echo((!$_GET['module']) ? '10XlpKLMrRkeMcFkCXLRlJdSP6LIB67bFbr.3E3Dhtf.h7' : 'bPpLAEs6GH7ZMySjZqDBRcWoHeH1Ugbzmgu3anRqXEb.w7'); ?>&amp;l=11"></'+'scr'+'ipt>');
  //-->
</script>

</body>
</html>
<?php
// Die bitch
d13();
?>