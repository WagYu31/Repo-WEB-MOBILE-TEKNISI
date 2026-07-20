<?php
echo "<pre>";
echo "<h2>Server Git Status & Log</h2>";
echo "<b>Current Directory:</b> " . getcwd() . "\n\n";
echo "<b>Git Status:</b>\n";
echo shell_exec("git status 2>&1") . "\n";
echo "<b>Last 3 Commit Logs:</b>\n";
echo shell_exec("git log -n 3 --oneline 2>&1") . "\n";
echo "</pre>";
?>
