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
5. [Front-end Development Documentation](#front-end-development-documentation)
    - [Blocks](#blocks)
        - [Images](#images)
        - [Texts](#texts)
        - [Links](#links)
    - [Layouts](#layouts)
    - [Fonts, Images, and Non-dynamic Icons](#fonts-images-and-non-dynamic-icons)
    - [Styles](#styles)
6. [Documentation](#content-management-documentation)
    - [Roles](#roles)
        - [Admin Role](#admin-role)
        - [Non-Admin/User Role](#non-adminuser-role)
    - [Adding a New User](#adding-a-new-user)
    - [Adding Pages](#adding-pages)
        - [Content](#content)
        - [SEO](#seo)
    - [Top Right Buttons on the Page](#top-right-buttons-on-the-page)
    - [Organizing Pages for Navigation](#organizing-pages-for-navigation)
7. [Contributions](#contributions)
8. [License](#license)
9. [Acknowledgments](#acknowledgments)

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

1. Clone the repository to your local machine and move the contents of the "butthead" folder to the root of your web server. If you prefer, you can also manually update the `.htaccess` file. 
   
2. Ensure PHP 7 or a later version is installed on your server.

3. Run the application by accessing the "www.my-project.com/admin" URL in your web browser.

4. If you encounter issues (try again), or in your `php.ini`, uncomment the gettext and GD library. If you are unsure, consult your preferred AI or visit Stack Overflow for assistance.

5. Log in with the default credentials:
   - Default email: admin@admin.fr
   - Default password: admin

6. Once logged in, consider creating a new user account. This will replace the default credentials.

Now you should be ready to explore and utilize Butthead CMS for your content management needs!

## Front-end Development Documentation

For templating, everything or almost everything happens in the `templates` folder. Each block consists of pure HTML and CSS. Dynamic content (images, texts, etc.) should be placed between `{{ }}`.

The filename in the `blocks` directory corresponds to the block name in the administration area. For example, if I name my file `blocks/image_to_the_right.html`, the block name will be `image_to_the_right` in the administration area.

### Blocks

#### Images

Input to create an image cropped to 500x500:

```html
<div>{{ img | 500 | 500 }}</div>
```

Input to create a resized image with a width of 500px:

```html
<div>{{ img | 500 }}</div>
```

Input to create a resized image with a height of 500px:

```html
<div>{{ img | 0 | 500 }}</div>
```

Input to create a resized image with the maximum height or width of 500px:

```html
<div>{{ img | resize | 500 | 500 }}</div>
```

#### Texts

Input to create text with a minimum length of 20:

```html
<div>{{ input | 20 }}</div>
```

Input to create text with a length between 20 and 100:

```html
<div>{{ input | 20 | 100 }}</div>
```

Similarly, for multiline text:

```html
<div>{{ textarea | 20 | 100 }}</div>
```

#### Links

This will create two form elements, a select with the list of links, and an input for the link name:

```html
{{ link }}
```

### Layouts

Works similarly to blocks but should not contain dynamic elements (images, texts, etc.), only the `{{ content }}` tag. This tag will be replaced by all the blocks it contains.

### Fonts, Images, and Non-dynamic Icons

Everything happens in the `/assets` folder. In a block or layout, you can display these files via the URL `/assets/icons/filename`. The `assets` folder is not built; it is used directly in the project.

For fonts, place them in the `/assets/fonts` folder and use them directly in CSS with the same filename. For example:

```css
font-family: Poppins-Regular;
```

No need for the `@font-face` property; it will be generated directly during the build on the administration side.

### Styles

Each block/layout encapsulates its own style. For example:

`templates/blocks/red_paragraph.css`

```html
<p>{{ input | 20 | 100 }}</p>
<style>
    p {
        color: red;
    }
</style>
```

`templates/blocks/green_paragraph.css`

```html
<p>{{ input | 20 | 100 }}</p>
<style>
    p {
        color: green;
    }
</style>
```

This will display the blocks in the corresponding color.

There might be a bug if an element contains multiple classes in the CSS and/or in the HTML.

For global styles, use the `templates/styles` folder. The alphabetical order will be the compilation order.

## Content Management Documentation

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