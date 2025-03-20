<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Index</title>
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

    function card($name, $link) {
        return '<div class="col-2">
                    <div class="card" style="width: 18rem;">
                        <div class="card-body">
						    <h5 class="card-title">'.$name.'</h5>
						    <a href="'.$link.'" class="btn btn-primary">Accéder</a>
				        </div>
				    </div>
				</div>';
    }


    foreach(glob('/home/'.$user.'/.config/valet/Sites/*',GLOB_ONLYDIR) as $dir){
        $dir = basename($dir);
        $link = 'https://'.$dir.'test';

        if ($dir != 'accueil') {
            echo card($dir, $link);
//            echo '<div class="col-2"><div class="card" style="width: 18rem;">';
//            echo '<div class="card-body">
//						<h5 class="card-title">'.$dir.'</h5>
//						<a href="https://" class="btn btn-primary">Accéder</a>
//				    </div>
//				</div>
//				</div>';
        }

	}

	?>
	</div>
</main>


    <script src="/docs/5.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

      
  </body>
</html>

