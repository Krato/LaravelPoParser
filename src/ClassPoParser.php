<?php namespace EricLagarda\LaravelPoParser;



use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use sepia\FileHandler;
use sepia\PoParser;
use Gettext\Extractors;
use Gettext\Generators;
use Gettext\Translations;
use Gettext\Translator;
use Config;
use App;
use File;

class ClassPoParser {

	private static $config = [];
    private static $locale;


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

    public static function getEntries($locale, $refresh = true)
    {
        if (empty($refresh) && ($cache = self::getCache($locale))) {
            return $cache;
        }
        $entries = clone self::scan();

        if (is_file($file = self::getFile($locale).'mo')) {
            $entries->mergeWith(Extractors\Mo::fromFile($file));
        }
        return $entries;
    }

    private static function store($locale, $entries)
    {
        $file = self::getFile($locale);
        $dir = base_path()."/".dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        Generators\Mo::toFile($entries, base_path()."/".$file.'mo');
        Generators\Po::toFile($entries, base_path()."/".$file.'po');
        Generators\PhpArray::toFile($entries, base_path()."/".$file.'php');
        return $entries;
    }

    public static function setEntries($locale, $translations)
    {
        if (empty($translations)) {
            return true;
        }
        $entries = self::getCache($locale) ?: (new Translations());
        foreach ($translations as $msgid => $msgstr) {
            $msgid = urldecode($msgid);
            if (!($entry = $entries->find(null, $msgid))) {
                $entry = $entries->insert(null, $msgid);
            }
            $entry->setTranslation($msgid);
        }

        self::store($locale, $entries);
        return $entries;
    }

    public static function load()
    {

        $locale = self::$locale;

        # IMPORTANT: locale must be installed in server!
        # sudo locale-gen es_ES.UTF-8
        # sudo update-locale
        putenv('LANG='.$locale);
        putenv('LANGUAGE='.$locale);
        putenv('LC_MESSAGES='.$locale);
        putenv('LC_PAPER='.$locale);
        putenv('LC_TIME='.$locale);
        putenv('LC_MONETARY='.$locale);
        setlocale(LC_MESSAGES, $locale);
        setlocale(LC_COLLATE, $locale);
        setlocale(LC_TIME, $locale);
        setlocale(LC_MONETARY, $locale);
        bindtextdomain(self::$config['domain'], self::$config['storage']);
        bind_textdomain_codeset(self::$config['domain'], 'UTF-8');
        textdomain(self::$config['domain']);
        # Also, we will work with gettext/gettext library
        # because PHP gones crazy when mo files are updated
        $path = base_path()."/".dirname(self::getFile(self::$locale));
        $file = $path.'/'.self::$config['domain'];


        if (is_file($file.'.php')) {
            $translations = $file.'.php';
        } elseif (is_file($file.'.mo')) {
            $translations = Translations::fromMoFile($file.'.mo');
        } elseif (is_file($file.'.po')) {
            $translations = Translations::fromPoFile($file.'.po');
        } else {
            $translations = new Translations();
        }
        $translations = new Translations();
        
        $translations->setLanguage($locale);
        $trans = new Translator();
        $trans->loadTranslations($translations);
        Translator::initGettextFunctions($trans);
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