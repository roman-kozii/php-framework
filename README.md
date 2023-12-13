A High-Performance PHP Framework (CRM SaaS web app)


## 👾 Introduction

Welcome to Nebula, an advanced PHP framework designed to elevate your web development projects to new heights!

## 👽 Features

- 🔥 **Blazing Speed**: Leverage Nebula's **htmx** capabilities for lightning-fast user experiences.
- 🥷 **Flexibility**: Craft custom web applications effortlessly with Nebula's extensible architecture.
- 👷 **Under Development**: Nebula is actively evolving to offer the best in class features.
- ❌ **Not for Production**: Please note that Nebula is currently not recommended for production use.


## 🚀 Getting Started

Follow these steps to kickstart your Nebula journey:

1. **Installation**: Clone the repository and install dependencies using Composer:
   ```bash
   git clone https://github.com/roman-kozii/php-framework.git
   cd php-framework
   composer install
   ```

2. **Configuration**: Customize your project by editing configuration files, including database settings. Copy the example environment configuration:
   ```bash
   cp .env.example .env
   ```

3. **Dependencies**: Nebula requires essential dependencies such as Redis (caching, rate limiting) and MySQL (database). See documentation.

4. **Development**: Begin building your application by creating controllers, views, and models within the `app` directory. Feel free to modify the framework source code located in the  `src` directory. Start a local development server with:
   ```bash
   ./nebula -s
   # Application will be served here: http://127.0.0.1:8888/
   ```


## ⭐ Docker Integration

Simplify your deployment process with Docker. Launch the entire stack in one command

1. Launch the Nebula stack:
   ```bash
   docker-compose up --build -d
   # Application will be served here: http://localhost/
   ```

2. Shut down the stack:
   ```bash
   docker-compose down
   ```

3. Access the application container:
   ```bash
   docker-compose exec nebula-app bash
   # Try launching the cli tool
   ./nebula -h
   ```

   `/shared/httpd`: project base directory

4. Access the database container:
   ```bash
   docker-compose exec nebula-mysql bash
   # Start MySQL with your .env db password credentials
   mysql -u root -p
   ```

5. Access the logs:
   ```bash
   docker-compose logs -f 
   ```
   


## 🖥️ Documentation

Note: documentation will be released in v0.0.1


## ⚡ Benchmarks

We are committed to optimizing Nebula for top-tier performance. Stay tuned for benchmark updates.


## 🌜 Contributing

Contributions to Nebula are appreciated. If you encounter issues or have enhancement suggestions, open an issue or submit a pull request.


## 🔑 License

This project is licensed under the [MIT License](https://github.com/libra-php/nebula/blob/main/LICENSE).


## 🌠 Acknowledgements

It draws inspiration from leading open-source projects including Symfony, Slim Framework, and Laravel.
