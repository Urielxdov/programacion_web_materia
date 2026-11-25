<?php
require __DIR__ . '/lib.php';
redirect(usuario() ? 'citas.php' : 'login.php');
