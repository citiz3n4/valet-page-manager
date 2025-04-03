<?php
session_start();
$user = exec('whoami');
$configValet = file_get_contents("/home/$user/.config/valet/config.json");
$configValet = json_decode($configValet);
$domain = '.'.$configValet->domain;
Config::setTld($domain);
$_SESSION['config'] = isset($_SESSION['config']) ? $_SESSION['config'] : false;


function valetUnlink($project): void {
    exec('valet unlink '.$project);
}

enum LinkType {
    case Link;
    case Park;
}

class Link {
    public bool $secure = false;
    public string $fileName;
    public string $link;

    private LinkType $type;

    public function __construct($fileName,LinkType $type)
    {
        $this->secure = $this->isSecure($fileName);
        $this->fileName = $fileName;
        $this->link = $this->secure ? 'https://'.$this->fileName.Config::get('app')['tld'] : 'http://'.$this->fileName.Config::get('app')['tld'];
        $this->type = $type;
    }

    public function lock() : string {
        if ($this->secure) {
            $html = '<div class="btn btn-success pe-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 448 512"><path d="M144 144l0 48 160 0 0-48c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192l0-48C80 64.5 144.5 0 224 0s144 64.5 144 144l0 48 16 0c35.3 0 64 28.7 64 64l0 192c0 35.3-28.7 64-64 64L64 512c-35.3 0-64-28.7-64-64L0 256c0-35.3 28.7-64 64-64l16 0z"/></svg>
                    </div>';
        }else{
            $html = '<div class="btn btn-danger pe-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 576 512"><path d="M352 144c0-44.2 35.8-80 80-80s80 35.8 80 80l0 48c0 17.7 14.3 32 32 32s32-14.3 32-32l0-48C576 64.5 511.5 0 432 0S288 64.5 288 144l0 48L64 192c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-192c0-35.3-28.7-64-64-64l-32 0 0-48z"/></svg>
                    </div>';
        }
        return $html;
    }
    public function isSecure($fileName) : bool {
        global $user;
        $crt = glob("/home/$user/.config/valet/Certificates/".$fileName.Config::get('app')['tld'].".crt");
        return !empty($crt);
    }
    public function card() : string {
        $html = '<div class="col-3">
                    <div class="card mb-4">
                        <div class="card-body">
						    <h5 class="card-title">'.$this->fileName.'</h5>';
        if(!$_SESSION['config']) {
            $html .= '<a target="_blank" href="' . $this->link . '" class="btn btn-primary">Open</a>
						    ' . $this->lock();
        }elseif($this->isExcluded()) {
            $html .= '<form action="" method="post" class="d-inline">
						        <input hidden="hidden" name="include" value="' . $this->fileName . '">
						        <button type="submit" class="btn btn-success">Include</button>
						    </form>';
        }else{
            if($this->type===LinkType::Link) {
                $html .= '<form action="" method="post" class="d-inline">
						        <input hidden="hidden" name="unlink" value="' . $this->fileName . '">
						        <button type="submit" class="btn btn-warning">Unlink</button>
						    </form>';
            }
            $html .= '<form action="" method="post" class="d-inline">
						        <input hidden="hidden" name="exclude" value="' . $this->fileName . '">
						        <button type="submit" class="btn btn-warning">Exclude</button>
						    </form>';
        }
        $html.='        </div>
				    </div>
				</div>';
        return $html;
    }
    public function isExcluded(): bool
    {
        foreach (Config::get('exclude') as $excluded) {
            if ($excluded == $this->fileName) {
                return true;
            }
        }
        return false;
    }

}
class Park
{
    public string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function isExcluded(): bool
    {
        foreach (Config::get('excludePath') as $excludedPath) {
            if ($excludedPath == $this->path) {
                return true;
            }
        }
        return false;
    }

}
class Nav
{

    public static function itemButton($link, $name,$icon=null,$target='_blank') : string {
        return '<li class="nav-item">
                    <a target="'.$target.'" class="nav-link" href="'.$link.'">
		            	'.(null!==$icon ? '<img src="'.$icon.'" width="32" height="32"> ' : $name).
            '</a>
                </li>';
    }
    public static function item($name) : string {
        return '<li class="nav-item">
                    <div class="nav-link pe-none">'.$name.'</div>
                </li>';

    }
}

class Config
{
    private static ?array $data=null;
    private static string $filename='config.json';

    private static function createFile()
    {
        global $domain;
        if(!file_exists(self::$filename)){
            self::$data = [
                'exclude'=>[
                    'phpmyadmin',
                    'phpinfo'
                ],
                'excludePath' => [

                ],
                'headers' => [
                    ['type'=>'link','domain'=>'phpmyadmin', 'name'=>'PHPMyAdmin','icon'=>'https://www.phpmyadmin.net/static/favicon.ico','target'=>'_blank'],
                    ['type'=>'link','domain'=>'phpinfo', 'name'=>'PHPInfo', 'icon'=> 'https://www.php.net/favicon.ico','target'=>'_blank'],
                    ['type'=> 'tld']
                ],
                'app' => [
                    'name' => 'Valet Page Manager',
                    'icon' => 'https://valetlinux.plus/favicon.ico',
                    'tld' => $domain
                ]
            ];
            self::save();
        }
    }
    private static function save()
    {
        file_put_contents(self::$filename, json_encode(self::$data, JSON_PRETTY_PRINT));
    }
    public static function initData()
    {
        self::$data = json_decode(file_get_contents(self::$filename), true);
    }
    public static function get($key,$default=null)
    {
        if(!file_exists(self::$filename)){
            self::createFile();
        }

        if(null==self::$data){
            self::initData();
        }
        return isset(self::$data[$key]) ? self::$data[$key] : $default;
    }
    public static function setTld($value): void
    {
        if(!file_exists(self::$filename)){
            self::createFile();
        }

        if(null==self::$data){
            self::initData();
        }

        self::initData();
        self::$data['app']['tld'] = $value;
        self::save();
    }
    public static function exclude($name)
    {
        self::initData();
        self::$data['exclude'][]=$name;
        self::save();
    }
    public static function include($name)
    {
        self::initData();
        self::$data['exclude'] = array_diff(self::$data['exclude'],[$name]);
        self::save();
    }
    public static function excludePath($path)
    {
        self::initData();
        self::$data['excludePath'][]=$path;
        self::save();
    }
    public static function includePath($path)
    {
        self::initData();
        self::$data['excludePath'] = array_diff(self::$data['excludePath'],[$path]);
        self::save();
    }
}

if (isset($_GET['config'])){
    $_SESSION['config'] = isset($_SESSION['config']) ? !$_SESSION['config'] : true ;
    header( "Location: http://{$_SERVER['SERVER_NAME']}");
    exit();
}
if (isset($_POST['unlink'])) {
    valetUnlink($_POST['unlink']);
    header( "Location: http://{$_SERVER['SERVER_NAME']}");
    exit();
}
if (isset($_POST['exclude'])) {
    Config::exclude($_POST['exclude']);
    header( "Location: http://{$_SERVER['SERVER_NAME']}");
    exit();
}
if (isset($_POST['include'])) {
    Config::include($_POST['include']);
    header( "Location: http://{$_SERVER['SERVER_NAME']}");
    exit();
}
if (isset($_POST['excludePath'])) {
    Config::excludePath($_POST['excludePath']);
    header( "Location: http://{$_SERVER['SERVER_NAME']}");
    exit();
}
if (isset($_POST['includePath'])) {
    Config::includePath($_POST['includePath']);
    header( "Location: http://{$_SERVER['SERVER_NAME']}");
    exit();
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title> <?= Config::get('app')['name'] ?> </title>
    <link rel="icon" href="<?= Config::get('app')['icon'] ?>">
    <!-- Bootstrap core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <style>
        .vl {
            border-left: 2px solid;
            height: 30px;
            color: #6F7378;
            margin-top: 5px;
        }
    </style>
</head>
<body>


<nav class="navbar navbar-expand-md navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <ul class="navbar-nav w-100 mb-2 mb-md-0">
                <?=

                implode(
                    '<div class="vl"></div>',
                    array_map(fn($item) => match ($item['type']) {
                        'tld' => Nav::item('TLD : ' . $domain),
                        default => Nav::ItemButton('http://'.$item['domain'].Config::get('app')['tld'], $item['name'],$item['icon'] ?? null,$item['target']??null),
                    }, Config::get('headers'))
                )
                ?>
            </ul>
            <div class="float-end">
                <a href=".?config=1" class="btn <?= ($_SESSION['config']??false) ? 'btn-primary' : 'btn-secondary' ?>">Settings</a>
            </div>
        </div>
    </div>
</nav>

<main class="container">
    <div class="row">
        <?php foreach ($configValet->paths as $path): ?>
            <?php if (basename($path) == 'Sites'): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Links</h5>
                        <div class="row">
                            <?php
                            foreach(glob($path.'/*',GLOB_ONLYDIR) as $dir){
                                if (basename($dir).$domain != $_SERVER['HTTP_HOST']) {
                                    $dir = basename($dir);
                                    $link = new Link($dir,LinkType::Link);
                                    if (!$link->isExcluded() || $_SESSION['config'] ) {
                                        echo $link->card();
                                    }
                                }
                            }?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php $park = new Park($path); ?>
                <?php if (!$park->isExcluded() || $_SESSION['config']) : ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <?php if ($_SESSION['config']): ?>
                                <div class="mb-4">
                                    <div class="float-end">
                                        <?php if ($park->isExcluded()): ?>
                                            <form action="" method="post" class="d-inline">
                                                <input hidden="hidden" name="includePath" value="<?= $park->path ?>">
                                                <button type="submit" class="btn btn-success">Include</button>
                                            </form>
                                        <?php else: ?>
                                            <form action="" method="post" class="d-inline">
                                                <input hidden="hidden" name="excludePath" value="<?= $park->path ?>">
                                                <button type="submit" class="btn btn-warning">Exclude</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <h5 class="card-title">Park links : <?= $park->path ?></h5>
                                </div>
                            <?php else : ?>
                                <h5 class="card-title">Park links : <?= $park->path ?></h5>
                            <?php endif; ?>

                            <div class="row">
                                <?php
                                foreach(glob($path.'/*',GLOB_ONLYDIR) as $dir){
                                    if (basename($dir).$domain != $_SERVER['HTTP_HOST']) {
                                        $dir = basename($dir);
                                        $link = new Link($dir,LinkType::Park);
                                        if (!$link->isExcluded() || $_SESSION['config']){
                                            echo $link->card();
                                        }
                                    }
                                }?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</main>


</body>
</html>
