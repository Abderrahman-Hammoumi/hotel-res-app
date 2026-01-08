# 🏨 HotelRes

**HotelRes** est une application web de réservation d’hôtel construite avec **Symfony 7.4**, **Doctrine ORM**, **Twig** et **Asset Mapper**.

---

## 🚀 Technologies utilisées

-   PHP 8.2+
-   Symfony 7.4
-   Doctrine ORM
-   Twig
-   Asset Mapper
-   MySQL / MariaDB / PostgreSQL
-   Symfony CLI (optionnel)

---

## 📋 Prérequis

-   PHP 8.2 ou supérieur
-   Composer
-   Base de données compatible Doctrine (MySQL, MariaDB ou PostgreSQL)
-   Symfony CLI (optionnel)
-   XAMPP ou équivalent (optionnel)

---

## ⚡ Démarrage rapide (local)

```bash
composer install
cp .env.example .env
# update .env with your local values (DB, APP_SECRET)
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console app:create-admin admin@gmail.com admin123
php bin/console app:create-receptionist reception@gmail.com reception123
symfony server:start
```
