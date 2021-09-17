.PHONY: deploy installNoDev installWithDev deploy soft-deploy server post-deploy real-deploy

OPTIONS?=
PROJECT_ID?='plurk-oldgod'
VERSION?='php-master'

installNoDev:
	composer install -o --no-dev

installWithDev:
	composer install -o

deploy:
	@$(MAKE) real-deploy OPTIONS="--promote --stop-previous-version $(OPTIONS)"

soft-deploy:
	@$(MAKE) real-deploy OPTIONS="--no-promote --no-stop-previous-version $(OPTIONS)"

real-deploy: installNoDev config.php
	gcloud app deploy -v $(VERSION)  --project=$(PROJECT_ID) $(OPTIONS)
	@$(MAKE) post-deploy

post-deploy:
	@echo "\033[1;33mDeploy done.\033[m"

config.php:
	@echo "\033[1;31m沒有設定 $@ ，無法 deploy，請參考 config.example.php 設定 config.php!\033[m" && exit -1

server: installWithDev
	php -S localhost:8080 -t public/

test: installWithDev
	vendor/bin/phpunit --testdox tests/ $(OPTIONS)
