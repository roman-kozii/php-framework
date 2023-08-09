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

- **Configuration**: Customize the configuration files according to your project requirements, including database settings and routes.
```
# Copy the example settings
cp .env.example .env
# Change ownership of the view cache
chown -R www-data:www-data views/.cache
```

- **Development**: Start building your application by creating controllers, views, and models within the `src` directory. You can start a local development server by running `./nebula -s`


### Documentation
- [Routing](/docs/ROUTING.md)
- [Controllers](/docs/CONTROLLERS.md)
- [Models](/docs/MODELS.md)


### Benchmarks

Here are a few sample test results using the `siege` tool. Our team is dedicated to optimizing Nebula to deliver exceptional performance, striving to position it among the top-performing PHP frameworks available 🚀 

**Coming soon**

- *Command: `siege -b -c 10 -t 1s $APP_URL`*
- *Server is on LAN*


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
