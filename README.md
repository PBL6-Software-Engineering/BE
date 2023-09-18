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
  <img alt="Composer" src="https://img.shields.io/badge/-Composer-885630?style=flat-square&logo=composer&logoColor=white" />
  <img src="https://img.shields.io/github/license/ucan-lab/docker-laravel" alt="License">
</p>

## Introduction

Laravel, Nginx, and MySQL with Docker Compose

## Usage

### Run API Laravel with Docker 

```bash
$ git clone git@github.com:PBL6-Software-Engineering/BE.git
$ cd BE folder 
$ docker compose build
$ docker compose up -d
$ docker compose exec app php artisan key:generate
$ docker compose exec app php artisan storage:link
$ docker compose exec app chmod -R 777 storage bootstrap/cache
$ docker compose exec app php artisan migrate
```

http://localhost:99

### Connect Database 
```bash
  Host     : 127.0.0.1 
  Port     : 3306 
  Username : hivanmanh
  Password : hivanmanh 
```

### Run API Laravel with Docker use Makefile (MacOS and Linux)

1. Git clone & change directory
2. Execute the following command

```bash
$ git clone git@github.com:PBL6-Software-Engineering/BE.git
$ cd BE folder 
$ make install
```

http://localhost:99

## Container structures

```bash
├── app
├── web
└── db
```

### app container

- Base image
  - [php](https://hub.docker.com/_/php):8.2-fpm-bullseye
  - [composer](https://hub.docker.com/_/composer):2.6

### web container

- Base image
  - [nginx](https://hub.docker.com/_/nginx):1.25

### db container

- Base image
  - [mysql/mysql-server](https://hub.docker.com/r/mysql/mysql-server):8.0

### mailpit container

- Base image
  - [axllent/mailpit](https://hub.docker.com/r/axllent/mailpit)
