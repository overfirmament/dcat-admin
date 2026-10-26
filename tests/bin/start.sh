#!/usr/bin/env bash

set -e

cd ./laravel-tests
export DISPLAY=:99.0
#sudo Xvfb :99.0 &
sudo chmod -R 0755 ./vendor/laravel/dusk/bin/

nohup php artisan serve --host=127.0.0.1 --port=8300 > /tmp/dcat-admin-server.log 2>&1 &

for _ in $(seq 1 30); do
    if curl -fsS http://127.0.0.1:8300 > /dev/null; then
        exit 0
    fi

    sleep 1
done

cat /tmp/dcat-admin-server.log
exit 1
