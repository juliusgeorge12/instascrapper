# Instagram Scraper

## Overview
This project is an Instagram scraper designed to extract publicly available data from Instagram profiles, posts, and other accessible endpoints. It is intended for educational and research purposes only.

## Features
- Extract post details (captions, likes, comments, etc.).

## Requirements
- PHP 8.2 or higher
- Required libraries (install via `requirements.txt`):
    - `illuminate\container`
    - `guzzlehttp`
    - `php-webdriver\webdriver`

## Installation
1. With composer:
     ```bash
     composer require juliusgeorge/instascrapper
     ```


## Usage
This package requires you to authenticate with instagram
There are two ways of doing that 

1.  Via cookie
    Login to instagram grab the required cookies (rur,mid,sessionid,ig_id,ds_user_id,csrftoken)
    create an array of cookiename/value pair i.e
    ```php
    [
        'rur' => 'rur value',
        'csrftoken' => 'csrftoken value',
        'mid' => 'mid value',
        'ig_id' => 'ig_id value',
        'ds_user_id' => 'ds_user_id value',
        'sessionid' => 'sessionid value'
    ]
    ```
    after that call the generateAuthCookie static method of the InstaAuth\Auth class
    use InstaAuth\Auth;
    ```php
    $cookieAuth = Auth::generateCookieAuth($the_array)
    ```

    Authenticate the scrapper using
    ```php
    Scrapper::cookieAuth($cookieAuth);
    ```

    you can also generate a cookie auth token by providing your password and email|username
    
     ```php
     $auth = new Auth();
     $cookieAuth = $auth->login('your email|username', 'your password');
     ```
     use the cookie auth like in above step to authenticate
2. Scrapping comments:
     

## Disclaimer
This project is for educational purposes only. Scraping data from Instagram may violate their terms of service. Use responsibly and ensure compliance with applicable laws and regulations.

## License
This project is licensed under the [MIT License](LICENSE).