# NoSleep Mode

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Live Site](https://img.shields.io/badge/Live-nosleepnow.com-blue)](https://nosleepnow.com)

NoSleep Mode is a lightweight, self-hosted keep-awake web page designed for long sessions such as gaming, studying, monitoring, or presentations.

Live site: https://nosleepnow.com

---

## Features

- Screen Wake Lock support (where supported by the browser)
- Image and video background rotation
- Optional automatic background cycling
- UI auto-hide for distraction-free use
- No frameworks or build tools
- Privacy-friendly (no analytics or tracking)

---

## Folder Structure (Important)

The **code file and the `assets/` folder must be in the same directory**.

project-root/
  ├── index.php
  └── assets/
      ├── image1.jpg
      ├── image2.webp
      └── video1.mp4


The application automatically scans the `assets/` folder and loads supported files.

---

## Supported Media Formats

- Images: jpg, jpeg, png, webp
- Videos: mp4

---

## Setup

1. Upload `index.php` and the `assets/` folder to the same directory on your server.
2. Add images or videos to the `assets/` folder.
3. Open `index.php` in a modern browser.

No additional configuration is required.

---

## reCAPTCHA (Optional)

reCAPTCHA v3 is **optional** and used only for passive bot scoring.

To enable it:

1. Create reCAPTCHA v3 keys at  
   https://www.google.com/recaptcha/admin

2. Replace the placeholders in `index.php`:

```php
define('RECAPTCHA_SITE_KEY', 'YOUR_SITE_KEY');
define('RECAPTCHA_SECRET_KEY', 'YOUR_SECRET_KEY');

3. Update the frontend usage:     
grecaptcha.execute('YOUR_SITE_KEY', { action: 'page_view' });

grecaptcha.execute('YOUR_SITE_KEY', { action: 'page_view' });
If reCAPTCHA is not configured, the page will continue to work normally.

Browser Notes

 . Chrome / Edge / Brave: Full Wake Lock support
 . Firefox / Safari: Wake Lock behavior may be limited by browser or OS policies

