# 🍽️ Foodify

A web-based food marketplace and recipe platform built with PHP, MySQL, and Bootstrap 5.

---

## Requirements

Before running this project, make sure the following are installed:

- [XAMPP](https://www.apachefriends.org/) (PHP 8.0 or above, Apache, MySQL)
- A modern web browser (Chrome, Firefox, Edge)
- Internet connection (required for Cloudinary image upload and ToyyibPay payment)

---

## Installation & Run Steps

### 1. Clone or Download the Project

Download or clone this repository into your XAMPP `htdocs` folder:

```
C:/xampp/htdocs/Foodifyy/
```

The project folder structure should look like this:

```
Foodifyy/
├── assets/
├── config/
├── includes/
├── modules/
├── vendor/
├── index.php
└── ...
```

---

### 2. Install Dependencies

This project uses Composer for PHP dependencies (Cloudinary SDK, PHPMailer).

If the `vendor/` folder is not present, run the following command in the project root:

```bash
composer install
```

> If Composer is not installed, download it from https://getcomposer.org/

---

### 3. Database Setup

#### a. Start XAMPP
Open XAMPP Control Panel and start both **Apache** and **MySQL**.

#### b. Create the Database
1. Open your browser and go to: `http://localhost/phpmyadmin`
2. Click **New** on the left sidebar
3. Create a database named:

```
foodifyy
```

#### c. Import the Database
1. Select the `foodifyy` database
2. Click the **Import** tab
3. Click **Choose File** and select the provided SQL file:

```
foodifyy.sql
```

4. Click **Import** to complete the setup

---

### 4. Configure the Application

All configuration files are located in the `config/` folder. Each file has an `.example.php` version — copy and rename them by removing `.example` from the filename.

#### a. Database Configuration

Copy `config/database.example.php` → `config/database.php`

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'foodifyy');
```

> Default XAMPP MySQL credentials: username `root`, password empty.

---

#### b. Cloudinary Configuration

Copy `config/cloudinary.example.php` → `config/cloudinary.php`

```php
Configuration::instance([
    'cloud' => [
        'cloud_name' => 'your_cloud_name',
        'api_key'    => 'your_api_key',
        'api_secret' => 'your_api_secret',
    ],
    'url' => ['secure' => true]
]);
```

> Register a free account at https://cloudinary.com to get your credentials.

---

#### c. Mailer Configuration (Password Reset)

Copy `config/mailer.example.php` → `config/mailer.php`

```php
$mail->Username = 'your_gmail@gmail.com';
$mail->Password = 'your_16_digit_app_password';
$mail->setFrom('your_gmail@gmail.com', 'Foodify');
```

> Use a Gmail App Password (not your regular password).
> Generate one at: https://myaccount.google.com/apppasswords

---

#### d. ToyyibPay Configuration (Payment Gateway)

Copy `config/toyyibpay.example.php` → `config/toyyibpay.php`

```php
define('TOYYIBPAY_SECRET_KEY',    'your_secret_key_here');
define('TOYYIBPAY_CATEGORY_CODE', 'your_category_code_here');
define('TOYYIBPAY_BASE_URL',      'https://toyyibpay.com');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'];

define('TOYYIBPAY_RETURN_URL',   $baseUrl . '/modules/shop/payment_return.php');
define('TOYYIBPAY_CALLBACK_URL', $baseUrl . '/modules/shop/payment_callback.php');
```

> Register at https://toyyibpay.com to obtain your Secret Key and Category Code.

---

### 5. Run the Application

1. Make sure Apache and MySQL are running in XAMPP Control Panel
2. Open your browser and go to:

```
http://localhost/Foodifyy/
```

---

## Default Credentials

The following accounts are pre-loaded in the database for testing purposes.

| Role     | Email                      | Password |
|----------|----------------------------|----------|
| Admin    | adminfoodify@gmail.com     | 12345678 |
| Staff    | staffoodify@gmail.com      | 12345678 |
| Customer | Register a new account via the Register page |

> It is recommended to change the default passwords after the first login.

---

## Notes

- Cloudinary is required for image uploads (profile pictures, item images, recipe images). The application will still run without it, but image upload functions will not work.
- ToyyibPay is required only for online banking payments. Cash on Delivery (COD) orders will work without ToyyibPay configuration.
- PHPMailer is required only for the password reset feature. Login and registration will work without it.