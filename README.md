

## 🛠️ Требования

-   **PHP**: `8.2.12` или выше (рекомендуется 8.3).
-   **Веб-сервер**: Apache (в составе XAMPP/Laragon) или Nginx.
-   **База данных**: MySQL / MariaDB.
-   **Клиент для тестов**: Bruno (рекомендуется) или Postman.

---

## 🚀 Установка и запуск (Windows + XAMPP)

### 1. Размещение проекта
Скопируйте папку проекта в корень веб-сервера:
`C:\xampp\htdocs\api-file`

### 2. Настройка PHP (Рекомендуется)
В файле `C:\xampp\php\php.ini` увеличьте лимиты загрузки:
```ini
upload_max_filesize = 3M
post_max_size = 4M
max_file_uploads = 20
```
Перезапустите Apache через XAMPP Control Panel.

### 3. Создание Базы Данных
Откройте `http://localhost/phpmyadmin`, перейдите во вкладку **SQL** и выполните:

```sql
CREATE DATABASE cloud_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cloud_api;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    token VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id VARCHAR(10) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE file_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id VARCHAR(10) NOT NULL,
    user_id INT NOT NULL,
    type ENUM('author', 'co-author') NOT NULL,
    UNIQUE KEY unique_access (file_id, user_id),
    FOREIGN KEY (file_id) REFERENCES files(file_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 4. Права доступа
Убедитесь, что папка `uploads` имеет права на запись:
```bash
mkdir C:\xampp\htdocs\api-file\uploads
```
*(В Windows обычно достаточно создать папку, права наследуются автоматически).*

---

## 🧪 Тестирование через Bruno

Проект поставляется с готовой коллекцией тестов.

### 1. Импорт коллекции
Откройте Bruno → `File` → `Open Collection` → выберите файл `Collection.json`

### 2. Настройка Environment
Нажмите иконку  (Environments) → `Create`. Назовите его `Local` и добавьте переменные:

| Variable | Value |
| :--- | :--- |
| `host` | `http://localhost/api-file` |
| `user_email` | `main@test.com` |
| `user_pass` | `Qa1` |
| `co_email` | `co@test.com` |
| `token` | *(оставьте пустым)* |
| `file_id` | *(оставьте пустым)* |

Или импортируйте файл `Env.json`.

> ⚠️ **Важно:** Используйте **Окружение Коллекции (Collection Environment)**, а не Глобальное. Это предотвратит конфликты переменных (`file_id`) между запусками.

### 3. Запуск
Нажмите **Run Collection** (Ctrl+Shift+R). Убедитесь, что все методы HTTP запросов (`POST`, `DELETE`) настроены верно (см. Troubleshooting).

---

##  Структура проекта

```text
api-file/
├── config.php              # Настройки БД и путей
├── index.php               # Точка входа и маршрутизация
├── src/
│   ├── Core/               # Ядро системы (Router, DB, Request, Response)
│   ├── Controllers/        # Обработчики запросов (Auth, Files)
│   ├── Middleware/         # Проверка авторизации
│   └── Services/           # Бизнес-логика (AuthService, FileService)
└── uploads/                # Хранилище файлов
```

---

##  Troubleshooting

| Ошибка | Причина | Решение |
| :--- | :--- | :--- |
| **422 Unprocessable Entity** | Ошибка валидации или неверный JSON | Проверьте `Content-Type: application/json` в Headers. Убедитесь, что пароль соответствует требованиям. |
| **404 Not Found (Access)** | Неверный метод запроса | Убедитесь, что Grant Access — это **POST**, а Revoke Access — **DELETE**. |
| **Class not found** | Ошибка автозагрузки | Проверьте, что имена файлов совпадают с именами классов (например, `FileService.php`, а не `FileServices.php`). |
| **Переменные не подставляются** | Ошибка в Environment | Убедитесь, что переменные созданы в окружении коллекции, а не в глобальном. |

---
