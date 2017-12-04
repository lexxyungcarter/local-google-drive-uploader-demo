# Local / Google Drive API v3 Basic Uploader Demo (Using Laravel)

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel attempts to take the pain out of development by easing common tasks used in the majority of web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, yet powerful, providing tools needed for large, robust applications. A superb combination of simplicity, elegance, and innovation give you tools you need to build any application with which you are tasked.

# Intro
This project is aimed at helping you get a good idea about the Laravel way of saving files. If using Laravel 5+, you can simply download the files and copy them to their respective locations. It uses the same logic that Facebook uses for displaying media (where each link to a media file is hashed - this project uses the md5() approach to obtain unique URLs).

You can find more information regarding how to get Google Drive credentials [HERE](https://gist.github.com/ivanvermeyen/cc7c59c185daad9d4e7cb8c661d7b89b). This project assumes you already have the keys in the `.env` file.

```
FILESYSTEM_CLOUD=google
GOOGLE_DRIVE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_DRIVE_CLIENT_SECRET=xxx
GOOGLE_DRIVE_REFRESH_TOKEN=xxx
GOOGLE_DRIVE_FOLDER_ID=null
``` 

When the application hits the `/media/show/{link?}` route, the `show` method is invoked in the `Mediacontroller` method, which in turn invokes `downloadMedia` method present in the `uploaderTrait` trait. This method check to see if the link exists in the database, and proceeds to process the download/stream via the method you have specified.
* downloadMediaFromLocal  - Downloads/Streams a file stored in the `local` disk. 
* downloadMediaFromGoogleDrive  - Downloads/Streams a file stored in the `Google Drive` disk. 

Feel free to customize the code to suit your environment. Same goes for uploading files. 

`Note:` Fetching files from Google Drive takes more time than accessing files stored in your app's local filesystem. Hence, do not use it to fetch and put files requring rapid and multiple concurrent access. It's a drive after all, whose primary goal is to store files. You can opt for the premium `Google cloud Storage` or `Amazon's AWS` for commercial-oriented filesystems.


## Getting Started

The basic files included here are
* uploaderTrait.php             /app/Traits/
* MediaController.php           /app/Http/Controllers/
* GoogleDriveServiceProvider    /app/Providers/

Go through them to get a general idea of the whole flow.

Start by requiring this package

```
composer require nao-pon/flysystem-google-drive
```

Or simply including it in your `composer.json` file

```
"nao-pon/flysystem-google-drive": "~1.1"
```

Followed by adding the `GoogleDriveServiceProvider` in your `config/app.php`

```

...

/*
* Application Service Providers...
*/
App\Providers\AppServiceProvider::class,
App\Providers\AuthServiceProvider::class,
App\Providers\BroadcastServiceProvider::class,
App\Providers\EventServiceProvider::class,
App\Providers\RouteServiceProvider::class,
App\Providers\GoogleDriveServiceProvider::class, // for Google Drive API

```

Finally, add the following in `/config/filesystems.php` under `disks`

```
'local' => [
    'driver' => 'local',
    'root' => storage_path('app'),
],

...

'google' => [
    'driver' => 'google',
    'clientId' => env('GOOGLE_DRIVE_CLIENT_ID'),
    'clientSecret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    'refreshToken' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    'folderId' => env('GOOGLE_DRIVE_FOLDER_ID'),
],
```


## Security Vulnerabilities

If you discover a security vulnerability within this demo, please send an email to Lexx YungCarer at lexxyungcarter@gmail.com.

## License

* [MIT license](http://opensource.org/licenses/MIT).
