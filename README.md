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

Routes defineds in config/routes.php
```php
// config/routes.php
return [
    'get' => [
        ['/', ExampleController::class, 'index']
    ],
];
```

If the route is not found, a 404 not found Response is generated.

### Controller Creating 

All kinds of controllers should extend the base `app\Controllers\Controller.php` class.

#### index.php

Before use Controller, it should be in [binding](#binding) in index.php 

#### Method Returning

A controller method can return **one** of types: 
1. An instance of `'ForgeAxiom\Framecore\Routing\View'`
2. An instance of `'ForgeAxiom\Framecore\Routing\Response'`
3. `null`

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

### Working with DI-container

Manages and resolves service classes through **bindings**. It allows you to write **clean** and adhere to Single Responsibility Principle (**SRP**) classes by delegating creation and lifecycle management of services to the container.

#### Binding

**Binding** is a full class name (**ClassName::class**) with a **Closure**, with code of creation for **later creation** in `get` method.

Two ways to make binding:

##### 1. `bind` (Every time anew instances)

Use `bind` method when you need to bind Class which would be creating **anew** instance **every time**.

**Example:**
```php
// Instance of container always would be passed for resolving dependencies
$container->bind(SiteController::class, function(Container $c) {
    return new Router(
        // First argument RoutesCollection
        $c->get(RoutesCollection::class),
        // Second argument DI-Container
        $c
    );
});
```

##### 2. `singleton` (Shared instances)

Use `singleton` method when you need to bind Class which would be **creating for once** and would be **stored** for later use, like Database connection.

**Example:**

```php
$container->singleton(Connection::class, function() {
    return new Connection();
});
```

#### Getting classes

By default, not bound classes would be resolves **automatically**. 

```php
$container->get(ClassName::class, true);
```
**Params:**
1. Getting class name
2. Auto resolving mode `(By default: true)`

##### Auto singletons

In `config/auto_singletons.php` you can put in returning array class names which would be created like [singleton](#2-singleton-shared-instances).

**Example:**
```php
// config/auto_singletons.php
return [
    Connection::class,
]
```

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

Маршруты определяются в файле `config/routes.php`, который должен возвращать массив:
```php
// config/routes.php
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

#### index.php

До использования контроллера, его нужно [привязать](#привязка-binding)
 in index.php 

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

### Работа с DI-container

Управляет и разрешает зависимостями классов сервисов через привязки(**bindings**). Он позволяет писать **чисто** и придерживаться Принципу Единой Ответственности(**SRP**) во время проектирования своих сервисов с помощью делегирования создания и управления жизненным циклом сервисов контейнеру.

#### Привязка (Binding)

Привязка (**Binding**) это полное классовое имя (**ClassName::class**) с замыкающей(**Closure**), в которой описана инструкция по созданию класса, для **дальнейшего** поулчение через `get`.

Есть два способа создания привязки(**binding**):

##### 1. `bind` (Каждый раз новый экземпляр)

Используйте метод `bind` когда необходимо привязать сервис, который будет создаваться все время по новой.

**Пример использования:**
```php
// Всегда подается подается контейнер в замыкающую ф-ию, для разрешния зависимостей
$container->bind(SiteController::class, function(Container $c) {
    return new Router(
        // First argument RoutesCollection
        $c->get(RoutesCollection::class),
        // Second argument DI-Container
        $c
    );
});
```

##### 2. `singleton` (Разделенные экземпляры)

Используйте метод `singleton` когда необходимо привязать сервис, который **создастся один раз** и будет **закэширован** для дальшейшего использования, например, подключение к базе данных.

**Пример использования:**

```php
$container->singleton(Connection::class, function() {
    return new Connection();
});
```

#### Пример получения класса с помощью контейнера

По умолчанию не привязанные классы разрешаются **автоматически**.

```php
$container->get(ClassName::class, true);
```
**Params:**
1. Getting class name
2. Auto resolving mode `(By default: true)`

##### Авто singletons

В `config/auto_singletons.php` Вы можете написать названия своих классов, которые необходимо создавать как [singleton](#2-singleton-разделенные-экземпляры).

**Пример:**
```php
// config/auto_singletons.php
return [
    Connection::class,
]
```