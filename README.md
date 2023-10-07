# docker-laravel 🐳

<p align="center">
    <img src="https://user-images.githubusercontent.com/35098175/145682384-0f531ede-96e0-44c3-a35e-32494bd9af42.png" alt="docker-laravel">
</p>
<p align="center">
  <img alt="MySQL" src="https://img.shields.io/badge/-MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white" />
  <img alt="PHP" src="https://img.shields.io/badge/-PHP-777BB4?style=flat-square&logo=php&logoColor=white" />
  <img alt="Laravel" src="https://img.shields.io/badge/-Laravel-FF2D20?style=flat-square&logo=laravel&logoColor=white" />
  <img alt="Docker" src="https://img.shields.io/badge/-Docker-46a2f1?style=flat-square&logo=docker&logoColor=white" />
  <img alt="Nginx" src="https://img.shields.io/badge/-Nginx-009639?style=flat-square&logo=nginx&logoColor=white" />
  <img alt="Node.js" src="https://img.shields.io/badge/-Node.js-339933?style=flat-square&logo=Node.js&logoColor=white" />
  <img alt="NPM" src="https://img.shields.io/badge/-NPM-CB3837?style=flat-square&logo=npm&logoColor=white" />
  <img alt="Composer" src="https://img.shields.io/badge/-Composer-885630?style=flat-square&logo=composer&logoColor=white" />
  <img src="https://img.shields.io/github/license/ucan-lab/docker-laravel" alt="License">
</p>

## Introduction

Laravel:9.0 - PHP:8.0 - Nginx:stable-alpine - NPM:Nodejs-18.14.2 - MySQL:8.0 with Docker Compose

## Usage

### Run API Laravel with Docker 

```bash
$ git clone git@github.com:PBL6-Software-Engineering/BE.git
$ cd BE  
$ docker-compose up -d --build app
$ docker-compose exec app cp /var/www/html/.env.example /var/www/html/.env
$ docker compose run --rm composer install
$ docker compose run --rm npm install
$ docker-compose exec app chmod -R 777 /var/www/html
$ docker compose run --rm artisan key:generate
$ docker-compose run --rm artisan storage:link
$ docker-compose run --rm artisan optimize:clear
$ docker-compose run --rm artisan optimize
$ docker-compose run --rm artisan migrate:fresh  --seed
$ docker-compose run --rm artisan db:seed --class=AdminsSeeder
$ docker-compose run --rm artisan db:seed --class=CategoriesSeeder
$ docker-compose run --rm artisan db:seed --class=DepartmentsSeeder
$ docker-compose run --rm artisan db:seed --class=ProvincesSeeder
$ docker-compose run --rm artisan queue:work 
```

http://localhost:99

### Run the after

```bash
$ git pull origin main 
$ docker-compose up -d 
$ docker-compose run --rm artisan queue:work 
```


### Connect Database 
```bash
  Host     : 127.0.0.1 
  Port     : 3309
  Database : pbl6 
  Username : hivanmanh
  Password : hivanmanh 
```

### Account Admin role Manager  
```bash
  Email    : vanmanh.dut@gmail.com
  Password : vanmanh.dut
```

### Run API Laravel with Docker use Makefile (MacOS and Linux)

1. Git clone & change directory
2. Execute the following command

```bash
$ git clone git@github.com:PBL6-Software-Engineering/BE.git
$ cd BE  
$ make install
```

http://localhost:99

### Other Commands 
```bash
$ docker-compose run --rm composer analyze 
$ docker-compose run --rm composer fix-format . 
$ docker-compose run --rm composer analyze PATH_FOLDER_OR_FILE 
$ docker-compose run --rm composer fix-format PATH_FOLDER_OR_FILE
```

## Container structures

```bash
├── app
├── web
└── db
```

### app container

- Base image
  - [php](https://hub.docker.com/_/php):8-fpm-alpine
  - [composer](https://hub.docker.com/_/composer):2.6.3
  - [node.js](https://hub.docker.com/_/node/):18.14.2

### web container

- Base image
  - [nginx](https://hub.docker.com/_/nginx):stable-alpine

### db container

- Base image
  - [mysql/mysql-server](https://hub.docker.com/r/mysql/mysql-server):8.0

### mailpit container

- Base image
  - [axllent/mailpit](https://hub.docker.com/r/axllent/mailpit)
