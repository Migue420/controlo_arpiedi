<?php
$file = 'uploads/modelos/BOTA CORDONES.png';
if (file_exists($file)) {
    echo "El archivo existe y es legible.";
} else {
    echo "El archivo NO existe en la ruta: " . realpath($file);
}
?>
