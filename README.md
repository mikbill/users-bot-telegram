

## MB User Bot
Бот предназнчен для абонентов биллинга MikBill. Миссия: организовать работу кабинета в меседжере на сколько это возможно

Возможности:
 - Авторизация
    - Номер телефона + пароль
    - Валидация абонента по ОТП
 - Базовая информация о абоненте
 - Новости
 - Услуги:
   - Кредит
   - Заморозка/Разморозка
   - Турбо
   - Реальный IP
   - Изменение тарифа
 - Пополнение
   - Карта пополнения
   - Платежные системы (генерация url пополнения)
 - История платежей
 - История сессии
 - Тикеты
   - Список тикетов
   - Создавать тикет
   - Ответить в тикет
 - Информация о компании
 - Настройки (Изменение языка бота)


![png image](https://raw.githubusercontent.com/mikbill/users-bot-telegram/main/resources/img/image.png)

### 0. Требования
Данный бот использует LK API для работы с абонентом
 - [Установка LK API](https://wiki.mikbill.pro/billing/external/api_cabinet)

### 1. Установка

Устанвливаем пакеты и зависимости
```shell script
cd /var/www/
git clone https://github.com/mikbill/users-bot-telegram.git
cd users-bot-telegram

# даем права
mkdir -p /var/www/users-bot-telegram/storage/{sessions,views,cache}
mkdir -p /var/www/users-bot-telegram/storage/framework/{sessions,views,cache}
sudo chown -R www-data:www-data /var/www/users-bot-telegram
sudo chmod -R 775 /var/www/users-bot-telegram/storage/

composer install
```

### 2. Nginx 

создаем конфиг на публичную диреторию
/var/www/users-bot-telegram/public

в идеале вынести на отдельный поддомен, и указать его в конфиге APP_URL
для вебхука телеграма обязателен валидный сертификат
  
p.s. необходима если будет использовать вебхук

```shell script
...

   location ~ /\.git {
  	    deny all;
   }

   location / {
        root   /var/www/users-bot-telegram/public;
        index  index.php;
        try_files $uri $uri/ /index.php?$args;
   }

   location ~ \.php$ {
      include /etc/nginx/fastcgi_params;
      fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
      fastcgi_index index.php;
      fastcgi_param SCRIPT_FILENAME /var/www/users-bot-telegram/public$fastcgi_script_name;
   }

...

```

### 2.1 Apache

создаем конфиг на публичную директорию
/var/www/users-bot-telegram/public


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

Конфиг находится в корне директории, файл .env.example
Скопируйте его переименовав в .env

Необходимые к заполнению:

```shell script

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

TELEGRAM_BOT_TOKEN="your:telegram-token"
TELEGRAM_BOT_NAME="YourBotName"

MB_API_HOST="http://lkapi.ispnet.demo"
MB_API_SECRET_KEY="apikey"
MB_CABINET_HOST="http://stat.ispnet.demo"

```

### 3.1 Запускаем миграцию БД

```shell script

php artisan migrate

```

### 3.2 Сгенерируем ключ приложения


```shell script

php artisan key:generate
```

### 4. Webhook
Перед запуском нужно указать в .env ссылку на хук для бота в переменной APP_URL

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
php artisan telebot:webhook --remove
```

После чего можно запустить пулинг
```php
php artisan telebot:polling --all
```
