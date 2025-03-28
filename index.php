<?php
if (!file_exists('config.json')) {
    exec('touch config.json');
    $configJson = fopen('config.json', 'w');
    fwrite($configJson, json_encode([
        "exclude" => ["phpmyadmin" ,"phpinfo"],
    ]));
}

$user = exec('whoami');
$config = file_get_contents("/home/$user/.config/valet/config.json");
$config = json_decode($config);
$domain = '.'.$config->domain;
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Valet Page Manager</title>
    <!-- Bootstrap core CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

  </head>
  <body>
  <style>
      .vl {
          border-left: 2px solid;
          height: 30px;
          color: #6F7378;
          margin-top: 5px;
      }
  </style>

    
<nav class="navbar navbar-expand-md navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <div class="collapse navbar-collapse" id="navbarCollapse">
      <ul class="navbar-nav w-100 mb-2 mb-md-0">
          <?= Nav::itemButton('http://phpmyadmin.test', 'PhpMyAdmin') ?>
          <?= Nav::itemButton('http://phpinfo.test', 'PhpInfo') ?>
          <?= Nav::item('Actual TLD : '.$domain) ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container">
	<div class="row">
        <?php foreach ($config->paths as $path): ?>
            <?php if (basename($path) == 'Sites'): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Links</h5>
                        <div class="row">
                            <?php
                            foreach(glob($path.'/*',GLOB_ONLYDIR) as $dir){
                                if (basename($dir).$domain != $_SERVER['HTTP_HOST']) {
                                    $dir = basename($dir);
                                    $link = new Link($dir);
                                    if (!$link->isExcluded()){
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
                                    $link = new Link($dir);
                                    if (!$link->isExcluded()){
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
<?php

class Link {
    public bool $secure = false;
    public string $fileName;
    public string $link;

    public function __construct($fileName)
    {
        $this->secure = $this->isSecure($fileName);
        $this->fileName = $fileName;
        $this->link = $this->secure ? 'https://'.$this->fileName.'.test' : 'http://'.$this->fileName.'.test';
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
        $crt = glob("/home/$user/.config/valet/Certificates/$fileName.test.crt");
        return !empty($crt);
    }
    public function card() : string {

        return '<div class="col-3">
                    <div class="card mb-4">
                        <div class="card-body">
						    <h5 class="card-title">'.$this->fileName.'</h5>   
						    <a target="_blank" href="'.$this->link.'" class="btn btn-primary">Accéder</a>
						    '.$this->lock().'
				        </div>
				    </div>
				</div>';
    }
    public function isExcluded(): bool
    {
        $config = json_decode(file_get_contents('config.json'));
        foreach ($config->exclude as $excluded) {
            if ($excluded == $this->fileName) {
                return true;
            }
        }
        return false;
    }

}
class Nav {

    public static function itemButton($link, $name) : string {
        return '<li class="nav-item">
                    <a target="_blank" class="nav-link" href="'.$link.'">
		            	  '.$name.'
                    </a>
                </li>
                <div class="vl"></div>';
    }
    public static function item($name) : string {
        return '<li class="nav-item">
                    <div class="nav-link pe-none">'.$name.'</div>
                </li>
                <div class="vl"></div>';

    }
}
