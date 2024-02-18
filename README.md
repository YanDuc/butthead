# butthead CMS

## Introduction

This is a Content Management System (CMS) designed to facilitate a clear separation between front-end development and content management. The goal is to provide a simple and lightweight solution that allows developers to focus on HTML and CSS while enabling webmasters to manage content efficiently.

## Features

### Developer-Focused
- Requires basic knowledge of HTML and CSS.
- Utilizes small templates that can be stacked together.

### Webmaster-Friendly
- Minimal configuration required.
- No WYSIWYG editor; uses text areas to replace content where necessary.

### Configuration and Dependencies
- Currently, no dependencies on other modules.
- Consideration for compatibility with older PHP versions, though not tested on versions below PHP 8.
- No database dependency; designed for easy deployment by copying the 'build' folder to hosting.

## Project Status

The admin section is approaching completion, but some refactoring work is still needed. The following tasks are pending:

1. Implement header and footer management.
2. Apply CSS encapsulation for multiple classes.
3. Integrate additional content types, like dates and links, which require careful design considerations.
4. Finalize the build feature.

Once these tasks are completed, the next steps involve wrapping up the build process to generate the necessary files.

**Note:** This project is a work in progress, and it is not yet finished. Expect ongoing development and updates.

## Getting Started

1. Clone the repository to your local machine.
2. Ensure PHP 7 or later is installed.
3. Open your terminal and navigate to the project directory.
4. Run the application by accessing the "butthead/admin" URL in your browser.
5. Log in with the default credentials:
   - Default email: admin@admin.fr
   - Default password: admin
6. Once logged in, consider creating a new user account. This will replace the default credentials.

## Contributions

Contributions and feedback are welcome. Feel free to open issues or pull requests.

## License

This project is licensed under the [MIT License](LICENSE).

## Acknowledgments

- Thank you to all contributors and supporters of this project.

**Happy Coding!**
