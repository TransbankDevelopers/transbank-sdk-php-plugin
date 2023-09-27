# Transbank PHP SDK for Plugin's
Sdk para plugin's Woocomerce, Prestashop 

![transbank-sdk-php-plugin](https://user-images.githubusercontent.com/1103494/113464583-14856a80-9404-11eb-947e-dd4dd4ff6510.png)

## Descripción
Este es el sdk para plugin's **oficial** de Transbank permite compartir funcionalidades entre los plugin's de Transbank Woocomerce, Prestashop. Está desarrollado en base al [SDK oficial de PHP](https://github.com/TransbankDevelopers/transbank-sdk-php)

## Requisitos:

- PHP 7.0+

## Dependencias
Para utilizar este SDK, debes tener las siguientes extensiones de PHP instaladas: 
- ext-curl
- ext-json
- ext-mbstring
- transbank/transbank-sdk:~2.0
- monolog/monolog
- guzzlehttp/guzzle: ^5.3.1|^6.5.8|^7.4.5


# Instalación

### Instalar con Composer

Para usar el SDK en tu proyecto **debes** usar Composer (si no lo tienes instalado, puedes instalarlo [acá](https://getcomposer.org/)), añadiendo el SDK como dependencia a tu proyecto:
```json
    {
        "repositories": [
            {
            "type": "git",
            "url": "https://github.com/TransbankDevelopers/transbank-sdk-php-plugin.git"
            }
        ],
        "require": {
            "TransbankDevelopers/transbank-sdk-php-plugin": "1.0.0"
        }
    }
```

En caso de necesitar apuntar a la rama develop

```json
    {
        "repositories": [
            {
            "type": "git",
            "url": "https://github.com/TransbankDevelopers/transbank-sdk-php-plugin.git"
            }
        ],
        "require": {
            "TransbankDevelopers/transbank-sdk-php-plugin": "develop"
        }
    }
```

Luego debes correr 
```
composer update
```


## Información para contribuir y desarrollar este SDK for plugin's

## Generar una nueva versión

Para generar una nueva versión, se debe crear un PR (con un título "Prepare release X.Y.Z" con los valores que correspondan para `X`, `Y` y `Z`). Se debe seguir el estándar semver para determinar si se incrementa el valor de `X` (si hay cambios no retrocompatibles), `Y` (para mejoras retrocompatibles) o `Z` (si sólo hubo correcciones a bugs).

En ese PR deben incluirse los siguientes cambios:

1. Modificar el archivo CHANGELOG.md para incluir una nueva entrada (al comienzo) para `X.Y.Z` que explique en español los cambios.

Luego de obtener aprobación del pull request, debes mezclar a master e inmediatamente generar un release en GitHub con el tag `X.Y.Z`. En la descripción del release debes poner lo mismo que agregaste al changelog.

## Estándares generales

- Para los commits nos basamos en las siguientes normas: https://github.com/angular/angular.js/blob/master/DEVELOPERS.md#commits👀
- Todas las mezclas a master se hacen mediante Pull Request ⬇️
- Usamos inglés para los mensajes de commit 💬
- Se pueden usar tokens como WIP en el subject de un commit separando el token con ':', por ejemplo -> 'WIP: this is a useful commit message'
- Para los nombres de ramas también usamos inglés
- Se asume que una rama de feature no mezclada, es un feature no terminado ⚠️
- El nombre de las ramas va en minúscula 🔤
- El nombre de la rama se separa con '-' y las ramas comienzan con alguno de los short lead tokens definidos a continuación, por ejemplo -> 'feat/tokens-configuration' 🌿
  
### **Short lead tokens**

`WIP` = En progreso

`feat` = Nuevos features

`fix` = Corrección de un bug

`docs` = Cambios solo de documentación

`style` = Cambios que no afectan el significado del código (espaciado, formateo de código, comillas faltantes, etc)

`refactor` = Un cambio en el código que no arregla un bug ni agrega una funcionalidad

`perf` = Cambio que mejora el rendimiento

`test` = Agregar test faltantes o los corrige

`chore` = Cambios en el build o herramientas auxiliares y librerías


## Reglas

1️⃣ -  Si no se añaden test en el pull request, se debe añadir un video o gif mostrando el cambio realizado y demostrando que la rama no rompe nada.

2️⃣ -  El pr debe tener 2 o mas aprobaciones para hacer el merge

3️⃣ - si un commit revierte  un commit anterior debera comenzar con "revert:" seguido con texto del commit anterior

## Pull Request

### Asunto ✉️

- Debe comenzar con el short lead token definido para la rama, seguido de ':' y una breve descripción del cambio
- Usar imperativos en tiempo presente: "change" no "changed" ni "changes"
- No usar mayúscula en el inicio
- No usar punto . al final

### Descripción 📃

Igual que en el asunto, usar imperativo y en tiempo presente. Debe incluir una mayor explicación de lo que se hizo en el pull request. Si no se añaden test en el pull request, se debe añadir un video o gif mostrando el cambio realizado y demostrando que la rama no rompe nada.
