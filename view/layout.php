<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TPV · ElectroBazar</title>
    <link rel="stylesheet" href="./webroot/css/estilos.css" />
    <link rel="stylesheet" href="./webroot/css/fonts.css" />
</head>

<body>
    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-brand">
            <div class="topbar-brand-dot"></div>
            TPV · ElectroBazar
        </div>
        <div class="topbar-info">
            Caja #1 &nbsp;·&nbsp; <span id="clock">--:--</span> &nbsp;·&nbsp;
            <span id="datestr">--</span>
        </div>
        

    <?php
    require_once $view[$_SESSION['paginaEnCurso']];
    ?>
    <script src="./webroot/js/main.js"></script>
</body>

</html>