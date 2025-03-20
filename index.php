<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Valet Page Manager</title>
    <!-- Bootstrap core CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>


    <style>
      .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
      }

      @media (min-width: 768px) {
        .bd-placeholder-img-lg {
          font-size: 3.5rem;
        }
      }
    </style>

    
    <!-- Custom styles for this template -->
    <link href="navbar-top.css" rel="stylesheet">
  </head>
  <body>
    
<nav class="navbar navbar-expand-md navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <div class="collapse navbar-collapse" id="navbarCollapse">
      <ul class="navbar-nav me-auto mb-2 mb-md-0">
        <li class="nav-item">
          <a class="nav-link" href="http://phpmyadmin.test">
			  <img src="../phpmyadmin/favicon.ico" width="16">
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main class="container">
	<div class="row">
	
	<?php
    $user = exec('whoami');

    function isSecure($fileName) {
        global $user;
        $crt = glob("/home/$user/.config/valet/Certificates/$fileName.test.crt");
        return !empty($crt);
    }

    function lock($secure = false) {
        if ($secure) {
            $html = '<a href="" class="btn btn-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 448 512"><path d="M144 144l0 48 160 0 0-48c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192l0-48C80 64.5 144.5 0 224 0s144 64.5 144 144l0 48 16 0c35.3 0 64 28.7 64 64l0 192c0 35.3-28.7 64-64 64L64 512c-35.3 0-64-28.7-64-64L0 256c0-35.3 28.7-64 64-64l16 0z"/></svg>
                    </a>';
        }else{
            $html = '<a href="" class="btn btn-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 576 512"><path d="M352 144c0-44.2 35.8-80 80-80s80 35.8 80 80l0 48c0 17.7 14.3 32 32 32s32-14.3 32-32l0-48C576 64.5 511.5 0 432 0S288 64.5 288 144l0 48L64 192c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-192c0-35.3-28.7-64-64-64l-32 0 0-48z"/></svg>
                    </a>';
        }
        return $html;
    }

    function card($name, $link, $secure = false) {

        return '<div class="col-3">
                    <div class="card">
                        <div class="card-body">
						    <h5 class="card-title">'.$name.'</h5>
						    <a target="_blank" href="'.$link.'" class="btn btn-primary">Accéder</a>
						    '.lock($secure).'
				        </div>
				    </div>
				</div>';
    }

    foreach(glob('/home/'.$user.'/.config/valet/Sites/*',GLOB_ONLYDIR) as $dir){
        $dir = basename($dir);

        if (isSecure($dir)) {
            $link = 'https://'.$dir.'.test';
            $secure = true;
        }else{
            $link = 'http://'.$dir.'.test';
            $secure = false;
        }

        if ($dir.'.test' != $_SERVER['HTTP_HOST']) {
            echo card($dir, $link, $secure);
        }

	}

	?>
	</div>
</main>


    <script src="/docs/5.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

      
  </body>
</html>
<?php
