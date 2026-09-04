@echo off
REM Arranca Gestora en dos terminales, una por proyecto.
REM Las rutas se resuelven desde la ubicacion de este script, asi que el
REM repositorio puede estar en cualquier carpeta.

setlocal
set "ROOT=%~dp0.."

echo Iniciando Gestora...
echo   Backend  : http://localhost:5240
echo   Frontend : http://localhost:8080
echo.

start "Gestora - Backend" powershell -NoExit -Command "Set-Location '%ROOT%\backend\Gestora.API'; dotnet run"

REM Margen para que la API levante antes de que el navegador pida datos.
timeout /t 5 /nobreak >nul

start "Gestora - Frontend" powershell -NoExit -Command "Set-Location '%ROOT%\frontend'; npm run dev"

echo Listo. Abra http://localhost:8080 cuando ambas terminales esten arriba.
endlocal
