<?php namespace EricLagarda\LaravelPoParser;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Gettext\Extractors;
use Gettext\Generators;
use Gettext\Translations;
use Gettext\Translator;
use Filesystem;
use Config;
use App;
use File;

require('php-mo.php');

class ClassPoParser {

	private static $config = [];
    private static $locale;
    private static $currentTranslation;


    public function __construct()
    {
    	self::$config =  Config::get('laravel-po-parser');
    	
    }


    public static function setConfig(array $config)
    {
        self::$config = $config;
    }

    public static function setLocale($locale){
    	self::$locale = $locale;
    }

    private static function getFile($locale)
    {
        return sprintf('%s/%s/LC_MESSAGES/%s.', self::$config['storage'], $locale, self::$config['domain']);
    }


    private static function getCache($locale)
    {
        if (is_file($file = self::getFile($locale).'po')) {
            return Extractors\Po::fromFile($file);
        }
        return false;
    }

    private static function scan()
    {
        Extractors\PhpCode::$functions = [
            '__' => '__',
            '_' => '__',
        ];
        $entries = new Translations();
        foreach (self::$config['directories'] as $dir) {
        	$dir = base_path()."/".$dir;
            if (!is_dir($dir)) {
                throw new Exception(__('Folder %s not exists. Gettext scan aborted.', $dir));
            }
            foreach (self::scanDir($dir) as $file) {
                if (strstr($file, '.blade.php')) {
                    $entries->mergeWith(Extractors\Blade::fromFile($file));
                } elseif (strstr($file, '.php')) {
                    $entries->mergeWith(Extractors\PhpCode::fromFile($file));
                }
            }
        }
        return $entries;
    }

    private static function scanDir($dir)
    {
        $directory = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);
        $files = [];
        foreach ($iterator as $fileinfo) {
            $name = $fileinfo->getPathname();

            if (!strpos($name, '/.')) {
                $files[] = $name;
            }
        }
        return $files;
    }

    public static function getEntries($refresh = true){
        if (empty($refresh) && ($cache = self::getCache(self::$locale))) {
            return $cache;
        }
        $file = self::getFile(self::$locale);
        if (is_file(base_path()."/".$file.'po')) {
            $entries = Translations::fromMoFile(base_path()."/".$file.'mo');
        }
        return $entries;
    }

    public static function getAllEntries($refresh = true)
    {
        if (empty($refresh) && ($cache = self::getCache(self::$locale))) {
            return $cache;
        }
        $entries = clone self::scan();
        $file = self::getFile(self::$locale);
        if (is_file(base_path()."/".$file.'mo')) {
            $entries->mergeWith(Extractors\Mo::fromFile(base_path()."/".$file.'mo'));
        }
        self::$currentTranslation = $entries;
        return $entries;
    }


    public static function getNewEntries($refresh = true)
    {
        if (empty($refresh) && ($cache = self::getCache(self::$locale))) {
            return $cache;
        }
        $entries = clone self::scan();
        $file = self::getFile(self::$locale);
        if (is_file(base_path()."/".$file.'mo')) {
            $entries->mergeWith(Extractors\Mo::fromFile(base_path()."/".$file.'mo'));
        }
        $new_entries = new Translations();
        foreach($entries as $entry){
            if(!$entry->getTranslation()){
                $new_entries[] = $entry;
            }
        }
        self::$currentTranslation = $new_entries;
        return $new_entries;
    }


    public static function UpdateAll($refresh = true){
        if (empty($refresh) && ($cache = self::getCache(self::$locale))) {
            return $cache;
        }
        $entries = clone self::scan();
        $file = self::getFile(self::$locale);
        if (is_file(base_path()."/".$file.'mo')) {
            $entries->mergeWith(Extractors\Mo::fromFile(base_path()."/".$file.'mo'));
            $entries->mergeWith(Extractors\Po::fromFile(base_path()."/".$file.'po'));
        }

        self::setTranslationObject($entries);
        self::UpdateFiles();
    }

    private static function store($entries)
    {

        $file = self::getFile(self::$locale);

        $dir = base_path()."/".dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $entries->setHeader('Language', self::$locale);
        Generators\Po::toFile($entries, base_path()."/".$file.'po');
        // Generators\PhpArray::toFile($entries, base_path()."/".$file.'php');
        return $entries;
    }

    public static function setEntries($translations)
    {
        if (empty($translations)) {
            return true;
        }
        $entries = self::getCache(self::$locale) ?: (new Translations());
        foreach ($translations as $msgid => $msgstr) {
            $msgid = urldecode($msgid);
            if (!($entry = $entries->find(null, $msgid))) {
                $entry = $entries->insert(null, $msgid);
            }
            $entry->setTranslation($msgid);
        }

        self::store(self::$locale, $entries);
        return $entries;
    }

    public static function load()
    {

        $locale = self::$locale;

        # IMPORTANT: locale must be installed in server!
        # sudo locale-gen es_ES.UTF-8
        # sudo update-locale

        // $_ENV["LANG"] = $locale;
        // $_ENV["LANGUAGE"] = $locale;

        // $_ENV["LC_MESSAGES"] = $locale;
        // $_ENV["LC_PAPER"] = $locale;
        // $_ENV["LC_TIME"] = $locale;
        // $_ENV["LC_MONETARY"] = $locale;
        // setlocale(LC_MESSAGES, $locale);
        // setlocale(LC_COLLATE, $locale);
        // setlocale(LC_TIME, $locale);
        // setlocale(LC_MONETARY, $locale);
        // bindtextdomain(self::$config['domain'], self::$config['storage']);
        // bind_textdomain_codeset(self::$config['domain'], 'UTF-8');
        // textdomain(self::$config['domain']);
        # Also, we will work with gettext/gettext library
        # because PHP gones crazy when mo files are updated
        $path = base_path()."/".dirname(self::getFile(self::$locale));
        $file = $path.'/'.self::$config['domain'];


        $isMO = false;
        $isPo = False;
        if(is_file($file.'.mo')) {
            $isMO = true;
            $translations = Translations::fromMoFile($file.'.mo');
        } 

        if(is_file($file.'.po')) {
            $isPo = true;
            $translationsPo = Translations::fromPoFile($file.'.po');
        }



        if($isMO && $isPo){
            $translations->mergeWith($translationsPo);
        }elseif($isPo) {
            $translations = $translationsPo;
        } else {
            $translations = new Translations();
        }

        $translations->setLanguage($locale);
        $translations->setHeader('LANGUAGE', $locale);


        self::$currentTranslation = $translations;


        // $trans = new Translator();
        // $trans->loadTranslations($translations);

        // Translator::initGettextFunctions($trans);
    }

    public static function updateMO(){

        $path = base_path()."/".dirname(self::getFile(self::$locale));
        $file = $path.'/'.self::$config['domain'];

        $translations = Translations::fromPoFile($file.'.po');  
        $translations->toMoFile($file.'.mo');

    }

    public static function find($key){
        $locale = self::$locale;
       
        $translation = self::$currentTranslation;
        $found = $translation->find('', $key);
         
        return $found;
    }

    public static function setTranslation($translation, $string){

        $translation->setTranslation($string);
        return $translation;
    }


    public function saveTranslation($key, $new_string, $plurals = false, $comments = false){

        $translations = self::getTranslationObject();
        
        $translation = $translations->find(null, $key);

        if ($translation) {
            $translation->setTranslation($new_string);
            if($plurals){
                $translation->setPluralTranslation($plurals);
            }
            if($comments){
                $translation->addComment($comments);
            }
            
        }

        self::UpdateFiles();
        return true;
       
    }

    public static function UpdateFiles(){
        $translations = self::getTranslationObject();
        $path = base_path()."/".dirname(self::getFile(self::$locale));
        $file = $path.'/'.self::$config['domain'];

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $translations->toPoFile($file.'.po');
        self::updateMO();
        clearstatcache();
    }

    public static function setTranslationObject($translation){
        self::$currentTranslation = $translation;
    }

    public static function getTranslationObject(){
        return self::$currentTranslation;
    }

    public function FileHandler($file)
    {
            return new \Sepia\FileHandler($file);
       
    } 


     public function get($filehandler)
    {
        
        return new \Sepia\PoParser($filehandler);
       
    } 
    
}
