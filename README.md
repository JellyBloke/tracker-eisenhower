# Eisenhower Productivity Tracker

A web-based To-Do List & Schedule application built around the **Eisenhower Matrix**, with a
**Focus Mode** (Pomodoro-style timer) and a **Productivity Tracker** that awards points,
levels and streaks for completing tasks on time.

- **Backend:** Laravel 11 (PHP 8.2+)
- **Frontend:** TypeScript + plain CSS (bundled via Vite)
- **Database:** MySQL 8
- **Auth:** Session-based with Laravel's `auth` middleware

---

## Features

### Eisenhower Matrix
The dashboard splits open tasks into four quadrants:

| Quadrant   | Meaning                  |
|------------|--------------------------|
| Do         | Urgent & Important       |
| Schedule   | Important, Not Urgent    |
| Delegate   | Urgent, Not Important    |
| Eliminate  | Neither                  |

- Add tasks with urgency/importance toggles, due date, estimate.
- Drag tasks between quadrants — the server updates the urgent/important flags.
- Complete a task by ticking it; deletions are confirmed.

### Focus Mode
- A Pomodoro-style countdown with SVG progress ring.
- Configure duration (15/25/45/60 minute presets or any 1–180 minute value).
- Optionally bind a session to a task — focus minutes accumulate against that task.
- Pause / resume / end-early controls.
- All focus minutes earn productivity points (1 pt/min) on completion.

### Productivity Tracker
- Each completed task earns base points by quadrant:
  - Do: 25, Schedule: 20, Delegate: 10, Eliminate: 5
- **+15 bonus** for on-time completion, **+10** for finishing ≥24 h early.
- **-5 penalty** if overdue.
- **+5** when actual time ≤ estimate.
- **+2 / day** of current streak.
- Daily stats are aggregated in `productivity_stats` (tasks completed, on-time, overdue, focus minutes, points).
- The Stats page shows a 14-day chart (rendered with the Canvas API — no third-party charting lib),
  total points, current level (`floor(points / 100) + 1`), and current streak.

### Authentication
- Email / password login & registration.
- All app routes (`/dashboard`, `/focus`, `/stats`, `/api/tasks*`) are gated by the `auth` middleware.
- Guests are redirected to `/login`.
- CSRF tokens are required for state-changing requests.

---

## Project layout

```
app/
  Http/Controllers/        Auth, Dashboard, Task, Focus, Stats
  Models/                  User, Task, FocusSession, ProductivityStat
  Services/                ProductivityService (points + streak logic)
config/                    app/auth/database/session/cache/...
database/migrations/       users, tasks, focus_sessions, productivity_stats
resources/
  views/                   Blade templates (layout, auth, dashboard, focus, stats)
  ts/                      TypeScript (api, dashboard, focus, stats, types)
  css/app.css              Styles
routes/web.php             All routes (guest + auth-gated)
public/                    Web entry (index.php, .htaccess)
```

---

## Setup

### Requirements
- PHP **8.2+** with `pdo_mysql`, `mbstring`, `bcmath`, `openssl`, `tokenizer`, `xml`, `ctype`
- Composer
- Node.js **18+**
- MySQL **8** (or compatible MariaDB)

### Install

```bash
git clone <repo> tracker2
cd tracker2

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Edit `.env` and set the MySQL connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eisenhower_tracker
DB_USERNAME=root
DB_PASSWORD=secret
```

Create the database, then run migrations:

```bash
mysql -uroot -p -e "CREATE DATABASE eisenhower_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
# optional: seed a demo user (demo@example.com / password) with sample tasks
php artisan db:seed
```

### Run dev server

In two terminals:

```bash
php artisan serve            # http://localhost:8000
npm run dev                  # Vite dev server for hot-reload TS/CSS
```

Visit http://localhost:8000 — you'll be redirected to `/login`.

### Production build

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## API surface

All endpoints require an authenticated session and CSRF token.

| Method | URI                               | Purpose                                 |
|--------|-----------------------------------|-----------------------------------------|
| GET    | `/api/tasks`                      | List current user's tasks               |
| POST   | `/api/tasks`                      | Create task                             |
| PATCH  | `/api/tasks/{task}`               | Update fields                           |
| PATCH  | `/api/tasks/{task}/quadrant`      | Move between quadrants (drag-and-drop)  |
| POST   | `/api/tasks/{task}/start`         | Mark as in-progress                     |
| POST   | `/api/tasks/{task}/complete`      | Complete + award points                 |
| DELETE | `/api/tasks/{task}`               | Delete                                  |
| POST   | `/focus/start`                    | Start a focus session                   |
| POST   | `/focus/{session}/finish`         | End a session (completed/interrupted)   |
| GET    | `/api/stats/summary`              | Quick user summary (points/level/streak)|

---

## Tests

```bash
php artisan test
```

The bundled `AuthenticationTest` verifies that guests are redirected, the login screen
renders, and registered users can log in.

---

## License

MIT.
