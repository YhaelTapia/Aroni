<?php
session_start();
include 'includes/db.php';

$mensaje = "";
$mensaje_login = "";
$mensaje_registro = "";
$formulario_mostrar = "";

$mensaje = "";
// Lógica Login
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion']) && $_POST['accion'] === 'login') {
    $nombre = trim($_POST['nombre_login']);
    $pass = $_POST['contraseña'];

    $sql = "SELECT * FROM usuarios WHERE nombre_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        if (password_verify($pass, $usuario['contraseña'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
            header("Location: index.php");
            exit;
        } else {
            $mensaje_login = "Contraseña incorrecta.";
            $formulario_mostrar = "login";
        }
    } else {
        $mensaje_login = "Usuario no encontrado.";
        $formulario_mostrar = "login";
    }
    
}
// Lógica Registro
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion']) && $_POST['accion'] === 'registro') {
    $nombre = trim($_POST['nombre_registro']);
    $correo = filter_var(trim($_POST['correo']), FILTER_VALIDATE_EMAIL);
    $pass_original = trim($_POST['contraseña']);
    $repetir_pass = trim($_POST['repetir_contraseña']);

    if (strlen($nombre) < 3) {
        $mensaje_registro = "El nombre debe tener al menos 3 caracteres.";
        $formulario_mostrar = "registro";
    } elseif (!$correo) {
        $mensaje_registro = "Correo inválido.";
        $formulario_mostrar = "registro";
    } elseif (strlen($pass_original) < 6) {
        $mensaje_registro = "La contraseña debe tener al menos 6 caracteres.";
        $formulario_mostrar = "registro";
    } elseif ($pass_original !== $repetir_pass) {
        $mensaje_registro = "Las contraseñas no coinciden.";
        $formulario_mostrar = "registro";
    } else {
        $sql_check = "SELECT * FROM usuarios WHERE nombre_usuario = ? OR correo = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $nombre, $correo);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $existe = $result_check->fetch_assoc();
            if ($existe['nombre_usuario'] === $nombre) {
                $mensaje_registro = "El nombre de usuario ya está registrado.";
            } elseif ($existe['correo'] === $correo) {
                $mensaje_registro = "El correo electrónico ya está registrado.";
            }
            $formulario_mostrar = "registro";
        } else {
            $pass = password_hash($pass_original, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nombre_usuario, correo, contraseña) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $nombre, $correo, $pass);

            if ($stmt->execute()) {
                $mensaje_login = "✅ Registro exitoso. Ahora puedes iniciar sesión.";
                $formulario_mostrar = "login";
            } else {
                $mensaje_registro = "❌ Error inesperado al registrar.";
                $formulario_mostrar = "registro";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - MEENTNOVA</title>
    <link rel="stylesheet" href="css/login.css?v=1.5">
</head>
<body>

<div class="navbar">
    <div class="logo">MEENTNOVA</div>
    <div class="menu">
        <span id="btn-login" class="nav-btn neon-blue">Iniciar sesión</span>
        <span id="btn-register" class="nav-btn neon-green">Registrarse</span>
    </div>
</div>

<div class="contenido-principal <?= $formulario_mostrar ? 'mostrar-formulario' : '' ?>" id="contenido-principal" data-form="<?= $formulario_mostrar ?>">
    <div class="form-container" id="form-container">

        <!-- Login -->
        <div class="login-box" id="form-login">
            <h2>Iniciar Sesión</h2>
            <form method="POST">
                <input type="hidden" name="accion" value="login" >
                <input type="text" name="nombre_login" placeholder="Nombre de usuario" required>
                <input type="password" name="contraseña" placeholder="Contraseña" required>
                <button type="submit">Entrar</button>
            </form>
            <p style="color: yellow;">
                <?= isset($mensaje_login) ? $mensaje_login : '' ?>
            </p>
            <p>¿No tienes cuenta? <a href="#" class="link-crear" onclick="mostrarFormulario('registro')">Crear una cuenta</a></p>
        </div>

        <!-- Registro -->
        <div class="login-box purple" id="form-registro">
        <h2>Registrarse</h2>
            <form method="POST">
                <input type="hidden" name="accion" value="registro" >
                <input type="text" name="nombre_registro" placeholder="Nombre de usuario" required>
                <input type="email" name="correo" placeholder="Correo electrónico" required>
                <input type="password" name="contraseña" placeholder="Contraseña" required>
                <input type="password" name="repetir_contraseña" placeholder="Repetir contraseña" required>
                <button type="submit">Registrarse</button>
            </form>
            <p style="color: yellow;">
                <?= isset($mensaje_registro) ? $mensaje_registro : '' ?>
            </p>

            <p>¿Ya tienes cuenta? <a href="#" class="link-crear" onclick="mostrarFormulario('login')">Iniciar sesión</a></p>
        </div>
    </div>

    <!-- Slider -->
    <div class="slider-carousel" id="slider-carousel">
        <img src="img/slide1.jpg" class="slide-img">
        <img src="img/slide2.jpg" class="slide-img">
        <img src="img/slide3.jpg" class="slide-img">
        <img src="img/slide4.jpg" class="slide-img">
        <img src="img/slide5.jpg" class="slide-img">
    </div>


</div>

<section class="welcome-section">
    <div class="welcome-block">
        <img src="img/wa.png" alt="Crea Torneos">
        <p>Crea tu propio torneo, reta a otros jugadores y gana premios mientras disfrutas tus juegos favoritos.</p>
    </div>
    <div class="welcome-block">
        <img src="img/we.png" alt="Organiza Competencias">
        <p>Organiza competencias épicas, reúne a la comunidad gamer y demuestra quién manda en el campo de batalla.</p>
    </div>
    <div class="welcome-block">
        <img src="img/wi.png" alt="Participa en Desafíos">
        <p>Participa en desafíos intensos, sube en el ranking y obtén recompensas por tu habilidad y estrategia.</p>
    </div>
</section>

<script>
// Script del carrusel
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('slider-carousel');
    if (!slider) return;
    let allSlides = Array.from(slider.querySelectorAll('.slide-img'));
    
    function updateSlidePositions(slidesToUpdate) {
        slidesToUpdate.forEach((slide, index) => {
            for (let i = 1; i <= 5; i++) { slide.classList.remove(`pos-${i}`); }
        });
        slidesToUpdate.forEach((slide, index) => {
            slide.classList.add(`pos-${index + 1}`);
        });
    }

    function rotateCarousel() {
        let visibleSlides = allSlides.filter(slide => window.getComputedStyle(slide).opacity !== '0');
        if (visibleSlides.length > 1) {
            const lastSlide = visibleSlides.pop();
            visibleSlides.unshift(lastSlide);
            allSlides = visibleSlides.concat(allSlides.filter(slide => !visibleSlides.includes(slide)));
        }
        updateSlidePositions(allSlides);
    }

    updateSlidePositions(allSlides);
    setInterval(rotateCarousel, 3000);
});

// Lógica para mostrar/ocultar formularios
const contenedor = document.getElementById('contenido-principal');
const formLogin = document.getElementById('form-login');
const formRegistro = document.getElementById('form-registro');

function mostrarFormulario(tipo) {
    contenedor.classList.add('mostrar-formulario');
    if (tipo === 'registro') {
        formLogin.style.display = 'none';
        formRegistro.style.display = 'block';
    } else {
        formLogin.style.display = 'block';
        formRegistro.style.display = 'none';
    }
}

document.getElementById('btn-login').onclick = () => {
    mostrarFormulario('login');
};

document.getElementById('btn-register').onclick = () => {
    mostrarFormulario('registro');
};

// Mostrar formulario correcto tras error
window.onload = () => {
    const cual = contenedor.getAttribute('data-form');
    if (cual === 'registro') mostrarFormulario('registro');
    else if (cual === 'login') mostrarFormulario('login');
};
</script>

</body>
</html>
