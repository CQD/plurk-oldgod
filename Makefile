.PHONY: deploy installNoDev installWithDev deploy soft-deploy server post-deploy real-deploy

OPTIONS?=
PROJECT_ID?='plurk-oldgod'
VERSION?='php-master'

installNoDev:
	composer install -o --no-dev

installWithDev:
	composer install -o

deploy: installNoDev
	@$(MAKE) real-deploy OPTIONS="--promote --stop-previous-version $(OPTIONS)"

soft-deploy: installNoDev
	@$(MAKE) real-deploy OPTIONS="--no-promote --no-stop-previous-version $(OPTIONS)"

real-deploy:
	gcloud app deploy -v $(VERSION)  --project=$(PROJECT_ID) $(OPTIONS)
	@$(MAKE) post-deploy

post-deploy:
	@echo "\033[1;33mDeploy done.\033[m"

server: installWithDev
	php -S localhost:8080 -t public/
