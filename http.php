<?php

$out = "Error";

if ($_SERVER["REQUEST_URI"] == "/script.js") {
    header("Content-Type: text/javascript");
    $out = file_get_contents("./http/script.js");
} elseif ($_SERVER["REQUEST_URI"] == "/style.css") {
    header("Content-Type: text/css");
    $out = file_get_contents("./http/style.css");
} elseif ($_SERVER["REQUEST_URI"] == "/status.json") {
    header("Content-Type: application/json");
    $out = file_get_contents("./status.json");
} else
    $out = file_get_contents("./http/index.html");

echo $out;
