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
class Nav {

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
                'exclude'=>['phpmyadmin','phpinfo'],
                'headers' => [
                    ['type'=>'link','domain'=>'phpmyadmin', 'name'=>'PHPMyAdmin','icon'=>'https://www.phpmyadmin.net/static/favicon.ico','target'=>'_blank'],
                    ['type'=>'link','domain'=>'phpinfo', 'name'=>'PHPInfo', 'icon'=> 'https://www.php.net/favicon.ico','target'=>'_blank'],
                    ['type'=> 'tld']
                ],
                'app' => [
                    'name' => 'Valet Page Manager',
                    'icon' => [
                        'type' => 'base64',
                        'data' => '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',
                    ],
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

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title> <?= Config::get('app')['name'] ?> </title>
    <!-- Icon need to be encode in base64 -->
    <link rel="icon" href="data:image/x-icon;<?= Config::get('app')['icon']['type'].','.Config::get('app')['icon']['data'] ?>">
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
                <a href=".?config=1" class="btn <?= ($_SESSION['config']??false) ? 'btn-primary' : 'btn-secondary' ?>">Config</a>
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
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Park links : <?= $path ?></h5>
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
        <?php endforeach; ?>
    </div>
</main>


</body>
</html>
