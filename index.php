<html>
<head>
<title>Risky</title>
<link rel="stylesheet" href="risky.css" type="text/css" />
</head>
<body>
<h1>Risky</h1>
<?php

require "utils.inc.php";

try {
   $dbh = new PDO('sqlite:risky.dair');

   $sort = array('1');

   if(isset($_REQUEST['type'])) { array_push($sort, 'type='.$dbh->quote($_REQUEST['type'])); }
   if(isset($_REQUEST['project'])) { array_push($sort, 'project='.$dbh->quote($_REQUEST['project'])); }
   if(isset($_REQUEST['category'])) { array_push($sort, 'category='.$dbh->quote($_REQUEST['category'])); }
   if(isset($_REQUEST['owner'])) { array_push($sort, 'owner='.$dbh->quote($_REQUEST['owner'])); }
   if(isset($_REQUEST['status'])) { array_push($sort, 'status='.$dbh->quote($_REQUEST['status'])); }
   if(isset($_REQUEST['tag'])) { array_push($sort, 'tag='.$dbh->quote($_REQUEST['tag'])); }
   if(isset($_REQUEST['person'])) { array_push($sort, 'person='.$dbh->quote($_REQUEST['person'])); }
   if(isset($_REQUEST['deadline'])) { array_push($sort, 'deadline<='.$dbh->quote($_REQUEST['deadline'])); }
   if(isset($_REQUEST['created'])) { array_push($sort, 'created<='.$dbh->quote($_REQUEST['created'])); }
   if(isset($_REQUEST['modified'])) { array_push($sort, 'modified>='.$dbh->quote($_REQUEST['modified'])); }
   if(isset($_REQUEST['score'])) { array_push($sort, 'score>='.($_REQUEST['score']*1.0)); }
   if(isset($_REQUEST['parentid'])) { array_push($sort, 'parentid='.$dbh->quote($_REQUEST['parentid'])); }
   if(isset($_REQUEST['flagged'])) { array_push($sort, 'flagged'); }

   print "<h2>Tags</h2>\n";
   foreach ($dbh->query('SELECT DISTINCT tag FROM tags ORDER BY tag') as $row) {
     print "<a class=\"tag\" href=\"?tag=".urlencode($row['tag'])."\">".htmlspecialchars($row['tag'])."</a>\n";
   }

   print "<h2>People</h2>\n";
   foreach ($dbh->query('SELECT DISTINCT person FROM persons WHERE person NOT NULL AND person!=\'\' ORDER BY person') as $row) {
     print "<a class=\"tag\" href=\"?person=".urlencode($row['person'])."\">".htmlspecialchars($row['person'])."</a>\n";
   }

   print "<h2>Entries</h2>\n";
   print '<a href="?status=new">INBOX</a> <a href="?">All</a> <a href="entry.php">New</a>';
   print "<table class=\"list\">\n";
   print "<tr>";
   print "<th></th>";
   print "<th>ID</th>";
   print "<th></th>";
   print "<th></th>";
   print "<th>project</th>";
   print "<th>category</th>";
   print "<th>title</th>";
   print "<th>owner</th>";
   print "<th>status</th>";
   print "<th>score</th>";
   print "<th>due</th>";
   print "<th>age</th>";
   print "<th>activity</th>";
   print "</tr>\n";
   foreach ($dbh->query('SELECT probability*impact AS score,entries.ROWID AS id,DATE(deadline) AS due,open*(DATE(\'now\', \'localtime\')>=deadline) AS overdue,julianday(deadline)-julianday(\'now\') as duein,DATE(timestamp, \'localtime\') AS created,julianday(\'now\')-julianday(timestamp) AS age,open*100/(1+(JULIANDAY(\'now\')-JULIANDAY(updated))) AS activity,DATE(updated,\'localtime\') AS modified,entries.ROWID IN (SELECT parentid FROM entries) AS linked,* FROM entries,tags,persons WHERE persons.entryid=id AND tags.entryid=id AND '.join(' AND ', $sort).' GROUP BY id ORDER BY open DESC,open*probability*impact DESC,deadline') as $row) {
     print "<tr ".($row['open']?'':'class="closed"').">";
     print "<td><a href=\"?".join('&', array('type='.urlencode($row['type']), $_SERVER['QUERY_STRING']))."\">".htmlspecialchars($row['type'])."</a></td>";
     print "<td><a href=\"entry.php?id=".$row['id']."\">".$row['id']."</td>";
     print "<td>".(isset($row['linked'])?'<a href="?parentid='.$row['id'].'"><img src="paperclip.png" border="0"></a>':"")."</td>";
     print "<td><a href=\"?flagged=1\">".($row['flagged']?"&#9873;":"")."</a></td>";
     print "<td><a href=\"?project=".$row['project']."\">".htmlspecialchars($row['project'])."</td>";
     print "<td><a href=\"?".join('&', array('category='.urlencode($row['category']), $_SERVER['QUERY_STRING']))."\">".htmlspecialchars($row['category'])."</a></td>";
     print "<td><a href=\"entry.php?id=".$row['id']."\" title=\"".htmlspecialchars($row['summary'])."\">".htmlspecialchars($row['title'])."</td>";
     print "<td>".reformat(htmlspecialchars($row['owner']))."</td>";
     print "<td><a href=\"?status=".urlencode($row['status'])."\">".htmlspecialchars($row['status'])."</a></td>";
     print "<td><a href=\"?score=".urlencode($row['score'])."\">".htmlspecialchars($row['score'])."</a></td>";
     print "<td><a ".($row['overdue']?"class=\"overdue\"":"")." href=\"?deadline=".urlencode($row['due'])."\">".htmlspecialchars($row['due'])."</a></td>";
     print "<td><a href=\"?created=".urlencode($row['created'])."\" title=\"".htmlspecialchars($row['timestamp'])."\">".htmlspecialchars(round($row['age']))."</a></td>";
     print "<td><a href=\"?modified=".urlencode($row['modified'])."\" title=\"".htmlspecialchars($row['updated'])."\">".htmlspecialchars(round($row['activity']))."%</a></td>";
     print "</tr>";
   }
   print "</table>\n";


   $dbh = null;
} catch (PDOException $e) {
   print "Error!: " . $e->getMessage() . "<br/>";
   die();
}
?>
</body>
</html>

