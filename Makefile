.PHONY: deploy installNoDev installWithDev test server e2e

installNoDev:
	composer install -o --no-dev

installWithDev:
	composer install -o

deploy: installNoDev
	gcloud app deploy -v 'php-master'  --project='plurk-oldgod'

DATASTORE_EMULATOR_HOST:=localhost:8081
test: installWithDev
	$(eval export DATASTORE_EMULATOR_HOST=$(DATASTORE_EMULATOR_HOST))
	@echo "\033[1;33mStarting datastore emulator\033[m"
	@gcloud beta emulators datastore start --no-store-on-disk  --host-port=$${DATASTORE_EMULATOR_HOST} 2> build/datastore_emulator.log &
	@sleep 2
	@curl -s $$DATASTORE_EMULATOR_HOST  && echo "\033[1;33mDatastore emulator is up at $${DATASTORE_EMULATOR_HOST}\033[m" || (echo "\033[1;31mDatastore emulator not up /__\\ \033[m" && exit -1)
	@echo "\033[1;33mRunning unit test\033[m" && ./vendor/bin/phpunit --coverage-html build/coverage/
	@echo "\033[1;33mShutting down datastore emulator\033[m" && curl -X POST $${DATASTORE_EMULATOR_HOST}/shutdown
