<?php 
namespace App\Traits;

use App\Upload; 
use Auth;
use File;
use Response;
use Storage; // utilize google drive API

/**
* Trait to handle file uploads
*
*/

trait uploaderTrait {
    /** 
    * upload file uploaded by user on the timeline
    *
    * @var mixed Request
    * @return string $media_link
    */
    public function uploadMedia($request, $upload_name = null)
    {
        // check if provided name is given 
        if(is_null($upload_name)) {
            $upload_to_work_with = 'media';
        } else {
            $upload_to_work_with = $upload_name;
        }

        // save the uploaded file now
        if($request->hasFile($upload_to_work_with)) {
            $media = $request->file($upload_to_work_with);
            $name = strtolower($request->get('name'));
            
            $nicename = $this->filenameSanitizer($name);
            $slug = md5($name . time());

            // $extension = strtolower($media->extension()); // form MIME type
            $extension = strtolower($media->getClientOriginalExtension()); // form filename

            $nicename =  $nicename . '-' . strtolower(session()->get('site_name'));
            $filename  = $nicename . '-' . time() . '.' . $extension;

            /*
            || Save to local storage. Google drive is much slower
            ||
            */
            $this->saveToLocalStorage($media, $slug, $extension);
            
            /*
            || Save to google drive storage
            || First save locally, then fetch it there.
            ||
            */
            // move media to location now 
            // $tempPath = storage_path() . '/app/public/tempuploads/';
            // $media->move($tempPath, $slug . '.' . $extension); // move to temp uploads path
            // $this->saveToGoogleDriveStorage($tempPath, $slug, $extension);
            
            // save into DB
            $upload = new Upload();
            $upload->user_id = Auth::id();
            $upload->slug = $slug; // use MD5 to avoid manenos
            $upload->filename = $name;
            $upload->extension = $extension;
            $upload->filesize = $media->getClientSize();
            $upload->description = $request->get('description');
            $upload->save();

            // return the record
            return $upload;
        }

        return false;
    }

    /** 
    * filename sanitizer
    *
    * @var mixed Request
    */
    public function filenameSanitizer($str)
    {
        $nicename = str_replace(' ', '-', strtolower($str));
        // Remove anything which isn't a word, whitespace, number,
        // or any of the following characters -_~,;[]().
        // if you don't need to handle multi-byte characters
        // you can use preg_replace rather than mb_ereg_replace
        $nicename = preg_replace('([^\w\s\d\-_~,;\[\]\(\).])', '', $nicename);
        // remove any runs of periods
        $nicename = preg_replace('([\.]{2,})', '', $nicename);

        return $nicename;
    }

    /** 
    * save to local storage
    * convention $path: storage_path() . '/app/public/uploads/2017/2/file.extension'
    *
    * @param mixed Request->file
    * @param string file name
    * @param string file extension
    */
    public function saveToLocalStorage($file, $slug, $extension) 
    {
        $current_year = date('Y'); // 2017
        $current_month = date('n'); // Numeric representation of a month, without leading zeros
            
        $path = storage_path('/app/public/uploads/' . $current_year . '/' . $current_month . '/'); // just like WordPress
        // query if directory exists. if not, create it.
        if(!File::exists($path)) {
            File::makeDirectory($path, 0777, true, true);
        }
        

        return true;
    }
    
    /** 
    * save to Google Drive storage
    * convention $path: cloud() . '/2017NOV/file.extension'
    *
    * @param string tempPath
    * @param string file name as slug
    * @param string file extension
    */
    public function saveToGoogleDriveStorage($tempPath, $slug, $extension) 
    {
        $current_year = date('Y'); // 2017
        $current_month = strtoupper(date('M')); // JAN, FEB
        $dirname = $current_year . $current_month;
        $path = storage_path() . '/app/public/tempuploads/'; // hold temp uploads

        $dir = '/';
        $recursive = false; // Get subdirectories also?
        $contents = collect(Storage::cloud()->listContents($dir, $recursive));

        $dir = $contents->where('type', '=', 'dir')
            ->where('filename', '=', $dirname)
            ->first(); // There could be duplicate directory names!

        if ( ! $dir) {
            // create one
            Storage::cloud()->makeDirectory($dirname);
        }

        // Storage::cloud()->put('test.txt', 'Hello World');

        // save/transfer to drive
        $tempFilePath = $tempPath . $slug . '.' . $extension;
        $tempFile = File::get($tempFilePath);
        $driveFilename = $dir['path'] . '/' . $slug . '.' . $extension;

        Storage::cloud()->put($driveFilename, $tempFile); // name, filecontents

        // delete tempfile
        File::delete($tempFilePath);
        
        return true;
    }
    
    /** 
    * download media file
    *
    * @var mixed Request
    * @var boolean streamFile
    */
    public function downloadMedia($media_link, $download=false)
    {
        $media = Upload::where('slug', $media_link)->first();

        if($media) {
            // return the real file
            return $this->downloadMediaFromLocal($media->slug . '.' . $media->extension, $download);
            // return $this->downloadMediaFromGoogleDrive($media->slug . '.' . $media->extension, $download);
        } else {
            // return a default image
            return $this->downloadMediaFromLocal('default.jpg', $download);
        }

        return false;
    }

    /** 
    * get media file from local Drive
    *
    * @var string file name
    * @var boolean downloadFile
    */
    public function downloadMediaFromLocal($filename='default.jpg', $download=false)
    {
        if($filename != 'default.jpg') {
            $current_year = date('Y'); // 2017
            $current_month = date('n'); // Numeric representation of a month, without leading zeros
            $path = storage_path('/app/public/uploads/' . $current_year . '/' . $current_month . '/' . $filename); // just like WordPress
        } else {
            $path = storage_path() . '/app/public/uploads/default.jpg';            
        }
        
        if(!File::exists($path)) { 
            // return default 
            $path = storage_path() . '/app/public/uploads/default.jpg';            
            
            return response()->file($path);
        }
        
        $file = File::get($path);
        $type = File::mimeType($path);

        if($download) {
            $response = response()->download($file, $filename);
            $response->headers->set('Content-Type', $type);
            // $response->headers->set('Content-Disposition', 'attachment; filename="' . $the_product->appname . '.apk"'); 
            // $response->headers->set('Content-Description', $the_product->appname);
            $response->headers->set('Content-Length', filesize($file));
        } else {
            $response = Response::make($file, 200);
            $response->header("Content-Type", $type);
        }
    
        return $response;
    }    


    /** 
    * get media file from Google Drive
    *
    * @var string file name
    * @var boolean downloadFile
    */
    public function downloadMediaFromGoogleDrive($filename, $download=false)
    {
        $current_year = date('Y'); // 2017
        $current_month = strtoupper(date('M')); // JAN, FEB
        $dirname = $current_year . $current_month;

        // get the directory uniqueID
        $dir = '/';
        $recursive = false; // Get subdirectories also?
        $contents = collect(Storage::cloud()->listContents($dir, $recursive));

        $folder = $contents->where('type', '=', 'dir')
            ->where('filename', '=', $dirname)
            ->first(); // There could be duplicate directory names!
        

        // get the file now 
        $dir = $folder['path']; // new directory name
        $recursive = false; // Get subdirectories also?
        $contents = collect(Storage::cloud()->listContents($dir, $recursive));

        $file = $contents
            ->where('type', '=', 'file')
            ->where('filename', '=', pathinfo($filename, PATHINFO_FILENAME))
            ->where('extension', '=', pathinfo($filename, PATHINFO_EXTENSION))
            ->first(); // there can be duplicate file names!

        //return $file; // array with file info
        $rawData = Storage::cloud()->get($file['path']);

        if($download) {
            return response($rawData, 200)
                ->header('ContentType', $file['mimetype'])
                ->header('Content-Disposition', "attachment; filename='$filename'");
        }
        return response()->make($rawData, 200);        

    }    
}