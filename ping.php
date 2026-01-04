<?php
// This file keeps the server and database awake
include 'db.php'; 
echo "Heartbeat sent to database at: " . date("Y-m-d H:i:s");
?>