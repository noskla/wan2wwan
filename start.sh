#!/bin/sh
php ./ssh.php &
php -q -S 0.0.0.0:8000 ./http.php
