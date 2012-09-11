<html>
<head>
<title>Risky</title>
<link rel="stylesheet" href="risky.css" type="text/css" />

<script type="text/javascript">
function setflagdate(value)
{
    document.forms['reminder'].elements["flagdate"].value = value;
}
</script>

</head>
<body>
<h1>Entry</h1>
<?php

require "utils.inc.php";

try {
   $dbh = new PDO('sqlite:risky.dair');
   $dbh->exec('PRAGMA foreign_keys = ON');

   $r_action = 'view';
   extract($_REQUEST, EXTR_PREFIX_ALL|EXTR_REFS, 'r');
   if(!isset($_REQUEST['back'])) {
    $r_back = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:".";
   }

   if($r_action == 'new') {
     unset($r_id);
   }

   if($r_action == 'X' && !isset($r_keywordid) && !isset($r_noteid) && !isset($r_actionid)) {
     $dbh->exec('DELETE FROM entries WHERE id='.$dbh->quote($r_id));
     unset($r_id);
   }
   if($r_action == 'X' && isset($r_participantid)) {
     $dbh->exec('DELETE FROM participants WHERE ROWID='.$dbh->quote($r_participantid));
   }
   if($r_action == 'X' && isset($r_keywordid)) {
     $dbh->exec('DELETE FROM keywords WHERE ROWID='.$dbh->quote($r_keywordid));
   }
   if($r_action == 'X' && isset($r_noteid)) {
     $dbh->exec('DELETE FROM notes WHERE ROWID='.$dbh->quote($r_noteid));
   }

   if($r_parentid == '') { $r_parentid = null; }
   if($r_probability == '') { $r_probability = null; }
   if($r_impact == '') { $r_impact = null; }
   if($r_urgency == '') { $r_urgency = null; }
   if($r_effort == '') { $r_effort = null; }
   if($r_owner == '') { $r_owner = null; }
   if($r_source == '') { $r_source = null; }
   if($r_deadline == '') { $r_deadline = null; }
   if($r_flagdate == '') { $r_flagdate = null; }
   if($r_author == '') { $r_author = null; }
   if($r_note == '') { $r_note = null; }
   if($r_asummary == '') { $r_asummary = null; }
   if($r_contingency == '') { $r_contingency = null; }
   if($r_created == '') { $r_created = null; }

   if(isset($r_participant)) {
     $dbh->exec('INSERT INTO participants VALUES('.$dbh->quote($r_participant).",".$dbh->quote($r_id).")");
   }
   if(isset($r_keyword)) {
     $dbh->exec('INSERT INTO keywords VALUES('.$dbh->quote($r_keyword).",".$dbh->quote($r_id).")");
   }

   if(isset($r_note)) {
     $dbh->exec('INSERT INTO notes (author, summary, entryid) VALUES('.$dbh->quote($r_author).",".$dbh->quote($r_note).",".$dbh->quote($r_id).")");
   }

   if($r_action == 'save' && !isset($r_id)) {	// new entry
     $sth = $dbh->prepare('INSERT INTO entries (project, type, category, title, summary, contingency, owner, source, status, probability, impact, urgency, effort, strategy, deadline, parentid) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
     $sth->execute(array($r_project, $r_type, $r_category, $r_title, $r_summary, $r_contingency, $r_owner, $r_source, $r_status, $r_probability, $r_impact, $r_urgency, $r_effort, $r_strategy, $r_deadline, $r_parentid));
     $r_id = $dbh->lastInsertId();
     $r_action = null;
   }

   if(($r_action == 'reminder') && isset($r_id)) {	// existing entry
     $dbh->exec('UPDATE entries SET flagdate = DATE('.$dbh->quote($r_flagdate).') WHERE id='.$dbh->quote($r_id));
   }

   if($r_action == 'save' && isset($r_id)) {	// existing entry
       $sth = $dbh->prepare('UPDATE entries SET project=?, type=?, category=?, title=?, summary=?, contingency=?, owner=?, source=?, status=?, probability=?, impact=?, urgency=?, effort=?, strategy=?, deadline=?, parentid=?, timestamp=IFNULL(DATE(?), timestamp) WHERE id=?');
       $sth->execute(array($r_project, $r_type, $r_category, $r_title, $r_summary, $r_contingency, $r_owner, $r_source, $r_status, $r_probability, $r_impact, $r_urgency, $r_effort, $r_strategy, $r_deadline, $r_parentid, $r_created, $r_id));
   }

   $row = $dbh->query('SELECT id,DATE(timestamp) AS created,DATE(updated, \'localtime\') AS modified,* FROM entries WHERE id='.$dbh->quote($r_id))->fetch();

     if(!isset($row['id'])) {
       $row['type'] = 'risk';
       $row['status'] = 'new';
       $row['strategy'] = 'accept';
       if(isset($r_parentid)) { $row['parentid'] = $r_parentid; }
       if(isset($r_category)) { $row['category'] = $r_category; }
       if(isset($r_project)) { $row['project'] = $r_project; }
       if(isset($r_type)) { $row['type'] = $r_type; }
     }

   if(isset($row['parentid'])) { $r_parentid = $row['parentid']; }
   $entries[''] = '';
   foreach($dbh->query('SELECT id,type,title FROM entries ORDER BY open DESC,title') as $entry) {
     $entries[$entry['title'].' ('.$entry['type'].')'] = $entry['id'];
   }

     print "<form method=\"POST\" action=\"".$r_back."\"><input class=\"button\" type=\"submit\" value=\"return to list\" name=\"action\"></form>\n";
     print "<form id=\"entry\" method=\"POST\" action=\"".$_SERVER['PHP_SELF']."\">\n";
     print "<input type=\"hidden\" value=\"".$r_back."\" name=\"back\">\n";
     print "<input type=\"hidden\" value=\"save\" name=\"action\">\n";
     if(isset($row['id'])) {
       print "<input type=\"hidden\" value=\"".$row['id']."\" name=\"id\">\n";
     }
     print "<input type=\"image\" src=\"save.png\" title=\"save changes\">";
     print "<p>".(isset($row['parentid'])?"<a href=\"?back=".urlencode($r_back)."&id=".htmlspecialchars($row['parentid'])."\">parent</a>:":"parent:").form_select('parentid', $entries, $r_parentid)."\n";
     print "<p>type:".form_select('type', array('risk'=>'risk','issue'=>'issue', 'action'=>'action', 'opportunity'=>'opportunity', 'decision'=>'decision'), $row['type'])."\n";
     print "<p>project:<br><input type=\"text\" name=\"project\" size=40 value=\"".htmlspecialchars($row['project'])."\">\n";
     print "<p>category:<br><input type=\"text\" name=\"category\" size=40 value=\"".htmlspecialchars($row['category'])."\">\n";
     print "<p>title:<br><input type=\"text\" name=\"title\" size=40 value=\"".htmlspecialchars($row['title'])."\">\n";
     print "<p>summary:<br><textarea type=\"text\" cols=40 rows=3 name=\"summary\">".htmlspecialchars($row['summary'])."</textarea>\n";
     print "<p>contingency:<br><textarea type=\"text\" cols=40 rows=3 name=\"contingency\">".htmlspecialchars($row['contingency'])."</textarea>\n";
     print "<p>owner:<br><input type=\"text\" name=\"owner\" size=40 value=\"".htmlspecialchars($row['owner'])."\">\n";
     print "<p>source:<br><input type=\"text\" name=\"source\" size=40 value=\"".htmlspecialchars($row['source'])."\">\n";
     print "<p>status:".form_select('status', array('new'=>'new','assessed'=>'assessed', 'open'=>'open', 'on hold'=> 'on hold', 'closed'=>'closed', 'solved'=>'solved'), $row['status'])."\n";
     print "<p>probability:".form_select('probability', array(''=>'', 'neutral'=>'1', 'unlikely'=>'2','probable'=>'3', 'almost certain'=>'5'), $row['probability'])."\n";
     print "<p>impact:".form_select('impact', array(''=>'', 'neutral'=>'1', 'low'=>'2','high'=>'3', 'critical'=>'5'), $row['impact'])."\n";
     print "<p>urgency:".form_select('urgency', array(''=>'', 'neutral'=>'1', 'normal'=>'2','urgent'=>'3', 'already late'=>'5'), $row['urgency'])."\n";
     print "<p>effort:".form_select('effort', array(''=>'', 'neutral'=>'1', 'low'=>'2','high'=>'3', 'huge'=>'5'), $row['effort'])."\n";
     print "<p>strategy:".form_select('strategy', array('accept'=>'accept','mitigate'=>'mitigate', 'transfer'=>'transfer', 'avoid'=>'avoid'), $row['strategy'])."\n";
     print "<p>deadline:<br><input type=\"text\" name=\"deadline\" id=\"deadline\" size=12 value=\"".htmlspecialchars($row['deadline'])."\">\n";
     if(isset($row['created'])) { print "<p>created:<br><input type=\"text\" name=\"created\" id=\"created\" size=12 value=\"".htmlspecialchars($row['created'])."\">\n"; }
     if(isset($row['modified'])) { print "updated: ".htmlspecialchars($row['modified'])."\n"; }

     print "</form>\n";

   if(isset($r_id))
   {
   print "<div id=\"tags\"><h2>Tags</h2>";
   print "<form method=\"POST\">\n";
     print "<input type=\"hidden\" value=\"".$r_back."\" name=\"back\">\n";
   print "<input type=\"text\" name=\"keyword\">\n";
   print "<input type=\"hidden\" value=\"".$row['id']."\" name=\"id\">";
   print "<input class=\"button\" type=\"submit\" value=\"add\" name=\"action\">";
   print "</form>\n";

   foreach($dbh->query('SELECT ROWID AS id,keyword FROM keywords WHERE entryid='.$dbh->quote($r_id)) as $tags) {
     print "<form method=\"POST\">\n";
     print "<input type=\"hidden\" value=\"".$r_back."\" name=\"back\">\n";
     print "<input type=\"hidden\" value=\"".$tags['id']."\" name=\"keywordid\">";
     print "<input type=\"hidden\" value=\"X\" name=\"action\">";
     print "<input class=\"button\" type=\"submit\" value=\"&#x2716; ".htmlspecialchars($tags['keyword']).'"> ';
     print "</form>\n";
   }
   print "</div>\n";
   print "<div id=\"participants\"><h2>Participants</h2>";
   print "<form method=\"POST\">\n";
     print "<input type=\"hidden\" value=\"".$r_back."\" name=\"back\">\n";
   print "<input type=\"text\" name=\"participant\">\n";
   print "<input type=\"hidden\" value=\"".$row['id']."\" name=\"id\">";
   print "<input class=\"button\" type=\"submit\" value=\"add\" name=\"action\">";
   print "</form>\n";

   foreach($dbh->query('SELECT ROWID AS id,participant FROM participants WHERE entryid='.$dbh->quote($r_id)) as $participants) {
     print "<form method=\"POST\">\n";
     print "<input type=\"hidden\" value=\"".$r_back."\" name=\"back\">\n";
     print "<input type=\"hidden\" value=\"".$participants['id']."\" name=\"participantid\">";
     print "<input type=\"hidden\" value=\"X\" name=\"action\">";
     print "<input class=\"button\" type=\"submit\" value=\"&#x2716; ".htmlspecialchars($participants['participant']).'"> ';
     print "</form>\n";
   }
   print "</div>\n";
   print "<h2>Reminder</h2><form id=\"reminder\" method=\"POST\">\n";
   print "<input type=\"hidden\" value=\"".$r_back."\" name=\"back\">\n";
   print "<input type=\"hidden\" value=\"".$row['id']."\" name=\"id\">";
   print "<input type=\"hidden\" value=\"reminder\" name=\"action\">";
   //print "<input class=\"button\" type=\"submit\" value=\"".($row['flagged']?"un":"")."flag\" name=\"action\">";
   print "<input type=\"text\" name=\"flagdate\" id=\"flagdate\" size=12 value=\"".htmlspecialchars($row['flagdate'])."\">\n";
   print "<button class=\"button\" >OK</button>";
   print "<button class=\"button\" onClick=\"setflagdate('')\">remove</button>";
   print "<button class=\"button\" onClick=\"setflagdate('".date('Y-m-d')."')\">today</button> ";
   print "<button class=\"button\" onClick=\"setflagdate('".date('Y-m-d', strtotime($row['flagdate'].'+1 day'))."')\">+1 day</button> ";
   print "<button class=\"button\" title=\"7 days\" onClick=\"setflagdate('".date('Y-m-d', strtotime($row['flagdate'].'+1 week'))."')\">+1 week</button> ";
   print "<button class=\"button\" title=\"4 weeks\" onClick=\"setflagdate('".date('Y-m-d', strtotime($row['flagdate'].'+4 week'))."')\">+1 month</button> ";
   print "<button class=\"button\" title=\"26 weeks\" onClick=\"setflagdate('".date('Y-m-d', strtotime($row['flagdate'].'+26 week'))."')\">+ 6 months</button> ";
   print "</form>\n";

   if(isset($row['id'])) {
   print "<div id=\"children\"><h2>Children</h2>";
   print "<table>\n";
   foreach($dbh->query('SELECT * FROM entries WHERE parentid='.$dbh->quote($r_id).'') as $child) {
     print "<tr><td>";
     print "<form method=\"POST\">\n";
     print "<input type=\"hidden\" value=\"".$r_back."\" name=\"back\">\n";
     print "<input type=\"hidden\" value=\"".$child['id']."\" name=\"id\">";
     print "<input class=\"button\" type=\"submit\" value=\"view\" name=\"action\"></form></td><td ".($child['open']?'':'class="closed"').">".reformat(htmlspecialchars($child['title']));
     print "</td>\n";
     print "<td ".($child['open']?'':'class="closed"').">".htmlspecialchars($child['type'])."</td>";
     print "<td ".($child['open']?'':'class="closed"').">".reformat(htmlspecialchars($child['owner']))."</td>";
     print "</tr>";
   }
   print "</table>";
   print "<form method=\"POST\">\n";
     print "<input type=\"hidden\" value=\"".$r_back."\" name=\"back\">\n";
   print "<input type=\"hidden\" value=\"".$r_id."\" name=\"parentid\">";
   print "<input type=\"hidden\" value=\"".$row['category']."\" name=\"category\">";
   print "<input type=\"hidden\" value=\"".$row['project']."\" name=\"project\">";
   print "<input type=\"hidden\" value=\"action\" name=\"type\">";
   print "<input class=\"button\" type=\"submit\" value=\"new\" name=\"action\">";
   print "</form>\n";
   print "</div>";
   }

   print "<div id=\"notes\"><h2>Notes</h2>";
   print "<table>\n";
   foreach($dbh->query('SELECT ROWID AS id,DATE(timestamp, \'localtime\') AS date,TIME(timestamp, \'localtime\') AS time,* FROM notes WHERE entryid='.$dbh->quote($r_id).' ORDER by timestamp DESC') as $tags) {
     print "<tr><td>";
     print "<form method=\"POST\">\n";
     print "<input type=\"hidden\" value=\"".$r_back."\" name=\"back\">\n";
     print "<input type=\"hidden\" value=\"".$tags['id']."\" name=\"noteid\">";
     print "<input class=\"button\" type=\"submit\" value=\"X\" name=\"action\">";
     print "</form></td>\n";
     print "<td title=\"".htmlspecialchars($tags['time'])."\">".htmlspecialchars($tags['date'])."</td>";
     print "<td>".reformat(htmlspecialchars($tags['author']))."</td>";
     print "<td>".reformat(htmlspecialchars($tags['summary']))."</td>";
     print "</tr>";
   }
   print "</table>";
   print "<form  method=\"POST\">\n";
     print "<input type=\"hidden\" value=\"".$r_back."\" name=\"back\">\n";
   print "<input type=\"hidden\" value=\"".$row['id']."\" name=\"id\">";
   print "from:<input type=\"text\" name=\"author\">\n";
   print "<p>note:<br><textarea type=\"text\" cols=40 rows=3 name=\"note\"></textarea>\n";
   print "<input class=\"button\" type=\"submit\" value=\"add\" name=\"action\">";
   print "</form>\n";
   print "</div>";


     if(isset($row['id'])) {
       print "<form id=\"trash\" method=\"POST\"><input type=\"hidden\" value=\"X\" name=\"action\">\n";
     print "<input type=\"hidden\" value=\"".$r_back."\" name=\"back\">\n";
       print "<input type=\"image\" src=\"trash.png\" title=\"delete this entry\">";
       print "<input type=\"hidden\" value=\"".$row['id']."\" name=\"id\"></form>";
     }

   }

   $dbh = null;
} catch (PDOException $e) {
   print "Error!: " . $e->getMessage() . "<br/>";
   die();
}
?>
</body>
</html>

