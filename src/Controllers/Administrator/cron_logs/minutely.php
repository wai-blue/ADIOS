<?php
  $logDir = "{$___ADIOSObject->config['logDir']}/cron/minutely";
  
  echo $___ADIOSObject->view->Title(array("center" => l("Systémové záznamy")))->render();
  
  echo "
    <div style='padding:5px'>
  ";
  
  if (is_dir($logDir)) {
    $log_files = scandir($logDir);
    $last_log_file = "";
    $last_log_file_ts = 0;
    
    foreach ($log_files as $file) {
      if ($file != "." && $file != "..") {
        $ts = filemtime("{$logDir}/{$file}");
        if ($ts > $last_log_file_ts) {
          $last_log_file = $file;
          $last_log_file_ts = $ts;
        };
      };
    };
    
    $log = file("{$logDir}/{$file}");
    
    echo "<xmp>";
    foreach ($log as $line) {
      echo $line;
    };
    echo "</xmp>";
    
  } else {
    echo "
      <br/>
      ".l("Žiadne systémové záznamy neboli nájdené.")."
    ";
  };
  
  echo "
    </div>
  ";
?>