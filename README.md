<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel"></a>
<a href="https://vuejs.org"><img src="https://img.shields.io/badge/Vue-3-4FC08D?logo=vuedotjs&logoColor=white" alt="Vue"></a>
<a href="https://inertiajs.com"><img src="https://img.shields.io/badge/Inertia.js-9553E9?logo=inertia&logoColor=white" alt="Inertia.js"></a>
<a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind_CSS-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind CSS"></a>
<a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white" alt="PHP"></a>
</p>

## About Rai CRM

Rai CRM is a customer relationship management system built with Laravel, Vue and Inertia.js. It keeps contacts, companies, deals, tasks and support tickets in one place, with a dashboard that shows what needs attention.

- **Contacts and companies**: manage people and the organizations they belong to.
- **Deals**: track sales through pipeline stages, with value, expected close date and automatic `closed_at` handling when a deal reaches a closed stage.
- **Tasks**: assign follow-ups to deals and team members.
- **Support tickets**: priorities, types, statuses, response and resolution tracking.
- **Comments and attachments**: add discussion and files to records, stored on a configurable disk.
- **Dashboard**: open deals, your tasks, recent deals and recent activity.
- **Reports**: help desk metrics such as volumes, average first response and resolution time, SLA compliance and CSAT.
- **Soft deletes with cascade**: deleting or restoring a record follows through to its child records (tasks, attachments).
- **Hashed URLs**: records appear in URLs as Hashids strings (for example `/deals/Xk2vQ9pL0a`) instead of numeric IDs.

## Tech Stack

| Layer     | Technology                  |
|-----------|-----------------------------|
| Backend   | Laravel, PHP 8.3+           |
| Frontend  | Vue 3, Inertia.js           |
| Styling   | Tailwind CSS                |
| Build     | Vite                        |
| Database  | MySQL / MariaDB (or SQLite) |

## Requirements

- PHP 8.3 or higher
- Composer
- Node.js 20+ and npm
- A database (MySQL, MariaDB or SQLite)

## Installation

Clone the repository:

```bash
git clone https://github.com/RaiJanin/rai_crm.git
cd rai_crm
```

Install dependencies:

```bash
composer install
npm install
```

Set up the environment:

```bash
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`, then run the migrations (add `--seed` if the project includes seeders):

```bash
php artisan migrate --seed
```

Link the storage directory so uploaded attachments can be served:

```bash
php artisan storage:link
```

## Configuration

Record URLs use [Hashids](https://hashids.org). Set these values in `.env` (or `config/hashids.php`) before going live, and do not change the salt afterwards, since every existing URL would change:

```dotenv
HASHIDS_SALT=your-long-random-secret
HASHIDS_MIN_LENGTH=10
```

## Running the Project

Start the Laravel server and the Vite dev server in two terminals:

```bash
php artisan serve
npm run dev
```

Or, if the project defines the `dev` Composer script:

```bash
composer run dev
```

Then open `http://localhost:8000`.

## Building for Production

```bash
npm run build
php artisan optimize
```

## Project Structure

```
app/
├── Contracts/          Interfaces (for example Attachable)
├── Enums/              DealStage, TicketStatus, TicketPriority, TicketType
├── Http/Controllers/   Single-action and resource controllers
├── Models/
│   └── Concerns/       HasHashId, CascadesSoftDeletes
└── Services/
    ├── Crm/            DashboardService, AttachmentService
    └── Reports/        SupportMetrics
resources/js/
├── Pages/              Inertia pages (Dashboard, Deals, Contacts, ...)
└── Components/         Shared Vue components
```

## Testing

```bash
php artisan test
```

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m "Add my feature"`
4. Push the branch: `git push origin feature/my-feature`
5. Open a pull request

## License

Add your license here. The Laravel framework itself is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
