#!/bin/bash
set -xe

serviceDir="$1"
hostPort="$2"
dockerPort="$3"
name="$4"
innerHostPort="$5"
innerDockerPort="$6"

cd "$serviceDir" || exit 1

containerId=$(sudo docker ps -a | grep "$name" | grep -v "grep" | awk '{print $1}')
if [[ ! -z "$containerId" ]]; then
   sudo docker stop "$containerId"
fi

cd $serviceDir

sudo rm composer.lock || true

sudo docker run --rm --volume $serviceDir:/opt/www gateio_hyperf_81 composer install --no-dev -o

sudo docker run -d  --rm --add-host "gateio-service-uniloan-inner.app-gateio.ntrnl-tke-dev.gateio:10.1.20.11" --add-host "gateio-service-data-inner.app-gateio.ntrnl-tke-dev.gateio:10.1.20.11" --add-host "gateio-service-data-cente-inner.app-gateio.ntrnl-tke-dev.gateio:124.156.123.72" --add-host "gateio-service-financial-inner.app-gateio.ntrnl-tke-dev.gateio:10.1.20.11"  --add-host "gateio-service-data-center.app-gateio.ntrnl-tke-dev.gateio:124.156.123.72"  --add-host "gateio-service-futures-inner.app-gateio.ntrnl-tke-dev.gateio:10.1.20.11"  --add-host "gateio-service-staking-inner.app-gateio.ntrnl-tke-dev.gateio:10.1.20.11" --add-host "gateio-service-spot-inner.app-gateio.ntrnl-tke-dev.gateio:10.1.20.11" --add-host "gateio-service-settings-innovate-inner.app-gateio.ntrnl-tke-dev.gateio:10.1.20.11"  --add-host "gateio-service-compliance.app-gateio.ntrnl-tke-dev.gateio:124.156.123.72" --add-host "gateio-service-compliance-inner.app-gateio.ntrnl-tke-dev.gateio:124.156.123.72" --add-host "gateio-service-risk-inner.app-gateio.ntrnl-tke-dev.gateio:124.156.123.72" --add-host "gateio-service-usercenter-inner.app-gateio.ntrnl-tke-dev.gateio:10.1.20.11" --add-host "gateio-service-settings-inner.app-gateio.ntrnl-tke-dev.gateio:10.1.20.11"  --name "$name" -v $serviceDir:/opt/www -p "$hostPort":"$dockerPort" -e PORT="$dockerPort" -p "$innerHostPort":"$innerDockerPort" -e PORT="$innerDockerPort"  gateio_hyperf_81 php /opt/www/bin/hyperf.php start

sudo docker logs -f $name

echo "success"

exit 0