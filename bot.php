<?php

//create table sent (id text not null, primary key (id));
$conn = pg_connect('dbname=eqtweets');

require('simplepie.inc');
$feed = new SimplePie();
$feed->set_feed_url('http://eq.org.nz/feed/');

// Run SimplePie.
$feed->init();

echo date('Y-M-D H:i:d') ."\n";

$username = 'eqnzlive';
$pass = $argv[1];

foreach ($feed->get_items() as $item) {
  if(already_sent($item)) {
//     print "Already sent\n";
  }
  else {
  //     echo $item->get_permalink();
    echo $item->get_title();
    echo " " . $item->get_longitude();
    echo " ". $item->get_latitude();
//     echo $item->get_description();
//     echo $item->get_date('j F Y | g:i a');
    print "\n";
    send_report($item);
  }
}

function send_report($item)  {
  $id = item_id($item);
  
  if (!pg_query("INSERT INTO sent (id) VALUES('". pg_escape_string($id) ."')")) {
    die;
  }
  $title = substr(html_entity_decode($item->get_title()), 0, 100);
  GLOBAL $pass;
  $command = "curl -u eqnz_live:$pass http://identi.ca/api/statuses/update.xml ";
  $command .= " -d status='" . escapeshellcmd($title) . " #eqnz " . escapeshellcmd($item->get_permalink()) ."'";
  $command .= " -d lat='" . escapeshellcmd($item->get_latitude()) . "'";
  $command .= " -d long='"  . escapeshellcmd($item->get_longitude()).  "'";
  print "$command\n";
  shell_exec($command);
}



function item_id($item) {
  return md5($item->get_date() . $item->get_permalink());
}
function already_sent($item) {
  $id = item_id($item);
  $result = pg_query("SELECT *  FROM sent where id='". pg_escape_string($id) ."'");
  if(pg_num_rows($result)) {
    return true;
  }
  return false;
}