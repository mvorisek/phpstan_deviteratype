@echo off
setlocal enabledelayedexpansion

cd vendor/atk4/ui && phpw ../../.. 1GB -d disable_functions= ../../phpstan/phpstan/phpstan analyse --ansi
