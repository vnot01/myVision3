<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

## Daftar Isi

1.  [Pendahuluan](#pendahuluan)
2.  [Base URL](#base-url)
3.  [Autentikasi](#autentikasi)
    *   [Autentikasi User (Sanctum)](#autentikasi-user-sanctum)
    *   [Autentikasi RVM (API Key)](#autentikasi-rvm-api-key)
4.  [Endpoint Autentikasi](#endpoint-autentikasi)
    *   [POST /api/login](#post-apilogin)
    *   [POST /api/auth/google](#post-apiauthgoogle)
    *   [POST /api/logout (Sanctum)](#post-apilogout-sanctum)
5.  [Endpoint User (Memerlukan Autentikasi Sanctum)](#endpoint-user-memerlukan-autentikasi-sanctum)
    *   [GET /api/user](#get-apiuser)
    *   [GET /api/user/profile](#get-apiuserprofile)
    *   [POST /api/user/rvm-token](#post-apiuserrvm-token)
    *   [GET /api/user/points](#get-apiuserpoints)
    *   [GET /api/user/deposits](#get-apiuserdeposits)
6.  [Endpoint RVM (Memerlukan Autentikasi RVM API Key)](#endpoint-rvm-memerlukan-autentikasi-rvm-api-key)
    *   [POST /api/deposits](#post-apideposits)
7.  [Endpoint Dashboard (Memerlukan Autentikasi Sanctum & Role)](#endpoint-dashboard-memerlukan-autentikasi-sanctum--role)
    *   [GET /api/dashboard/stats](#get-apidashboardstats)
    *   [GET /api/dashboard/rvms](#get-apidashboardrvms)
    *   [GET /api/dashboard/deposits](#get-apidashboarddeposits)

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
