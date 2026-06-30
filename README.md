# WB API Parser

Laravel-приложение для выгрузки данных из API Wildberries-подобного сервиса в MySQL.

## База данных (Aiven MySQL)

| Параметр | Значение |
|----------|----------|
| Host | `mysql-1fdee782-d805.h.aivencloud.com` |
| Port | `24405` |
| База | `defaultdb` |
| Пользователь | `avnadmin` |
| Пароль | указать свой (см. .env) |
| SSL | Обязательный |

### Таблицы

| Таблица | Описание |
|---------|----------|
| `sales` | Продажи |
| `orders` | Заказы |
| `stocks` | Остатки складов |
| `incomes` | Доходы |

## Установка и запуск

```bash
composer install

cp .env.example .env

php artisan key:generate

# Миграции
php artisan migrate

# Загрузка данных (все сущности)
php artisan app:fetch-api-data

# Загрузка конкретной сущности
php artisan app:fetch-api-data sales
php artisan app:fetch-api-data orders
php artisan app:fetch-api-data stocks
php artisan app:fetch-api-data incomes
```

## Переменные окружения (.env)

```env
DB_HOST=mysql-1fdee782-d805.h.aivencloud.com
DB_PORT=24405
DB_DATABASE=defaultdb
DB_USERNAME=avnadmin
DB_PASSWORD=ваш_пароль
DB_SSLMODE=required

API_BASE_URL=http://example.com/api
API_KEY=ваш_ключ
```
