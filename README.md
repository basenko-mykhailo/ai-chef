# AI Chef 🍳

Hackathon-застосунок на Laravel, який генерує рецепти з продуктів у коморі користувача
з урахуванням дієтичних обмежень членів родини через Anthropic Claude API, з розпізнаванням
комори за фото.

Інтерфейс — українською. Уся інфраструктура запускається через **Docker Compose** —
ні PHP, ні Composer, ні Node на хості не потрібні (потрібен лише Docker).

---

## Зміст

- [Архітектура контейнерів](#архітектура-контейнерів)
- [Крок 0. Встановлення Docker](#крок-0-встановлення-docker)
  - [Ubuntu](#ubuntu)
  - [Windows](#windows)
- [Крок 1. Інсталяція проєкту](#крок-1-інсталяція-проєкту)
- [Крок 2. Збірка фронтенду (Vite)](#крок-2-збірка-фронтенду-vite)
- [Перевірка та доступ](#перевірка-та-доступ)
- [Корисні команди](#корисні-команди)
- [Налаштування Anthropic API](#налаштування-anthropic-api)
- [Типові проблеми](#типові-проблеми-troubleshooting)

---

## Архітектура контейнерів

`docker-compose.yml` піднімає три сервіси в одній мережі `ai_chive`:

| Сервіс | Образ | Призначення | Порт (host → container) |
|--------|-------|-------------|--------------------------|
| `web`  | `nginx:alpine` | Веб-сервер, віддає `src/public` | **8080 → 80** |
| `php`  | `ai_chive/php:8.4-fpm` (збирається з `docker/php/Dockerfile`) | PHP-FPM 8.4 + Composer | 9000 (внутрішній) |
| `db`   | `postgres:latest` | База даних PostgreSQL | **5432 → 5432** |

Важливі деталі:

- Код `src/` змонтовано в `php` **на читання-запис**, а в `web` — **тільки на читання**.
- Сервіс `php` **інжектить параметри БД як змінні оточення** (`DB_CONNECTION=pgsql`,
  `DB_HOST=db`, `DB_DATABASE=ai_chive`, `DB_USERNAME=ai_chive`, `DB_PASSWORD=secret`).
  Вони **мають пріоритет над `.env`** (де за замовчуванням стоїть `sqlite`), тому
  міграції одразу йдуть у Postgres — редагувати `.env` для БД **не потрібно**.
- Дані Postgres зберігаються у volume `pgdata` і переживають перезапуск контейнерів.
- `php`-контейнер містить **лише PHP + Composer** (без Node/npm). Фронтенд (Vite/Tailwind)
  збирається окремим одноразовим Node-контейнером — див. [Крок 2](#крок-2-збірка-фронтенду-vite).

---

## Крок 0. Встановлення Docker

### Ubuntu

> Перевірено для Ubuntu 22.04 / 24.04. Команди виконуються в терміналі (`bash`).

1. **Видаліть старі/конфліктні пакети** (якщо є):

   ```bash
   sudo apt-get remove -y docker docker-engine docker.io containerd runc 2>/dev/null || true
   ```

2. **Додайте офіційний репозиторій Docker:**

   ```bash
   sudo apt-get update
   sudo apt-get install -y ca-certificates curl gnupg git
   sudo install -m 0755 -d /etc/apt/keyrings
   curl -fsSL https://download.docker.com/linux/ubuntu/gpg | \
     sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
   sudo chmod a+r /etc/apt/keyrings/docker.gpg

   echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
     https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
     sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
   ```

3. **Встановіть Docker Engine + плагін Compose:**

   ```bash
   sudo apt-get update
   sudo apt-get install -y docker-ce docker-ce-cli containerd.io \
     docker-buildx-plugin docker-compose-plugin
   ```

4. **Дозвольте запуск без `sudo`** (щоб працював `UID=$(id -u)` нижче):

   ```bash
   sudo usermod -aG docker $USER
   newgrp docker   # або просто перелогіньтеся
   ```

5. **Перевірте:**

   ```bash
   docker --version
   docker compose version
   docker run --rm hello-world
   ```

### Windows

> Рекомендований шлях — **Docker Desktop із бекендом WSL 2**. Команди нижче — для **PowerShell**.

1. **Увімкніть WSL 2** (один раз, у PowerShell від адміністратора):

   ```powershell
   wsl --install
   ```

   Перезавантажте комп'ютер, якщо WSL встановлювався вперше.

2. **Встановіть Docker Desktop:**
   - Завантажте інсталятор зі сторінки <https://www.docker.com/products/docker-desktop/>.
   - Під час інсталяції залиште увімкненою опцію **"Use WSL 2 instead of Hyper-V"**.
   - Після встановлення запустіть Docker Desktop і дочекайтесь статусу **Running** (зелений значок).

3. **Встановіть Git** (якщо ще немає): <https://git-scm.com/download/win>.

4. **Перевірте у PowerShell:**

   ```powershell
   docker --version
   docker compose version
   docker run --rm hello-world
   ```

> 💡 **Порада щодо продуктивності:** на Windows bind-mount працює суттєво швидше, якщо
> тримати репозиторій **усередині файлової системи WSL 2** (наприклад `\\wsl$\Ubuntu\home\<user>\...`),
> а не на диску `C:`. За бажанням клонуйте проєкт у WSL і виконуйте всі команди в терміналі Ubuntu —
> тоді працюватимуть і Ubuntu-інструкції з блоку вище.

---

## Крок 1. Інсталяція проєкту

Усі команди виконуються **з кореня репозиторію** (там, де лежить `docker-compose.yml`).

### 1.1. Клонування

```bash
git clone <URL-репозиторію> ai_chive
cd ai_chive
```

### 1.2. Файл оточення `.env`

Laravel-конфіг лежить у `src/`. Скопіюйте приклад:

**Ubuntu / WSL:**
```bash
cp src/.env.example src/.env
```

**Windows (PowerShell):**
```powershell
Copy-Item src\.env.example src\.env
```

> БД для Docker налаштовувати в `.env` **не потрібно** — параметри Postgres інжектяться
> сервісом `php` із `docker-compose.yml`. Але якщо плануєте користуватись AI-функціями,
> уже зараз пропишіть ключ `ANTHROPIC_API_KEY` — див.
> [Налаштування Anthropic API](#налаштування-anthropic-api).

### 1.3. Збірка образу `php`

Аргументи `UID`/`GID` потрібні, щоб файли, які контейнер пише у змонтований `src/`
(логи, кеш, `storage/`), належали вашому користувачу.

**Ubuntu / WSL:**
```bash
UID=$(id -u) GID=$(id -g) docker compose build
```

**Windows (PowerShell):**
```powershell
docker compose build
```
> На Windows/Docker Desktop UID/GID не застосовні — образ збереться зі значеннями за
> замовчуванням (`1000`), і права на bind-mount керуються самим Docker Desktop.

### 1.4. Запуск контейнерів

```bash
docker compose up -d
```

Сервіс `php` стартує **лише після** того, як `db` пройде healthcheck (готовність Postgres),
тому наступні команди одразу зможуть під'єднатися до бази.

### 1.5. Залежності PHP, ключ застосунку, міграції

Виконуються **всередині контейнера `php`** (працює і на Ubuntu, і на Windows однаково):

```bash
# 1. Composer-залежності (vendor/ не зберігається в git)
docker compose exec php composer install

# 2. Згенерувати APP_KEY (запишеться у src/.env)
docker compose exec php php artisan key:generate

# 3. Міграції + сидери (зокрема довідник продуктів / FamilyMemberSeeder)
docker compose exec php php artisan migrate --seed

# 4. Символьне посилання для публічних файлів (фото комори тощо)
docker compose exec php php artisan storage:link
```

> Щоб повністю перезібрати БД з нуля: `docker compose exec php php artisan migrate:fresh --seed`.

---

## Крок 2. Збірка фронтенду (Vite)

У `php`-контейнері **немає Node/npm**, тому фронтенд (Tailwind v4 + Vite) збирається
**одноразовим Node-контейнером**. Blade-шаблони підключають ассети через `@vite(...)`,
тож без зібраного `public/build/manifest.json` сторінки впадуть з помилкою Vite.

### Разова збірка (production-режим)

**Ubuntu / WSL:**
```bash
docker run --rm -u $(id -u):$(id -g) \
  -v "$(pwd)/src":/app -w /app \
  node:22-alpine sh -c "npm install --ignore-scripts && npm run build"
```

**Windows (PowerShell):**
```powershell
docker run --rm -v ${PWD}\src:/app -w /app `
  node:22-alpine sh -c "npm install --ignore-scripts && npm run build"
```

### Режим розробки з hot-reload (опційно)

Якщо активно правите стилі/JS, можна підняти Vite dev-server (порт `5173`):

**Ubuntu / WSL:**
```bash
docker run --rm -it -u $(id -u):$(id -g) \
  -v "$(pwd)/src":/app -w /app -p 5173:5173 \
  node:22-alpine sh -c "npm install --ignore-scripts && npm run dev -- --host"
```

**Windows (PowerShell):**
```powershell
docker run --rm -it -v ${PWD}\src:/app -w /app -p 5173:5173 `
  node:22-alpine sh -c "npm install --ignore-scripts && npm run dev -- --host"
```

---

## Перевірка та доступ

Відкрийте у браузері:

```
http://localhost:8080
```

Якщо бачите стартову сторінку застосунку — все працює. 🎉

PostgreSQL доступний із хоста на `localhost:5432` (база/користувач `ai_chive`, пароль `secret`) —
зручно для підключення з DBeaver / TablePlus / `psql`.

---

## Корисні команди

Усе виконується через `docker compose exec php …`:

| Команда | Призначення |
|---------|-------------|
| `docker compose exec php php artisan migrate` | Застосувати нові міграції |
| `docker compose exec php php artisan migrate:fresh --seed` | Повністю перебудувати БД + сидери |
| `docker compose exec php php artisan tinker` | REPL-консоль Laravel |
| `docker compose exec php php artisan queue:listen --tries=1 --timeout=0` | Воркер черги (потрібен для AI-генерації рецептів) |
| `docker compose exec php composer test` | Прогнати тести (PHPUnit, sqlite `:memory:`) |
| `docker compose exec php vendor/bin/pint` | Лінтер/форматер коду (Laravel Pint) |
| `docker compose logs -f php` | Логи PHP-FPM у реальному часі |
| `docker compose logs -f web` | Логи nginx |
| `docker compose down` | Зупинити контейнери (дані БД зберігаються у volume) |
| `docker compose down -v` | Зупинити **й видалити** volume `pgdata` (повне очищення БД) |

> Генерація рецептів виконується у фоновій черзі (`QUEUE_CONNECTION=database`). Щоб вона
> працювала, тримайте запущеним окремий термінал із `queue:listen` (команда вище).

---

## Налаштування Anthropic API

AI-функції (генерація рецептів, розпізнавання комори за фото) потребують ключа Claude API.
Відкрийте `src/.env` і впишіть свій ключ:

```dotenv
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_DEFAULT_MODEL=claude-haiku-4-5
ANTHROPIC_QUALITY_MODEL=claude-sonnet-4-6
```

Після зміни `.env` скиньте кеш конфігу:

```bash
docker compose exec php php artisan config:clear
```

---

## Типові проблеми (troubleshooting)

**Порт 8080 або 5432 уже зайнятий.**
Змініть ліву частину мапінгу портів у `docker-compose.yml` (наприклад `"8081:80"` чи
`"5433:5432"`) і виконайте `docker compose up -d` знову.

**`Permission denied` / помилки запису в `storage` або `bootstrap/cache` (Ubuntu).**
Образ зібрано з чужими UID/GID. Перезберіть із правильними значеннями:
```bash
UID=$(id -u) GID=$(id -g) docker compose build php
docker compose up -d
```

**`No application encryption key has been specified`.**
Не виконано крок 1.5.2 — запустіть `docker compose exec php php artisan key:generate`.

**`Unable to locate file in Vite manifest` / зламана верстка.**
Не зібрано фронтенд — виконайте [Крок 2](#крок-2-збірка-фронтенду-vite).

**`could not translate host name "db"` / помилки підключення до БД.**
Контейнер `db` ще не готовий або не запущений. Перевірте `docker compose ps` і логи
`docker compose logs db`; за потреби `docker compose up -d` знову.

**`composer: command not found` під час `docker run … node`.**
Це нормально — Composer живе у контейнері `php`, а Node-команди — у контейнері `node`.
Не змішуйте їх: `composer`/`artisan` → через `docker compose exec php`, `npm` → через `docker run … node`.

**Повне «чисте» перевстановлення.**
```bash
docker compose down -v          # прибрати контейнери + volume БД
docker compose build            # (Ubuntu: з UID/GID, як у кроці 1.3)
docker compose up -d
# далі повторіть кроки 1.5 та 2
```