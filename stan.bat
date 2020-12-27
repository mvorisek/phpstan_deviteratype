@echo off
setlocal enabledelayedexpansion

phpw . 1GB -d disable_functions= vendor/phpstan/phpstane/bin/phpstan analyse --ansi --debug
