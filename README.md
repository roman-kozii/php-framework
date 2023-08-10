# Nebula
[![PHP Composer](https://github.com/libra-php/nebula/actions/workflows/php.yml/badge.svg?branch=main)](https://github.com/libra-php/nebula/actions/workflows/php.yml)

⭐ Nebula is a powerful PHP micro-framework

✅ Provides developers with a flexible and extensible architecture to build custom web applications with ease.

👷 *Currently under development*

❌ **Not for production use**


### Getting Started

To get started with Nebula, follow these steps:


- **Installation**: Clone the repository and install dependencies using Composer.
```bash
git clone https://github.com/libra-php/nebula.git
cd nebula
composer install
```

- **Configuration**: Customize the configuration files according to your project requirements, including database settings and configurations. If you're starting a new project, then copy the example env configuration.

```bash
cp .env.example .env
```

- **Dependencies**: The application has the following dependencies:

    - Redis: caching, rate limiting
    - MySQL: database

- **Note**: you may enable/disable application behaviour in `/app/Config`

- **Development**: Start building your application by creating controllers, views, and models within the `src` directory. 
Hint: You can start a local development server by running `./nebula -s`

### Admin Backend

WIP: Nebula comes fully equipped with a sophisticated admin backend, easily accessible through the `/admin/sign-in` route. The power to enable or disable user registration rests in your hands, effortlessly adjustable within the `.env` configuration using the `ADMIN_REGISTER_ENABLED=true` toggle. Once authentication is successfully established, users are seamlessly guided to the `/admin/module/dashboard` destination. Nevertheless, this behaviour can be modified to match your application's unique specifications.

### Documentation
- [Config](docs/CONFIG.md)
- [Console](docs/CONSOLE.md)
- [Helpers](docs/HELPERS.md)
- [Database](docs/DATABASE.md)
    - [Migrations](docs/MIGRATIONS.md)
- [Routing](docs/ROUTING.md)
- [Middleware](docs/MIDDLEWARE.md)
- [Controllers](docs/CONTROLLERS.md)
- [Views](docs/VIEWS.md)
- [Models](docs/MODELS.md)
    - [Factory](docs/FACTORY.md)

**Additional information coming soon!**

**Note**: Please be aware that this documentation has been generated by AI. If you come across any inaccuracies, please don't hesitate to get in touch with us.


### Benchmarks

Our team is dedicated to optimizing Nebula to deliver exceptional performance, striving to position it among the top-performing PHP frameworks available 🚀 

**Coming soon**

- *Command: `siege -b -c 10 -t 1s $APP_URL`*


### Contributing

Contributions to Nebula are welcome! If you find any issues or have suggestions for improvements, please open an issue or submit a pull request. 


### License

This project is licensed under the <a href='https://github.com/libra-php/nebula/blob/main/LICENSE'>MIT License</a>.


### Acknowledgements

We would like to express our gratitude to the following open-source projects that have inspired Nebula:

- Symfony
- Slim Framework
- Leaf
- Laravel


### Contact

For any inquiries or questions, please contact william.hleucka@gmail.com.


🇨🇦 Made in Canada
