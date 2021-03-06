<!DOCTYPE html>
<!--[if lt IE 7]> <html class="lt-ie9 lt-ie8 lt-ie7" lang="es"> <![endif]-->
<!--[if IE 7]> <html class="lt-ie9 lt-ie8" lang="es"> <![endif]-->
<!--[if IE 8]> <html class="lt-ie9" lang="es"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="es"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <title>Datos de clima - Iniciar sesión</title>
  <link rel="shortcut icon" href="<?php echo site_url('images/favicon.ico')?>" />
  <link rel="stylesheet" href="<?php echo site_url('css/login.css')?>">
  <link rel="stylesheet" href="<?php echo site_url('css/style.css')?>">
</head>
<body>
  <section class="container-login">
    <div class="login">
      <h1>Iniciar sesión</h1>
      <form method="post" action="<?php echo site_url('clima/login')?>">
        <p><input type="email" name="identity" value="<?php echo set_value('identity')?>" required=required" placeholder="E-mail"></p>
        <p><input type="password" name="password" value="" required=required" placeholder="Contraseña"></p>
        <p class="remember_me">
          <label>
            <input type="checkbox" name="remember" id="remember_me">
            Recordarme
          </label>
        </p>
        <p class="submit"><input type="submit" name="commit" value="Ingresar"></p>
      </form>
      <?php if (strlen($message)>0): ?>
        <div class="login-msg"><?php echo $message?></div>
      <?php endif?>
    </div>

    <div class="login-help">
      <p>¿Olvidaste la contraseña? <a href="<?php echo site_url('clima/recuperar_clave')?>">Click aquí para recuperarla</a>.</p>
    </div>
  </section>

  <section class="about">
    <p>Desarrollado por <a href="mailto:victoradrianjimenez@gmail.com" style="font-size:inherit;">Adrian Jimenez</a> - Enero de 2015</p>
  </section>
</body>
</html>

