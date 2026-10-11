<?php
$db=new mysqli('127.0.0.1','tphl_read_only','ReadOnlyPass123!','tphl_db');
$res=$db->query('DESCRIBE leads');
while($r=$res->fetch_assoc()){ echo $r['Field'].' ('.$r['Type'].')'."\n"; }
