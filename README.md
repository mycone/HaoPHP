# HaoPHP v1.0

HaoPHP 是一个简单的PHP MVC框架，通过简单的路由配置即可实现遵循 RESTful接口的WEB应用，
你可以通过本框架进行无限扩展满足你实际项目需要。

## 站长链接
- [官方网址](http://www.sifangke.com)

### 服务器配置

#### Apache

You may need to add the following snippet in your Apache HTTP server virtual host configuration or **.htaccess** file.

```apacheconf
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond $1 !^(index\.php)
RewriteRule ^(.*)$ /index.php/$1 [L]
```

#### Nginx

Under the `server` block of your virtual host configuration, you only need to add three lines.
```conf
location / {
  try_files $uri $uri/ /index.php?$args;
}
```