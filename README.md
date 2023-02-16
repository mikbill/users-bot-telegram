

## MB User Bot
Бот предназнчен для абонентов биллинга MikBill. Миссия: организовать работу кабинета в меседжере на сколько это возможно


### Возможности:
 - 
 - 
 - 
 - 
 - 
 - 
 
![png image](https://github.com/kagatan/mb-support-bot/blob/master/resources/img/image.png?raw=true)

### 1. Установка

Устанвливаем пакеты и зависимости
```shell script
cd /var/www/
git clone https://github.com/kagatan/mb-users-bot.git
cd mb-support-bot

composer install

# даем права
sudo chown -R www-data:www-data /var/www/mb-users-bot
sudo chmod -R 775 /var/www/mb-users-bot/storage/

```

### 2. Nginx 

создаем конфиг на публичную диреторию
/var/www/mb-users-bot/public

в идеале вынести на отдельный поддомен, и указать его в конфиге APP_URL
для вебхука телеграма обязателен валидный сертификат
  
p.s. необходима если будет использовать вебхук

```shell script
...

   location ~ /\.git {
  	    deny all;
   }

   location / {
        root   /var/www/mb-users-bot/public;
        index  index.php;
        try_files $uri $uri/ /index.php?$args;
   }

   location ~ \.php$ {
      include /etc/nginx/fastcgi_params;
      fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
      fastcgi_index index.php;
      fastcgi_param SCRIPT_FILENAME /var/www/mb-users-bot/public$fastcgi_script_name;
   }

...

```

### 2.1 Apache

создаем конфиг на публичную директорию
/var/www/mb-users-bot/public


пример .htaccess
```shell script

<IfModule mod_rewrite.c>
<IfModule mod_negotiation.c>
    Options -MultiViews
</IfModule>

RewriteEngine On

RewriteCond %{REQUEST_FILENAME} -d [OR]
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^ ^$1 [N]

RewriteCond %{REQUEST_URI} (\.\w+$) [NC]
RewriteRule ^(.*)$ public/$1

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule (.*) index.php
DirectoryIndex /public/index.php
</IfModule>

```

### 3. Настраиваем .env

Конфиг находится в корне директории ,файл .env

Необходимые к заполнению:

```shell script

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

TELEGRAM_BOT_TOKEN="your:telegram-token"
TELEGRAM_BOT_NAME="YourBotName"

MB_API_HOST="http://api.loc"
MB_API_SECRET_KEY=your_key
MB_CABINET_HOST="http://stat2.loc"

```

### 3.1 Запускаем миграцию БД

```shell script

php artisan migrate
```

### 4. Webhook

Установить webhook
```php
php artisan telebot:webhook --setup
```

Удалить webhook
```php
php artisan telebot:webhook --remove
```

### 5. Long pooling

Запустить в режиме пулинга без вебхука.

Чтоб запустить необходимо сначала выполнить команду 
"удалить вебхук" если он установлен
```php
php artisan telebot:polling --all
```
