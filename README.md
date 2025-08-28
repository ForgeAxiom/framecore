# Framecore: A PHP Micro-Framework

**Languages:**
[English](#english)
[Русский](#русский)

# English

## What is it?
A lightweight framework built from scratch to understand the core principles behind modern PHP frameworks like Laravel. 🚀 

## About The Project
This project is a deep dive into architecture of modern web frameworks. By building a micro-framework from the ground up, it explores fundamental concepts such as:
- 🧭 Routing
- 💉 Dependency Injection (DI) Container 
- 🧱 Query Builder, Active Record (a common ORM pattern)
- ...and more, primarily inspired by Laravel

## Getting Started

### Prerequisites

- PHP 8.3 or higher
- Composer

### Installation

1. Clone the repo
```sh
git clone https://github.com/ForgeAxiom/framecore
```

2. Navigate to the project directory
```sh 
cd framecore
```

3. Install dependencies
```sh
composer install
```

4. Run the built-in PHP server
```sh
php -S localhost:8080 -t public
```

## Usage

### Defining Routes

```php
//app/Routes/routes.php
return [
    'get' => [
        ['/', ExampleController::class, 'index']
    ],
];
```

If the route is not found, a 404 not found Response is generated.

### Controller Creating 

All kinds of controllers should extend the base `app\Controllers\Controller.php` class.

A controller method can return **one** of types: 
1. An instance of `'ForgeAxiom\Framecore\Routing\View'`
2. An instance of `'ForgeAxiom\Framecore\Routing\Response'`
3. `null`

#### Method Returning

##### 1. View

```php
// To render simple view, method can return an instance of View.
// Framecore will automatically wrap your view to Response with code 200.
return new View('MyView');
```

##### 2. Response

```php
// For full control, you can return an instance of Response directly.
return new Response(201, new View('resource-created'));
```

##### 3. Null

```php
// An empty Response with code 200 will be generated.
return null;
```

### Working with Response

Represents final HTTP response to be sent to the client.

#### Creating
`new Response(200, new View('view'))`

1. `httpResponseCode` - Sets http response code.
2.  (Optional) `View` - View which would be displayed to user.


#### Response::notFound()

You can use static method `Response::notFound()` to make 404 not found response.


### Working with View

Simple wrapper for your php template pages.

#### Creating

```php
// app/Views/entrance/registration.php
new View('entrance.registration')
```

1. `path` - Path to view from `app/Views`, passed with *dot notation*

#### Template Example 

```php
//app/Views/MyView.php
return <<< HTML
<h1>My View</h1>
HTML;
```

#### Custom 404 not found

You can make custom 404 not found view. 

File of 404 not found view must be placed on path: 
`app/Views/404.php`.

That view would be uses for 404 not found cases. 

# Русский

## Что это?
Легковесный фреймворк, созданный с нуля, для изучения фундаментальных принципов, лежащих в основе современных PHP-фреймворков, таких как Laravel. 🚀

## О проекте
Этот проект — это глубокое погружение в архитектуру современных веб-фреймворков. Путем создания микро-фреймворка с нуля, с помощью этого проекта, я исследую такие фундаментальные концепции, как:
- 🧭 Маршрутизация (Routing)
- 💉 Внедрение Зависимостей (DI Container)
- 🧱 Построитель Запросов (Query Builder) и Active Record (распространенный паттерн ORM)
- ...и многое другое, в первую очередь вдохновляясь Laravel.

## Начало работы

### Требования

- PHP 8.3 или выше
- Composer

### Установка

1. Клонируйте репозиторий
```sh
git clone https://github.com/ForgeAxiom/framecore
```

2. Перейдите в директорию проекта
```sh 
cd framecore
```

3. Установите зависимости
```sh
composer install
```

4. Запустите встроенный веб-сервер PHP
```sh
php -S localhost:8080 -t public
```

## Использование

### Определение Маршрутов

Маршруты определяются в файле `app/Routes/routes.php`, который должен возвращать массив:
```php
//app/Routes/routes.php
return [
    'get' => [
        ['/', ExampleController::class, 'index']
    ],
];
```

Если маршрут не будет найден, сгенерируется `Response` 404 Not Found.

### Создание Контроллера

Все контроллеры приложения должны наследоваться от базового класса `app\Controllers\Controller.php`.

Метод контроллера может возвращать **одно** из трех значений:
1.  Экземпляр `'ForgeAxiom\Framecore\Routing\View'`
2.  Экземпляр `'ForgeAxiom\Framecore\Routing\Response'`
3.  `null`

#### Возвращаемые значения

##### 1. View

```php
// Для рендеринга простого представления, метод может вернуть экземпляр View.
// Фреймворк автоматически обернет Ваше представление в Response со статусом 200 OK.
return new View('MyView');
```

##### 2. Response

```php
// Для полного контроля Вы можете создать и вернуть экземпляр Response напрямую.
return new Response(201, new View('resource-created'));
```

##### 3. Null

```php
// Будет сгенерирован пустой Response со статусом 200 OK.
return null;
```

### Работа с Response

Представляет финальный HTTP-ответ для отправки клиенту.

#### Создание
`new Response(200, new View('view'))`

1.  `httpResponseCode` - Устанавливает HTTP-код ответа.
2.  (Опционально) `View` - Представление, которое будет отображено пользователю.

#### Response::notFound()

Вы можете использовать статический метод `Response::notFound()` для быстрого создания ответа 404 Not Found.


### Работа с View

Обертка для Ваших PHP-шаблонов.

#### Создание

```php
// Для файла app/Views/entrance/registration.php
new View('entrance.registration')
```

1.  `path` - Путь к файлу шаблона от корня `app/Views`, указывается через *dot notation*.

#### Пример Шаблона

```php
//app/Views/MyView.php
return <<<HTML
<h1>Моё Представление</h1>
HTML;
```

#### Пользовательская страница 404

Вы можете создать свою собственную страницу для ошибки 404 Not Found.

Файл шаблона должен быть расположен по пути:
`app/Views/404.php`.

Этот шаблон будет автоматически использоваться для всех случаев 404-й ошибки.