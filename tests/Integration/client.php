<?php

$fp = stream_socket_client("tcp://localhost:8000", $errno, $errstr, 30);

if (!$fp) {
    echo "$errstr ($errno)<br />\n";
} else {
    fwrite($fp, "./tests/Integration/fixture.sh\n");
    while (!feof($fp)) {
        echo fgets($fp);
    }
    fclose($fp);
}
