# esoTalk 2.0.0

**License:** GNU Affero General Public License v3.0 (AGPLv3) — see `LICENSE.txt`

Foro ligero modernizado para PHP 8.0–8.4 (XAMPP / Apache / MariaDB).

Mantenido en: **https://github.com/Scoppettuolo**

Blog / info: **https://katnya.blogspot.com/**

> Esta línea es la continuación modernizada de esoTalk 1.x (código g4/g5).  
> Toby Zerner pasó después a **Flarum** (Fork de esoTalk).

## Requisitos
- PHP ≥ 8.0 (pdo_mysql, gd, mbstring)
- MySQL 5.7+ / MariaDB 10.3+

## Instalación
1. Copia a la carpeta del servidor web
2. Abre en el navegador y completa el instalador
3. Config recomendada:

```php
$config["esoTalk.cache"] = "file";
$config["esoTalk.urls.friendly"] = false;
$config["esoTalk.urls.rewrite"] = false;
$config["esoTalk.database.host"] = "127.0.0.1";
```

## Changelog 2.0.0
- Identidad de versión unificada como **2.0.0**
- PHP 8