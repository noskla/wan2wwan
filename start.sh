#!/bin/sh
php ./ssh.php &
php -S 0.0.0.0:8000 ./http.php
