<?php
session_start();
$user = exec('whoami');
$configValet = file_get_contents("/home/$user/.config/valet/config.json");
$configValet = json_decode($configValet);
$domain = '.'.$configValet->domain;
Config::setTld($domain);
$_SESSION['config'] = $_SESSION['config'] ?? false;


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
            if (strtolower($excluded) == strtolower($this->fileName)) {
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

    private static function createFile(): void
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
    private static function save(): void
    {
        file_put_contents(self::$filename, json_encode(self::$data, JSON_PRETTY_PRINT));
    }
    public static function initData(): void
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
        return self::$data[$key] ?? $default;
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
    public static function exclude($name): void
    {
        self::initData();
        self::$data['exclude'][]=$name;
        self::save();
    }
    public static function include($name): void
    {
        self::initData();
        self::$data['exclude'] = array_diff(self::$data['exclude'],[$name]);
        self::save();
    }
    public static function excludePath($path): void
    {
        self::initData();
        self::$data['excludePath'][]=$path;
        self::save();
    }
    public static function includePath($path): void
    {
        self::initData();
        self::$data['excludePath'] = array_diff(self::$data['excludePath'],[$path]);
        self::save();
    }
}

if (isset($_GET['config'])){
    $_SESSION['config'] = !isset($_SESSION['config']) || !$_SESSION['config'];
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

if (isset($_POST['create'])) {

    $data = $_POST['create'];

    // Get all the datas from the form in separate vars for utility
    $projectName = trim($data['name']);
    $projectPath = rtrim($data['path'], '/');
    $projectType = $data['type'];
    $dbType = $data['dbType'];
    $dbName = trim($data['dbName'] ?? '');
    $dbUser = trim($data['dbUser'] ?? '');
    $dbPassword = trim($data['dbPassword'] ?? '');
    $installAdminLTE = isset($data['adminlte']) ? "1" : "0";
    $installSpatiePermission = isset($data['spatiePermission']) ? "1" : "0";
    $valetLink = isset($data['valetLink']) ? "1" : "0";

    // Where the bash script will be saved (so where VPM is)
    $scriptPath = __DIR__ . '/setup_project.sh';

    // Create the bash file if it doesn't exist
    if (!file_exists($scriptPath)) {
        $bashScript = <<<EOT
            #!/bin/bash
            
            projectName="\$1"
            projectPath="\$2"
            projectType="\$3"
            dbType="\$4"
            dbName="\$5"
            dbUser="\$6"
            dbPassword="\$7"
            installAdminLTE="\$8"
            installSpatiePermission="\$9"
            valetLink="\$10"
            
            fullProjectPath="\$projectPath/\$projectName"
            
            # Delete if the project already exist
            rm -rf "\$fullProjectPath"
            
            # Create Laravel project 
            composer create-project laravel/laravel "\$fullProjectPath" && cd "\$fullProjectPath"
            
            # Create .env file
            if [ ! -f .env ]; then
                cp .env.example .env
            fi
            
            sed -i -e "s/APP_NAME=.*/APP_NAME=\$projectName/" \
            -e "s|APP_URL=.*|APP_URL=http:\/\/\$projectName.test|" \
            -e "s/\(APP_LOCALE=en\)/APP_LOCALE=fr/g" \
            -e "s/\(APP_FALLBACK_LOCALE=en\)/APP_FALLBACK_LOCALE=fr/g" \
            -e "s/\(APP_FAKER_LOCALE=en_US\)/APP_FAKER_LOCALE=fr_FR/g" .env
            
            if [ "\$dbType" = "mysql" ]; then
                sed -i -e "s/\(sqlite\)/mysql/g" \
                -e "s/\(# DB\)/DB/g" \
                -e "s/\(DB_DATABASE=\).*/\1\$dbName/" \
                -e "s/\(DB_USERNAME=\).*/\1\$dbUser/" \
                -e "s/\(DB_PASSWORD=\).*/\1\$dbPassword/" .env
            else
                sed -i -e "s/\(DB_CONNECTION=\).*/\1sqlite/" .env
            fi
            
            # Create the link
            if [ "\$valetLink" = "1" ]; then
                valet link
            fi
            
            # Migrate
            php artisan migrate:fresh --force
            
            # Run commands for each project type
            if [ "\$projectType" = "api" ]; then
                php artisan install:api
            else
                composer require barryvdh/laravel-debugbar --dev
            fi
            
            if [ "\$projectType" = "api" ] || [ "\$projectType" = "monolithic" ]; then
                composer require laravel/tinker
            fi
            
            if [ "\$installAdminLTE" = "1" ]; then
                composer require jeroennoten/laravel-adminlte &&
                php artisan adminlte:install &&
                composer require laravel/ui &&
                php artisan ui bootstrap --auth &&
                php artisan adminlte:install --type=full --force
            fi
            
            if [ "\$installSpatiePermission" = "1" ]; then
                composer require spatie/laravel-permission
            fi
            
            npm install
        EOT;
        file_put_contents($scriptPath, $bashScript);
        chmod($scriptPath, 0755);
    }

    // Build the command line with the required arguments
    $command = "nohup bash " . escapeshellarg($scriptPath) . " "
        . escapeshellarg($projectName) . " "
        . escapeshellarg($projectPath) . " "
        . escapeshellarg($projectType) . " "
        . escapeshellarg($dbType) . " "
        . escapeshellarg($dbName) . " "
        . escapeshellarg($dbUser) . " "
        . escapeshellarg($dbPassword) . " "
        . escapeshellarg($installAdminLTE) . " "
        . escapeshellarg($installSpatiePermission) . " "
        . escapeshellarg($valetLink)
        . " > /dev/null 2>&1 &";

    // Execute the script and redirect the user
    shell_exec($command);
    header("Location: http://{$_SERVER['SERVER_NAME']}?create=$projectName");
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
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
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
            <div class="float-end mx-1">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createModal">Create</button>
            </div>
            <div class="float-end mx-1">
                <a href=".?config=1" class="btn <?= ($_SESSION['config']??false) ? 'btn-primary' : 'btn-secondary' ?>">Settings</a>
            </div>
        </div>
    </div>
</nav>

<?php if($_GET['create']): ?>
    <div class="w-25 mx-auto text-center alert alert-primary" role="alert">
        Your project <?= $_GET['create'] ?> is being created in the background.
    </div>
<?php endif; ?>

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

<!-- Modal for the create project feature -->
<div class="modal fade" id="createModal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModal">Create a new project</h5>
                <button type="button" class="close btn" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="my-2">
                        <label for="name">Project's name : </label>
                        <input class="form-control" id="name" name="create[name]" oninput="document.getElementById('dbName').value = this.value; document.getElementById('submitButton').innerHTML = 'Create '+this.value; document.getElementById('pathSmall').innerHTML = 'For example if you put /home/usersio/code the project will be in /home/usersio/code/'+this.value" required/>
                    </div>
                    <div class="my-2">
                        <label for="path">Folder where the project will be created : </label>
                        <input class="form-control" id="path" name="create[path]" oninput="document.getElementById('pathSmall').innerHTML = 'For example if you put /home/usersio/code the project will be in /home/usersio/code/" value="/home/<?= $user ?>" required/>
                        <small class="text-muted" id="pathSmall"></small>
                    </div>
                    <hr>
                    <label for="type">What type of project do you want to create ? </label>
                    <div id="type" class="my-2 d-flex justify-content-around">
                        <div class="my-1">
                            <input type="radio" id="api" name="create[type]" value="api" required/>
                            <label for="name">API</label>
                        </div>
                        <div class="my-1">
                            <input type="radio" id="monolithic" name="create[type]" value="monolithic" required/>
                            <label for="name">Monolithic</label>
                        </div>
                        <div class="my-1">
                            <input type="radio" id="headless" name="create[type]" value="headless" required/>
                            <label for="name">Headless</label>
                        </div>
                    </div>
                    <hr>
                    <label for="dbType">What kind of SGBD do you want to use ?</label>
                    <div id="dbType" class="my-2 d-flex justify-content-around">
                        <div class="my-1">
                            <input type="radio" id="sqlite" name="create[dbType]" value="sqlite" oninput="document.getElementById('dbName').disabled = true; document.getElementById('dbUser').disabled = true; document.getElementById('dbPassword').disabled = true;" required/>
                            <label for="name">SQLite</label>
                        </div>
                        <div class="my-1">
                            <input type="radio" id="mysql" name="create[dbType]" value="mysql" oninput="document.getElementById('dbName').disabled = false; document.getElementById('dbUser').disabled = false; document.getElementById('dbPassword').disabled = false;" required/>
                            <label for="name">MySQL</label>
                        </div>
                    </div>
                    <div class="my-2">
                        <label for="dbName">Database's name : </label>
                        <input class="form-control" id="dbName" name="create[dbName]" onclick="this.value = document.getElementById('name').value"/>
                    </div>
                    <div class="my-2">
                        <label for="dbUser">Database's username : </label>
                        <input class="form-control" id="dbUser" name="create[dbUser]" value="usersio"/>
                    </div>
                    <div class="my-2">
                        <label for="dbPassword">Database's user's password : </label>
                        <input type="password" class="form-control" id="dbPassword" name="create[dbPassword]" value="pwsio"/>
                    </div>
                    <hr>
                    <label for="addons">Options : </label>
                    <div id="addons" class="my-2 d-flex justify-content-around">
                        <div class="my-1">
                            <input type="checkbox" id="valetLink" name="create[valetLink]" value="valetLink"/>
                            <label for="adminlte">Valet link the project</label>
                        </div>
                        <div class="my-1">
                            <input type="checkbox" id="adminlte" name="create[adminlte]" value="adminlte"/>
                            <label for="adminlte">Install AdminLTE</label>
                        </div>
                        <div class="my-1">
                            <input type="checkbox" id="spatiePermission" name="create[spatiePermission]" value="spatiePermission"/>
                            <label for="adminlte">Install Spatie Permission</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="submitButton">Create the project</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
