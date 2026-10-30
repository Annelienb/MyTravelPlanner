# My Travel Planner

I made Travelly to give myself one simple place to collect trip ideas and get ready
to travel. Visitors can browse trips I share, and use the planner, packing list, and
itinerary for their own plans.

## What I can do with it

- Add, edit, and remove shared trips from the admin area.
- Change the title and introduction on the homepage.
- Add a trip photo, dates, destination, budget, and notes to a shared trip.
- Make personal trips, add notes, and check a destination forecast.
- Keep a packing checklist and itinerary.
- Open Skyscanner or Booking.com to look for flights or places to stay.

My shared trips and homepage text are stored in MySQL. Personal trips, the packing
list, and the itinerary are saved in the browser's local storage. That means those
personal plans stay in the same browser and device; they are not saved to my
website account or shared with other visitors. Clearing browser data can remove
them.

## What the project uses

- PHP for the website and admin pages
- MySQL or MariaDB for shared trips, homepage content, and admin accounts
- JavaScript and browser local storage for personal planning tools
- Open-Meteo for destination lookup and forecast information

## Run it on my computer with XAMPP

1. Put the project folder in XAMPP's `htdocs` directory. For example:
   `C:\xampp\htdocs\MyTravelPlanner`
2. Start Apache and MySQL from the XAMPP Control Panel.
3. In phpMyAdmin, create a database. I can call it `u250848_travelly`, or choose
   another name and set `DB_NAME` to match.
4. Create the shared-content tables by running the SQL below in that database.
5. Configure the database environment variables for PHP (see the next section).
6. Open `http://localhost/MyTravelPlanner/public/` in my browser.
7. Open `http://localhost/MyTravelPlanner/admin/login.php` to create the first
   admin account.

The first admin account is created on the login page when the `users` table is
empty. I choose a username with at least 3 characters and a password with at least
12 characters. This admin login is separate from the MySQL username and password.

### Database tables for shared content

The login page creates the `users` table automatically. The `trips` and
`page_content` tables are for shared trips and homepage text; I create them once
with this SQL:

```sql
CREATE TABLE IF NOT EXISTS trips (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    country VARCHAR(80) NOT NULL,
    city VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    budget DECIMAL(10, 2) NOT NULL DEFAULT 0,
    description TEXT NOT NULL,
    cover_image VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS page_content (
    page_key VARCHAR(100) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Database settings

PHP reads these settings from the server environment. I get the correct values
from XAMPP or my hosting provider; they are not the same as my Travelly admin
login.

| Setting | What it is | Default |
| --- | --- | --- |
| `DB_HOST` | MySQL server hostname | `localhost` |
| `DB_PORT` | MySQL port | `3306` |
| `DB_SOCKET` | Optional Unix socket path; takes priority over host and port | Not set |
| `DB_NAME` | Database name | `u250848_travelly` |
| `DB_USER` | MySQL database username | Must be set |
| `DB_PASS` | MySQL database password | Must be set |

On shared hosting, I add these in the hosting control panel's PHP/environment
settings. If my provider gives me a socket path such as
`/var/lib/mysql/mysql.sock`, I set that as `DB_SOCKET`. I should not put a real
database password in a file that I commit or share. If a password has been exposed,
I change it in the hosting panel.

## Project folders

```text
admin/       Admin login and pages for managing trips and homepage text
config/      Database connection and shared PHP helpers
public/      Visitor-facing pages, styles, JavaScript, and the search endpoint
uploads/     Site logo and uploaded trip photos
```

## A few things to know

- A database connection problem usually means one of the `DB_*` settings is missing
  or incorrect, or the database is unavailable. The page shows a general message;
  the specific error is written to the server's PHP error log.
- On hosting, I look for **Error logs**, **PHP logs**, or **Website logs** in the
  control panel, or ask the provider where to find them. I don't enable public PHP
  error display on the website.
- Trip photos are saved under `uploads/`. The web server needs permission to write
  there.
- Flight and hotel buttons open Skyscanner and Booking.com. The optional endpoint
  in `public/api/travel-search.php` needs a `SERPAPI_KEY` environment variable if I
  use it.
- I can change the default database name in the server environment with `DB_NAME`.
