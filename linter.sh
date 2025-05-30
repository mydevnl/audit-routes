#!/bin/bash

set -e

echo -e "\n🛠  Running PHP CS Fixer...\n"
./vendor/bin/php-cs-fixer fix --diff --verbose --show-progress=dots --ansi

echo -e "\n🤖  Running Rector...\n"
./vendor/bin/rector --ansi

echo -e "\n\n👮️  Running PHP CodeSniffer...\n"
./vendor/bin/phpcs --colors -ps

echo -e "\n\n🔍️  Running PHPStan...\n"
./vendor/bin/phpstan analyse --ansi
