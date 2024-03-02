# Butthead CMS

## Table of Contents
1. [Introduction](#introduction)
2. [Features](#features)
    - [Developer-Focused](#developer-focused)
    - [Webmaster-Friendly](#webmaster-friendly)
    - [Configuration and Dependencies](#configuration-and-dependencies)
3. [Project Status](#project-status)
    - [Overview](#overview)
    - [Known Bugs](#known-bugs)
4. [Getting Started](#getting-started)
5. [Documentation](#butthead-documentation)
    - [Roles](#roles)
        - [Admin Role](#admin-role)
        - [Non-Admin/User Role](#non-adminuser-role)
    - [Adding a New User](#adding-a-new-user)
    - [Adding Pages](#adding-pages)
        - [Content](#content)
        - [SEO](#seo)
    - [Top Right Buttons on the Page](#top-right-buttons-on-the-page)
    - [Organizing Pages for Navigation](#organizing-pages-for-navigation)
6. [Contributions](#contributions)
7. [License](#license)
8. [Acknowledgments](#acknowledgments)

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

**Usable but with Some Bugs**

### Overview

This project is currently in a usable state; however, there are a few bugs that users should be aware of. We appreciate your patience and understanding as we work to resolve these issues.

### Known Bugs

1. **Build site**
   - Old pages are not deleted after a new build, particularly when the URL changes.

2. **Change block not change previews post**
   - It's not really a bug but an option should be created.

3. **No return possible if build crashes**
   - The content will be broken; pray for not losing your data.

## Getting Started

1. Clone the repository to your local machine.
2. Ensure PHP 7 or later is installed.
3. Open your terminal and navigate to the project directory.
4. Run the application by accessing the "butthead/admin" URL in your browser.
5. Log in with the default credentials:
   - Default email: admin@admin.fr
   - Default password: admin
6. Once logged in, consider creating a new user account. This will replace the default credentials.

## Butthead Documentation

### Roles

There are two roles in Butthead: admin and non-admin.

#### Admin Role
- Admins can add, remove, and modify user permissions.
- They have the authority to modify all pages on the site and publish them.

#### Non-Admin/User Role
- Users with non-admin privileges can edit pages for which they have permission and publish only those pages.

### Adding a New User

1. Click on the gear icon at the top left.
2. Select "Add New User."
3. An automatically generated password will be displayed, give it to your friend.
4. The user can log in using this password and change it as needed.

### Adding Pages

1. In the left sidebar, click on the "Add Page" button.
2. Two options will appear: **Content** and **SEO**.

#### Content
- Allows you to add text and images by clicking on a menu element (block or layout).
- You can organize, modify, delete, and copy each block using the options that appear on hover.

#### SEO
- Enables you to change the page title and modify the description, crucial for search engine optimization.

### Top Right Buttons on the Page

- **Preview:** Allows you to view the page without publishing it.
- **Publish:** Publishes the page, making it visible on the site.
- **Add Page:** Adds a sub-page, visible in the site's dropdown menu under the parent page.
- **Delete:** Deletes the page.
- **Copy:** Copies the entire page (remember to modify the content and SEO for the new page).

### Organizing Pages for Navigation

In the left menu, you can drag and drop pages to arrange them as desired. Note that Butthead supports only one level of depth (no sub-pages within sub-pages), making drag and drop not possible in such cases.

This covers the basic functionalities for now.

## Contributions

Contributions and feedback are welcome. Feel free to open issues or pull requests.

## License

This project is licensed under the [MIT License](LICENSE).

## Acknowledgments

- Thank you to all contributors and supporters of this project.

**Happy Coding!**