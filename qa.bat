@echo off
REM QA PideAca: corre backend + barrido de navegador y abre el reporte.
REM Requiere XAMPP (Apache + Postgres) en marcha.
set QA_EMAIL=qa.robot@pideaca.com
set QA_PASSWORD=QaRobot2026!
cd /d %~dp0
if exist qa\logs\.lock (
  echo Ya hay una corrida QA en curso. Espera a que termine (no correr dos a la vez rompe los tests de uploads).
  pause
  exit /b 1
)
echo running > qa\logs\.lock
echo [1/3] Tests backend...
C:\xampp\php\php.exe vendor/phpunit/phpunit/phpunit --log-junit qa/logs/junit.xml > qa/logs/phpunit.log 2>&1
echo [2/3] Barrido humo del navegador...
cd qa
call npx playwright test > logs/smoke.log 2>&1
echo [3/3] Reporte...
node report.js
del ..\qa\logs\.lock
start logs/report.html
echo Listo. Revisa el reporte en tu navegador.
