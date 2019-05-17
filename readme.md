# Instruction for using

## Installation

1. Clone this project into specific directory on your server, and go to this directory.
2. Run ``composer install`` command from your terminal.
3. Configure your server for using api, according to the [laravel docs](https://laravel.com/docs/5.5/installation#web-server-configuration "laravel docs").
4. Copy `.env.example` file to `.env` file and `.env.testing` files. Set `APP_URL` param in these files.
5. Create databases:
    - For common usage, and set `DB_DATABASE` param on `.env` file
    - For testing, and set `DB_DATABASE` param on `.env.testing` file
    - Set other params in both files: `DB_USERNAME`, `DB_PASSWORD`
6. run `php artisan key:generate`
6. run `php artisan migrate:fresh --seed` command to create tables and set default user.


### Task with normalized string

For using first task, just run command from your terminal:
`php check:string some-string`. 

### Api

Before using api requests, take out the api key(api_token) from table `users` of your database.
You can also take it from `DatabaseSeeder` class(api_token). You should add this key to any request you would like to perform(`api_token=needed_token`).

#### Api requests

| url  | method | description   |
| ------------ | ------------ | ------------ |
| /api/image/store-files | POST | storing array of files  |
|  /api/image/store-from-remote-source | POST | storing from remote source(only one file)   |
| /api/image/store-from-base64  | POST | from base 64 |
| /api/image/create-resize  | POST | creates resize on filesystem and updates table |
| /api/image/resizes  | GET | getting list of created resizes for specific image |
| /api/image/resize  | DELETE | deleting specific resize |
| /api/image/all-resizes  | DELETE | deleting all resizes from specific image |

### Testing

Just run all tests from `tests` directory.